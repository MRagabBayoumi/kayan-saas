<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\StatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create([
        'name' => 'إكسسوارات',
        'code' => 'ACC',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-MAIN',
        'name' => 'المستودع الرئيسي',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->supplier = Supplier::create([
        'code' => 'SUP-01',
        'name' => 'شركة النور للتجارة والتوريدات',
        'phone' => '01000000001',
        'opening_balance' => 0.00,
        'current_balance' => 0.00,
        'is_active' => true,
    ]);

    $this->dozenUnit = Unit::create([
        'name' => 'دستة',
        'code' => 'DZN',
        'conversion_factor' => 12.0000,
        'is_default' => false,
        'is_active' => true,
    ]);

    $this->pieceUnit = Unit::create([
        'name' => 'قطعة',
        'code' => 'PCS',
        'conversion_factor' => 1.0000,
        'is_default' => true,
        'is_active' => true,
    ]);

    $this->product = Product::create([
        'sku' => 'TEST-PROD-01',
        'name' => 'قلم جاف فاخر',
        'category_id' => $this->category->id,
        'cost_price' => 120.00, // 120 per dozen = 10 per piece
        'retail_price' => 240.00,
        'unit' => 'دستة',
        'unit_factor' => 12.0000,
        'is_active' => true,
    ]);

    $this->treasury = Treasury::create([
        'code' => 'TR-MAIN',
        'name' => 'الخزينة الرئيسية',
        'current_balance' => 5000.00,
        'is_active' => true,
    ]);
});

