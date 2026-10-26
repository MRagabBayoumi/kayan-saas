<?php

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
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
        'name' => 'إلكترونيات',
        'code' => 'ELEC',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-01',
        'name' => 'المخزن الرئيسي',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->unit = Unit::create([
        'name' => 'قطعة',
        'code' => 'PCS',
        'operator' => '*',
        'conversion_factor' => 1.0,
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'sku' => 'TEST-001',
        'barcode' => '622000000001',
        'name' => 'شاحن هاتف سريع',
        'cost_price' => 50.00,
        'retail_price' => 100.00,
        'unit' => 'قطعة',
        'unit_factor' => 1.0,
        'pieces_per_package' => 1,
        'is_active' => true,
    ]);

    // Add 100 pieces to inventory
    app(InventoryService::class)->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 100.0,
        costPrice: 50.00,
        notes: 'Opening stock for deferred payment test'
    );

    // Open Cashier Shift for Admin
    $this->shift = CashierShift::create([
        'shift_number' => CashierShift::generateShiftNumber(),
        'user_id' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => CashierShift::STATUS_OPEN,
        'opened_at' => now(),
        'opening_balance' => 500.00,
        'cash_sales_amount' => 0.00,
    ]);
});

test('cash sale records full cash in shift drawer and leaves customer debt unchanged', function () {
    $customer = Customer::create([
        'name' => 'عميل نقدي تجريبي',
        'phone' => '01011112222',
        'current_balance' => 0.00,
        'credit_limit' => 0.00,
        'is_active' => true,
    ]);

    $response = $this->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'cash',
        'paid_amount' => 200.00,
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unit->id,
                'quantity' => 2,
                'unit_price' => 100.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $invoice = SalesInvoice::latest()->first();
    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->total_amount)->toBe(200.00)
        ->and($invoice->payment_type)->toBe(SalesInvoice::PAYMENT_CASH)
        ->and((float) $invoice->paid_amount)->toBe(200.00)
        ->and((float) $invoice->remaining_amount)->toBe(0.00);

    // Cashier shift cash sales should have full 200.00
    $this->shift->refresh();
    expect((float) $this->shift->cash_sales_amount)->toBe(200.00);

    // Customer current balance remains 0
    $customer->refresh();
    expect((float) $customer->current_balance)->toBe(0.00);
});

test('credit sale records 0 cash in shift drawer and adds full debt to customer on posting', function () {
    $customer = Customer::create([
        'name' => 'عميل آجل معتمد',
        'phone' => '01033334444',
        'current_balance' => 50.00,
        'credit_limit' => 1000.00,
        'is_active' => true,
    ]);

    $response = $this->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'credit',
        'paid_amount' => 0.00,
        'action' => 'post',
        'due_date' => now()->addDays(30)->toDateString(),
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unit->id,
                'quantity' => 3,
                'unit_price' => 100.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $invoice = SalesInvoice::latest()->first();
    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->total_amount)->toBe(300.00)
        ->and($invoice->payment_type)->toBe(SalesInvoice::PAYMENT_CREDIT)
        ->and((float) $invoice->paid_amount)->toBe(0.00)
        ->and((float) $invoice->remaining_amount)->toBe(300.00);

    // Shift cash sales remains 0
    $this->shift->refresh();
    expect((float) $this->shift->cash_sales_amount)->toBe(0.00);

    // Customer balance increased by 300 (50 + 300 = 350)
    $customer->refresh();
    expect((float) $customer->current_balance)->toBe(350.00);
});

test('partial sale records cash portion in shift and remaining debt in customer balance', function () {
    $customer = Customer::create([
        'name' => 'عميل دفع جزئي',
        'phone' => '01055556666',
        'current_balance' => 0.00,
        'credit_limit' => 2000.00,
        'is_active' => true,
    ]);

    $response = $this->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'partial',
        'paid_amount' => 100.00,
        'action' => 'post',
        'due_date' => now()->addDays(15)->toDateString(),
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unit->id,
                'quantity' => 4,
                'unit_price' => 100.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $invoice = SalesInvoice::latest()->first();
    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->total_amount)->toBe(400.00)
        ->and($invoice->payment_type)->toBe(SalesInvoice::PAYMENT_PARTIAL)
        ->and((float) $invoice->paid_amount)->toBe(100.00)
        ->and((float) $invoice->remaining_amount)->toBe(300.00);

    // Cashier shift cash sales should receive only 100.00
    $this->shift->refresh();
    expect((float) $this->shift->cash_sales_amount)->toBe(100.00);

    // Customer balance increased by 300.00
    $customer->refresh();
    expect((float) $customer->current_balance)->toBe(300.00);
});

