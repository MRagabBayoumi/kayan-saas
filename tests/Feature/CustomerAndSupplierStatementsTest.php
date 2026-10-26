<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\StatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'مدير النظام',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-MAIN',
        'name' => 'المخزن الرئيسي',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->category = Category::create([
        'name' => 'إكسسوارات',
        'code' => 'ACC',
        'is_active' => true,
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
        'sku' => 'HAIR-001',
        'barcode' => '622000000999',
        'name' => 'طوق شعر لؤلؤي',
        'cost_price' => 10.00,
        'retail_price' => 25.00,
        'wholesale_price' => 18.00,
        'distributor_price' => 15.00,
        'unit' => 'قطعة',
        'unit_factor' => 1.0,
        'pieces_per_package' => 1,
        'is_active' => true,
    ]);

    app(InventoryService::class)->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 500,
        costPrice: 10.00,
        notes: 'Initial stock for statement testing'
    );
});

test('customer statement computes debits, credits, and running balance accurately', function () {
    $customer = Customer::create([
        'name' => 'العميل محمد حسن',
        'phone' => '01012345678',
        'pricing_tier' => 'wholesale',
        'opening_balance' => 100.00,
        'current_balance' => 100.00,
        'is_active' => true,
    ]);

    // 1. Credit Invoice: Total 200, paid 50, remaining 150
    $inv1 = SalesInvoice::create([
        'invoice_number' => 'INV-0001',
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'user_id' => $this->admin->id,
        'invoice_date' => now()->subDays(5)->toDateString(),
        'subtotal' => 200.00,
        'total_amount' => 200.00,
        'payment_type' => SalesInvoice::PAYMENT_PARTIAL,
        'paid_amount' => 50.00,
        'remaining_amount' => 150.00,
        'status' => SalesInvoice::STATUS_POSTED,
    ]);

    SalesInvoiceItem::create([
        'sales_invoice_id' => $inv1->id,
        'product_id' => $this->product->id,
        'unit_id' => $this->unit->id,
        'unit_name' => 'قطعة',
        'conversion_factor' => 1.0,
        'quantity' => 10,
        'base_quantity' => 10,
        'unit_price' => 20.00,
        'unit_cost' => 10.00,
        'subtotal' => 200.00,
        'total_cogs' => 100.00,
    ]);

    // 2. Full Cash Invoice: Total 80, paid 80, remaining 0
    $inv2 = SalesInvoice::create([
        'invoice_number' => 'INV-0002',
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'user_id' => $this->admin->id,
        'invoice_date' => now()->subDays(2)->toDateString(),
        'subtotal' => 80.00,
        'total_amount' => 80.00,
        'payment_type' => SalesInvoice::PAYMENT_CASH,
        'paid_amount' => 80.00,
        'remaining_amount' => 0.00,
        'status' => SalesInvoice::STATUS_POSTED,
    ]);

    SalesInvoiceItem::create([
        'sales_invoice_id' => $inv2->id,
        'product_id' => $this->product->id,
        'unit_id' => $this->unit->id,
        'unit_name' => 'قطعة',
        'conversion_factor' => 1.0,
        'quantity' => 4,
        'base_quantity' => 4,
        'unit_price' => 20.00,
        'unit_cost' => 10.00,
        'subtotal' => 80.00,
        'total_cogs' => 40.00,
    ]);

    $service = app(StatementService::class);
    $statement = $service->getCustomerStatement($customer);

    expect($statement['opening_balance'])->toEqual(100.00)
        ->and($statement['total_debits'])->toEqual(280.00)
        ->and($statement['total_credits'])->toEqual(130.00)
        ->and($statement['final_balance'])->toEqual(250.00); // 100 + 280 - 130 = 250

    // Test Statement Web View
    $response = $this->get(route('customers.statement', $customer));
    $response->assertOk();
    $response->assertSee('كشف حساب العميل: العميل محمد حسن');
    $response->assertSee('INV-0001');
    $response->assertSee('INV-0002');
});

test('customer statement can be printed in a4, a5 and thermal formats', function () {
    $customer = Customer::create([
        'name' => 'عميل طباعة كشف حساب',
        'phone' => '01099998888',
        'pricing_tier' => 'retail',
        'opening_balance' => 0.00,
        'current_balance' => 500.00,
        'is_active' => true,
    ]);

    foreach (['a4', 'a5', 'thermal_80mm'] as $format) {
        $response = $this->get(route('customers.statement.print', [$customer, 'format' => $format]));
        $response->assertOk();
        $response->assertSee('كشف حساب');
    }
});