test('draft purchase invoice can be edited and updated', function () {
    // 1. Create a draft invoice
    $response = $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => '2026-10-18',
        'payment_type' => 'credit',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    $invoice = PurchaseInvoice::first();
    expect($invoice->status)->toBe(PurchaseInvoice::STATUS_DRAFT);
    expect((float) $invoice->total_amount)->toBe(600.00);

    // 2. Open edit page
    $editRes = $this->get(route('purchases.edit', $invoice));
    $editRes->assertStatus(200);
    $editRes->assertSee('تعديل مسودة فاتورة شراء');

    // 3. Update invoice (change quantity to 8 and add discount)
    $updateRes = $this->put(route('purchases.update', $invoice), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => '2026-10-18',
        'payment_type' => 'credit',
        'discount_type' => 'fixed',
        'discount_value' => 60.00,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 8,
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    $updateRes->assertRedirect(route('purchases.show', $invoice));
    $invoice->refresh();
    // Subtotal: 8 * 120 = 960, discount: 60, total: 900
    expect((float) $invoice->subtotal)->toBe(960.00);
    expect((float) $invoice->total_amount)->toBe(900.00);
    expect($invoice->items)->toHaveCount(1);
    expect((float) $invoice->items->first()->quantity)->toBe(8.0);
});

test('cannot edit posted purchase invoice', function () {
    // Post purchase invoice
    $this->post(route('purchases.store'), [
        'action' => 'post',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => '2026-10-18',
        'payment_type' => 'credit',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    $invoice = PurchaseInvoice::first();
    expect($invoice->status)->toBe(PurchaseInvoice::STATUS_POSTED);

    $res = $this->get(route('purchases.edit', $invoice));
    $res->assertRedirect(route('purchases.show', $invoice));
    $res->assertSessionHas('error');
});

test('purchase returns index page loads properly', function () {
    $response = $this->get(route('purchase-returns.index'));
    $response->assertStatus(200);
    $response->assertSee('مرتجعات المشتريات والتوريد');
    $response->assertSee('تسجيل مرتجع جديد');
});

test('purchase return can be stored and posted with FIFO stock deduction and debt reduction', function () {
    // 1. Post a purchase invoice first to establish stock: 5 dozen = 60 base pieces @ 10 cost per piece
    $this->post(route('purchases.store'), [
        'action' => 'post',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => '2026-10-18',
        'payment_type' => 'credit',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->dozenUnit->id,
                'conversion_factor' => 12.0000,
                'quantity' => 5,
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    $invoice = PurchaseInvoice::first();
    $this->supplier->refresh();

    // Check stock was added in base pieces: 5 * 12 = 60
    $stockBalance = StockBalance::where('product_id', $this->product->id)->where('warehouse_id', $this->warehouse->id)->first();
    expect((float) $stockBalance->quantity)->toBe(60.0);
    // Supplier debt balance is credit of 600.00
    expect((float) $this->supplier->current_balance)->toBe(600.00);

    // 2. Return 1 dozen (12 base pieces @ 120 = 120.00 total) to supplier with deduct_balance
    $returnResponse = $this->post(route('purchase-returns.store'), [
        'action' => 'post',
        'purchase_invoice_id' => $invoice->id,
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'return_date' => '2026-10-18',
        'settlement_type' => PurchaseReturn::SETTLEMENT_DEDUCT_BALANCE,
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->dozenUnit->id,
                'conversion_factor' => 12.0000,
                'quantity' => 1,
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    $purchaseReturn = PurchaseReturn::first();
    expect($purchaseReturn)->not->toBeNull();
    expect($purchaseReturn->status)->toBe(PurchaseReturn::STATUS_POSTED);
    expect((float) $purchaseReturn->total_amount)->toBe(120.00);

    // Verify stock balance was deducted by 12 base pieces: 60 - 12 = 48
    $stockBalance->refresh();
    expect((float) $stockBalance->quantity)->toBe(48.0);

    // Verify supplier debt was reduced: 600 - 120 = 480
    $this->supplier->refresh();
    expect((float) $this->supplier->current_balance)->toBe(480.00);

    // Verify StatementService reflects the return
    $statementService = app(StatementService::class);
    $statement = $statementService->getSupplierStatement($this->supplier);
    expect($statement['final_balance'])->toBe(480.00);

    $returnRow = collect($statement['rows'])->firstWhere('type', 'return');
    expect($returnRow)->not->toBeNull();
    expect((float) $returnRow['debit'])->toBe(120.00);
});

test('purchase return with cash_refund records deposit in treasury', function () {
    // Stock 30 pieces directly
    $inventoryService = app(InventoryService::class);
    $inventoryService->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 30,
        costPrice: 10.00
    );

    $initialTreasuryBalance = (float) $this->treasury->current_balance;

    // Return 10 pieces with cash refund: 10 * 10 = 100.00
    $this->post(route('purchase-returns.store'), [
        'action' => 'post',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'return_date' => '2026-10-18',
        'settlement_type' => PurchaseReturn::SETTLEMENT_CASH_REFUND,
        'treasury_id' => $this->treasury->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->pieceUnit->id,
                'conversion_factor' => 1.0000,
                'quantity' => 10,
                'unit_cost' => 10.00,
            ],
        ],
    ]);

    $purchaseReturn = PurchaseReturn::first();
    expect($purchaseReturn->status)->toBe(PurchaseReturn::STATUS_POSTED);

    // Treasury balance should have increased by 100
    $this->treasury->refresh();
    expect((float) $this->treasury->current_balance)->toBe($initialTreasuryBalance + 100.00);
});

test('cannot return more stock than available in warehouse', function () {
    // Warehouse has only 5 pieces in stock
    $inventoryService = app(InventoryService::class);
    $inventoryService->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 5,
        costPrice: 10.00
    );

    // Try to return 10 pieces
    $response = $this->post(route('purchase-returns.store'), [
        'action' => 'post',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'return_date' => '2026-10-18',
        'settlement_type' => PurchaseReturn::SETTLEMENT_DEDUCT_BALANCE,
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'unit_cost' => 10.00,
            ],
        ],
    ]);

    $response->assertSessionHas('error');
    // Ensure no return was posted
    expect(PurchaseReturn::where('status', PurchaseReturn::STATUS_POSTED)->count())->toBe(0);
});

test('purchase return print page and direct print API work correctly with formats', function () {
    $inventoryService = app(InventoryService::class);
    $inventoryService->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 20,
        costPrice: 10.00
    );

    $this->post(route('purchase-returns.store'), [
        'action' => 'post',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'return_date' => '2026-10-18',
        'settlement_type' => PurchaseReturn::SETTLEMENT_DEDUCT_BALANCE,
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->pieceUnit->id,
                'quantity' => 5,
                'unit_cost' => 10.00,
            ],
        ],
    ]);

    $purchaseReturn = PurchaseReturn::first();

    // 1. Web preview route for a4, a5, thermal_80mm
    $responseA4 = $this->get(route('purchase-returns.print', [$purchaseReturn, 'format' => 'a4']));
    $responseA4->assertStatus(200);
    $responseA4->assertSee('مرتجع مشتريات');

    $responseThermal = $this->get(route('purchase-returns.print', [$purchaseReturn, 'format' => 'thermal_80mm']));
    $responseThermal->assertStatus(200);

    // 2. Direct print via Python route
    $responseDirect = $this->postJson(route('purchase-returns.direct-print', [$purchaseReturn, 'format' => 'thermal_80mm']));
    $responseDirect->assertStatus(200);
    $responseDirect->assertJson(['success' => true]);

    // 3. Show page contains print combobox dropdown
    $responseShow = $this->get(route('purchase-returns.show', $purchaseReturn));
    $responseShow->assertStatus(200);
    $responseShow->assertSee('data-component="print-dropdown"', false);
    $responseShow->assertSee('طباعة إذن المرتجع');
});
