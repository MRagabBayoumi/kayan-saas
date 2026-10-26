<?php

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
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

    $this->customer = Customer::create([
        'name' => 'عميل تجزئة تجريبي',
        'phone' => '01099887766',
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

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'sku' => 'TEST-PROD-01',
        'barcode' => '6221100223344',
        'name' => 'منتج تجريبي للاختبار',
        'cost_price' => 20.00,
        'retail_price' => 35.00,
        'wholesale_price' => 30.00,
        'distributor_price' => 25.00,
        'unit' => 'قطعة',
        'unit_factor' => 1.0,
        'pieces_per_package' => 1,
        'is_active' => true,
    ]);

    // Add initial stock batch of 50 pieces at 20 LE cost
    $inventoryService = app(InventoryService::class);
    $result = $inventoryService->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 50.0,
        costPrice: 20.00,
        retailPrice: 35.00,
        referenceType: 'opening_balance'
    );
    $this->batch = $result['batch'];
});

test('unposting a posted sales invoice restores stock to batches and warehouse balance and redirects to edit', function () {
    // 1. Create and post sales invoice for 10 pieces
    $response = $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 10,
                'unit_price' => 35.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $response->assertRedirect();
    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED);

    // Stock should be 50 - 10 = 40
    $stockBalance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $stockBalance->quantity)->toBe(40.0);

    $this->batch->refresh();
    expect((float) $this->batch->remaining_quantity)->toBe(40.0);

    // 2. Unpost and Edit
    $unpostResponse = $this->post(route('sales.unpost-and-edit', $invoice));
    $unpostResponse->assertRedirect(route('sales.edit', $invoice));

    // Status is draft
    $invoice->refresh();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_DRAFT);

    // Stock balance restored to 50
    $stockBalance->refresh();
    expect((float) $stockBalance->quantity)->toBe(50.0);

    // Batch restored to 50
    $this->batch->refresh();
    expect((float) $this->batch->remaining_quantity)->toBe(50.0);
});

test('sales edit view renders successfully with items pre-populated', function () {
    // Create draft invoice
    $response = $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'action' => 'draft',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 8,
                'unit_price' => 35.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->first();

    $editResponse = $this->get(route('sales.edit', $invoice));
    $editResponse->assertOk();
    $editResponse->assertSee($invoice->invoice_number);
    $editResponse->assertSee('منتج تجريبي للاختبار');
    $editResponse->assertSee('data-page="sales-edit"', false);
});

test('updating an invoice recalculates totals and re-posts with correct FIFO deduction', function () {
    // Create draft invoice with 5 pieces
    $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'action' => 'draft',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 5,
                'unit_price' => 35.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->first();

    // Update with 15 pieces and post
    $updateResponse = $this->put(route('sales.update', $invoice), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 15,
                'unit_price' => 35.00,
                'line_discount' => 10.00, // line discount
            ],
        ],
    ]);

    $updateResponse->assertRedirect(route('sales.show', $invoice));

    $invoice->refresh();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED);
    // 15 * 35 - 10 = 515
    expect((float) $invoice->total_amount)->toBe(515.0);

    // Stock deducted 15 from 50 = 35
    $stockBalance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $stockBalance->quantity)->toBe(35.0);
});

test('net profit is visible to admin and company manager, but hidden from cashiers and unauthorized users', function () {
    // 1. Create a posted sales invoice with profit
    $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 10,
                'unit_price' => 35.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->first();

    // 2. Admin should see profit on index and show
    $this->actingAs($this->admin);
    $adminIndex = $this->get(route('sales.index'));
    $adminIndex->assertOk();
    $adminIndex->assertSee('إجمالي الأرباح المحققة');

    $adminShow = $this->get(route('sales.show', $invoice));
    $adminShow->assertOk();
    $adminShow->assertSee('صافي ربح الفاتورة:');
    $adminShow->assertSee('إجمالي تكلفة البضاعة المباعة (COGS):');

    // 3. Company Manager should also see profit
    $companyManagerRole = Role::create([
        'name' => 'مدير الشركة',
        'slug' => 'مدير_للشركه',
        'is_active' => true,
    ]);

    $companyManager = User::factory()->create([
        'name' => 'مدير عام الشركة',
        'role' => 'مدير_للشركه',
        'role_id' => $companyManagerRole->id,
        'is_active' => true,
    ]);

    $this->actingAs($companyManager);
    $managerIndex = $this->get(route('sales.index'));
    $managerIndex->assertOk();
    $managerIndex->assertSee('إجمالي الأرباح المحققة');

    $managerShow = $this->get(route('sales.show', $invoice));
    $managerShow->assertOk();
    $managerShow->assertSee('صافي ربح الفاتورة:');

    // 4. Cashier user without view-cogs must NOT see profit or COGS
    $cashierRole = Role::create([
        'name' => 'كاشير مبيعات',
        'slug' => User::ROLE_CASHIER,
        'is_active' => true,
    ]);

    $cashier = User::factory()->create([
        'name' => 'كاشير المحل',
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
        'is_active' => true,
    ]);

    $this->actingAs($cashier);
    $cashierIndex = $this->get(route('sales.index'));
    $cashierIndex->assertOk();
    $cashierIndex->assertDontSee('إجمالي الأرباح المحققة');
    $cashierIndex->assertSee('مسودات قيد المراجعة');

    $cashierShow = $this->get(route('sales.show', $invoice));
    $cashierShow->assertOk();
    $cashierShow->assertDontSee('صافي ربح الفاتورة:');
    $cashierShow->assertDontSee('إجمالي تكلفة البضاعة المباعة (COGS):');
});

test('unposting a deferred credit invoice with customer and active shift reverts customer balance and shift return', function () {
    $shift = CashierShift::create([
        'shift_number' => CashierShift::generateShiftNumber(),
        'user_id' => $this->admin->id,
        'opened_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
        'opened_at' => now(),
        'opening_balance' => 500.00,
        'status' => CashierShift::STATUS_OPEN,
    ]);

    // Initial customer balance is 100
    $this->customer->update(['current_balance' => 100.00]);

    // Create posted invoice: Total = 350, Paid = 150 (recorded in shift), Remaining = 200 (added to customer balance)
    $response = $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'payment_type' => 'partial',
        'action' => 'post',
        'paid_amount' => 150.00,
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 10,
                'unit_price' => 35.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $response->assertRedirect();
    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED);
    expect((float) $invoice->remaining_amount)->toBe(200.0);

    // Customer balance should be 100 + 200 = 300
    $this->customer->refresh();
    expect((float) $this->customer->current_balance)->toBe(300.0);

    // Unpost the invoice for editing
    $unpostResponse = $this->post(route('sales.unpost-and-edit', $invoice));
    $unpostResponse->assertRedirect(route('sales.edit', $invoice));

    // Customer balance must be reverted by remaining 200 => back to 100
    $this->customer->refresh();
    expect((float) $this->customer->current_balance)->toBe(100.0);

    // Invoice status is draft
    $invoice->refresh();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_DRAFT);
});

test('sales invoice show page correctly renders non-zero FIFO batch allocation details', function () {
    $response = $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'cash',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 5,
                'unit_price' => 35.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED);

    $showResponse = $this->get(route('sales.show', $invoice));
    $showResponse->assertOk();
    $showResponse->assertSee('تفاصيل توزيع دفعات المخزن (FIFO Batch Allocation):');
    $showResponse->assertSee('5 قطعة');
    $showResponse->assertSee('35.00 ج.م');
    $showResponse->assertSee('سارٍ ومصروف');
});