test('customer purchased items report aggregates quantities and compares price tiers', function () {
    $customer = Customer::create([
        'name' => 'عميل مسحوبات جملة',
        'phone' => '01122334455',
        'pricing_tier' => 'wholesale',
        'is_active' => true,
    ]);

    $inv = SalesInvoice::create([
        'invoice_number' => 'INV-PUR-01',
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'user_id' => $this->admin->id,
        'invoice_date' => now()->toDateString(),
        'subtotal' => 180.00,
        'total_amount' => 180.00,
        'payment_type' => SalesInvoice::PAYMENT_CASH,
        'paid_amount' => 180.00,
        'remaining_amount' => 0.00,
        'status' => SalesInvoice::STATUS_POSTED,
    ]);

    SalesInvoiceItem::create([
        'sales_invoice_id' => $inv->id,
        'product_id' => $this->product->id,
        'unit_id' => $this->unit->id,
        'unit_name' => 'قطعة',
        'conversion_factor' => 1.0,
        'quantity' => 10,
        'base_quantity' => 10,
        'unit_price' => 18.00,
        'unit_cost' => 10.00,
        'subtotal' => 180.00,
        'total_cogs' => 100.00,
    ]);

    $response = $this->get(route('customers.purchased-items', $customer));
    $response->assertOk();
    $response->assertSee('طوق شعر لؤلؤي');
    $response->assertSee('180.00');

    // Test print endpoints
    foreach (['a4', 'a5', 'thermal_80mm'] as $format) {
        $printResponse = $this->get(route('customers.purchased-items.print', [$customer, 'format' => $format]));
        $printResponse->assertOk();
        $printResponse->assertSee('طوق شعر لؤلؤي');
    }
});

test('customer whatsapp share URL generates valid encoded Arabic message', function () {
    $customer = Customer::create([
        'name' => 'العميل أحمد كامل',
        'phone' => '01012345678',
        'current_balance' => 450.00,
        'is_active' => true,
    ]);

    $service = app(StatementService::class);
    $url = $service->generateWhatsAppUrl($customer);

    expect($url)->toContain('api.whatsapp.com/send?phone=201012345678')
        ->and($url)->toContain('text=');

    $response = $this->get(route('customers.whatsapp', $customer));
    $response->assertRedirect($url);
});

test('supplier statement calculates running balance with purchases and payments', function () {
    $supplier = Supplier::create([
        'name' => 'مصنع تشجيانغ للأطواق',
        'supplier_type' => 'china_factory',
        'currency' => 'CNY',
        'opening_balance' => 500.00,
        'current_balance' => 500.00,
        'is_active' => true,
    ]);

    PurchaseInvoice::create([
        'invoice_number' => 'PUR-000001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'user_id' => $this->admin->id,
        'invoice_date' => now()->toDateString(),
        'subtotal' => 1200.00,
        'total_amount' => 1200.00,
        'payment_type' => PurchaseInvoice::PAYMENT_PARTIAL,
        'paid_amount' => 400.00,
        'remaining_amount' => 800.00,
        'status' => PurchaseInvoice::STATUS_POSTED,
    ]);

    $service = app(StatementService::class);
    $statement = $service->getSupplierStatement($supplier);

    expect($statement['opening_balance'])->toEqual(500.00)
        ->and($statement['total_credits'])->toEqual(1200.00)
        ->and($statement['total_debits'])->toEqual(400.00)
        ->and($statement['final_balance'])->toEqual(1300.00); // 500 + 1200 - 400 = 1300

    $response = $this->get(route('suppliers.statement', $supplier));
    $response->assertOk();
    $response->assertSee('كشف حساب المورد: مصنع تشجيانغ للأطواق');
    $response->assertSee('PUR-000001');

    foreach (['a4', 'a5', 'thermal_80mm'] as $format) {
        $printResponse = $this->get(route('suppliers.statement.print', [$supplier, 'format' => $format]));
        $printResponse->assertOk();
        $printResponse->assertSee('كشف حساب');
    }
});

test('supplier balance due report lists outstanding balances and prints in all formats', function () {
    Supplier::create([
        'name' => 'مورد مستحق تجريبي 1',
        'supplier_type' => 'china_factory',
        'currency' => 'CNY',
        'current_balance' => 2500.00,
        'is_active' => true,
    ]);

    Supplier::create([
        'name' => 'مورد مستحق تجريبي 2',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
        'current_balance' => 8000.00,
        'is_active' => true,
    ]);

    $response = $this->get(route('suppliers.balance-due-report'));
    $response->assertOk();
    $response->assertSee('تقرير أرصدة ومستحقات الموردين');
    $response->assertSee('مورد مستحق تجريبي 1');
    $response->assertSee('مورد مستحق تجريبي 2');

    foreach (['a4', 'a5', 'thermal_80mm'] as $format) {
        $printResponse = $this->get(route('suppliers.balance-due-report.print', ['format' => $format]));
        $printResponse->assertOk();
        $printResponse->assertSee('تقرير أرصدة الموردين المستحقة');
    }
});