test('credit sale is rejected when customer credit limit is exceeded', function () {
    $customer = Customer::create([
        'name' => 'عميل محدود الائتمان',
        'phone' => '01077778888',
        'current_balance' => 400.00,
        'credit_limit' => 500.00, // available is only 100
        'is_active' => true,
    ]);

    $response = $this->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'credit',
        'paid_amount' => 0.00,
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unit->id,
                'quantity' => 2,
                'unit_price' => 100.00, // 200 > 100 available credit
                'line_discount' => 0,
            ],
        ],
    ]);

    $response->assertSessionHasErrors(['error']);
    expect(SalesInvoice::count())->toBe(0);
});

test('credit purchase increments supplier balance upon posting', function () {
    $supplier = Supplier::create([
        'name' => 'شركة التوريدات العالمية',
        'phone' => '01099990000',
        'current_balance' => 1500.00,
        'is_active' => true,
    ]);

    $response = $this->post(route('purchases.store'), [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'payment_type' => 'credit',
        'paid_amount' => 0.00,
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'unit_cost' => 50.00,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $invoice = PurchaseInvoice::latest()->first();
    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->total_amount)->toBe(500.00)
        ->and($invoice->payment_type)->toBe(PurchaseInvoice::PAYMENT_CREDIT)
        ->and((float) $invoice->paid_amount)->toBe(0.00)
        ->and((float) $invoice->remaining_amount)->toBe(500.00);

    // Supplier balance should increase by 500 (1500 + 500 = 2000)
    $supplier->refresh();
    expect((float) $supplier->current_balance)->toBe(2000.00);
});

test('voiding a partial sale decrements customer balance and refunds drawer cash', function () {
    $customer = Customer::create([
        'name' => 'عميل إلغاء بيع جزئي',
        'phone' => '01012345678',
        'current_balance' => 0.00,
        'credit_limit' => 2000.00,
        'is_active' => true,
    ]);

    $this->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'partial',
        'paid_amount' => 100.00,
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unit->id,
                'quantity' => 2,
                'unit_price' => 100.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest()->first();
    $customer->refresh();
    $this->shift->refresh();
    expect((float) $customer->current_balance)->toBe(100.00)
        ->and((float) $this->shift->cash_sales_amount)->toBe(100.00);

    // Void the invoice
    $response = $this->post(route('sales.void', $invoice), [
        'void_reason' => 'خطأ في عملية البيع الجزئي',
    ]);

    $response->assertRedirect(route('sales.show', $invoice));

    $customer->refresh();
    $this->shift->refresh();
    expect((float) $customer->current_balance)->toBe(0.00)
        ->and((float) $this->shift->cash_returns_amount)->toBe(100.00)
        ->and($this->shift->calculateExpectedBalance())->toBe(500.00);
});

test('customer and supplier show views display invoice ledger and credit metrics', function () {
    $customer = Customer::create([
        'name' => 'عميل عرض الحساب',
        'phone' => '01011119999',
        'current_balance' => 450.00,
        'credit_limit' => 2000.00,
        'is_active' => true,
    ]);

    $supplier = Supplier::create([
        'name' => 'مورد عرض الحساب',
        'phone' => '01022228888',
        'current_balance' => 1200.00,
        'is_active' => true,
    ]);

    $customerRes = $this->get(route('customers.show', $customer));
    $customerRes->assertOk();
    $customerRes->assertSee('سجل فواتير المبيعات وحركات الآجل للعميل');
    $customerRes->assertSee('إجمالي المشتريات');
    $customerRes->assertSee('إجمالي المسدد نقداً');
    $customerRes->assertSee('الرصيد الآجل المعلق');

    $supplierRes = $this->get(route('suppliers.show', $supplier));
    $supplierRes->assertOk();
    $supplierRes->assertSee('سجل فواتير الشراء وحركات الآجل للمورد');
    $supplierRes->assertSee('إجمالي المشتريات والتوريدات');
    $supplierRes->assertSee('إجمالي المسدد للمورد');
    $supplierRes->assertSee('الرصيد المعلق المستحق للمورد');
});
