<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'مدير النظام',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    $this->category = Category::create([
        'name' => 'أدوات مكتبية',
        'code' => 'STAT',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-MAIN',
        'name' => 'المستودع الرئيسي',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->customer = Customer::create([
        'name' => 'عميل تجريبي',
        'phone' => '01012345678',
        'pricing_tier' => 'retail',
        'is_active' => true,
    ]);

    $this->unitPiece = Unit::create([
        'name' => 'قطعة',
        'code' => 'PCS',
        'operator' => '*',
        'conversion_factor' => 1.0000,
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->unitDozen = Unit::create([
        'name' => 'دستة',
        'code' => 'DOZ',
        'operator' => '*',
        'conversion_factor' => 12.0000,
        'is_active' => true,
        'is_default' => false,
    ]);

    // Product registered as piece (قطعة), retail price = 35 LE / piece
    $this->product = Product::create([
        'category_id' => $this->category->id,
        'sku' => 'PEN-BLUE-01',
        'barcode' => '6221000111222',
        'name' => 'قلم جاف أزرق روترنج',
        'cost_price' => 20.00,
        'retail_price' => 35.00,
        'wholesale_price' => 30.00,
        'distributor_price' => 25.00,
        'unit' => 'قطعة',
        'unit_factor' => 1.0,
        'pieces_per_package' => 12,
        'is_active' => true,
    ]);

    $inventoryService = app(InventoryService::class);

    // Batch 1 (older): 10 pieces @ 20 LE cost
    $res1 = $inventoryService->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 10.0,
        costPrice: 20.00,
        retailPrice: 35.00,
        referenceType: 'opening_balance'
    );
    $this->batch1 = $res1['batch'];

    // Sleep briefly to ensure distinct FIFO timestamps if necessary
    usleep(50000);

    // Batch 2 (newer): 20 pieces @ 25 LE cost
    $res2 = $inventoryService->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 20.0,
        costPrice: 25.00,
        retailPrice: 35.00,
        referenceType: 'purchase_invoice'
    );
    $this->batch2 = $res2['batch'];
});

test('selling by dozen deducts 12 pieces via FIFO across batches and accurately calculates COGS and profit', function () {
    // We have 30 pieces total (10 in batch1 @ 20 LE, 20 in batch2 @ 25 LE).
    // Selling 1 dozen (دستة) at 12 * 35 = 420 LE.
    // FIFO should deduct: 10 pieces from batch1 (cost 200 LE), 2 pieces from batch2 (cost 50 LE).
    // Total COGS = 250 LE. Total profit = 420 - 250 = 170 LE.
    // Remaining stock = 18 pieces (0 in batch1, 18 in batch2).

    $response = $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0,
                'quantity' => 1,
                'unit_price' => 420.00, // 12 * 35 LE
                'line_discount' => 0,
            ],
        ],
    ]);

    $response->assertRedirect();
    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED);

    // 1. Verify item attributes
    $item = $invoice->items->first();
    expect((float) $item->quantity)->toBe(1.0);
    expect((float) $item->conversion_factor)->toBe(12.0);
    expect((float) $item->unit_price)->toBe(420.0);
    expect((float) $item->subtotal)->toBe(420.0);

    // 2. Verify FIFO deduction across batches
    $this->batch1->refresh();
    $this->batch2->refresh();

    expect((float) $this->batch1->remaining_quantity)->toBe(0.0);
    expect((float) $this->batch2->remaining_quantity)->toBe(18.0);

    // 3. Verify warehouse stock balance
    $stockBalance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $stockBalance->quantity)->toBe(18.0);

    // 4. Verify COGS and Profit
    // Batch 1: 10 pieces * 20 = 200
    // Batch 2: 2 pieces * 25 = 50
    // Total COGS = 250
    // Profit = 420 - 250 = 170
    $invoice->refresh();
    $item->refresh();

    expect((float) $item->total_cogs)->toBe(250.0);
    expect((float) $item->total_profit)->toBe(170.0);
    expect((float) $invoice->total_cogs)->toBe(250.0);
    expect((float) $invoice->total_profit)->toBe(170.0);
});

test('unposting a dozen sales invoice restores all 12 pieces back to the original FIFO batches', function () {
    // 1. Sell 1 dozen
    $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0,
                'quantity' => 1,
                'unit_price' => 420.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED);

    // 2. Unpost and Edit
    $unpostResponse = $this->post(route('sales.unpost-and-edit', $invoice));
    $unpostResponse->assertRedirect(route('sales.edit', $invoice));

    // 3. Verify batches are restored
    $this->batch1->refresh();
    $this->batch2->refresh();

    expect((float) $this->batch1->remaining_quantity)->toBe(10.0);
    expect((float) $this->batch2->remaining_quantity)->toBe(20.0);

    $stockBalance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $stockBalance->quantity)->toBe(30.0);
});

test('mixed cart with dozen and individual pieces correctly aggregates FIFO deductions and stock breakdown', function () {
    // 30 pieces total in stock.
    // Sell: 1 dozen (12 pcs) + 2 pieces (2 pcs) = 14 pcs in total.
    // FIFO deductions:
    // Batch 1 (10 pcs @ 20 LE) -> 10 pcs depleted
    // Batch 2 (20 pcs @ 25 LE) -> 4 pcs deducted (16 pcs left)
    // Remaining in stock = 16 pcs.
    // formatStockBreakdown: 16 pcs with factor 12 -> 1 دستة و 4 قطعة (16 قطعة).

    $response = $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0,
                'quantity' => 1,
                'unit_price' => 420.00,
                'line_discount' => 0,
            ],
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 2,
                'unit_price' => 35.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $response->assertRedirect();
    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED);

    $this->batch1->refresh();
    $this->batch2->refresh();

    expect((float) $this->batch1->remaining_quantity)->toBe(0.0);
    expect((float) $this->batch2->remaining_quantity)->toBe(16.0);

    $stockBalance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $stockBalance->quantity)->toBe(16.0);

    // Verify formatStockBreakdown on the Product model
    $this->product->refresh();
    expect($this->product->formatStockBreakdown())->toContain('1 دستة و 4 قطعة');
});
