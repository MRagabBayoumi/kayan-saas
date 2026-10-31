<?php

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'مدير النظام',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    CompanySetting::create([
        'company_name' => 'شركة كيان للتجارة والتوزيع',
        'phone' => '01000000000',
        'email' => 'info@kayan.test',
        'address' => 'القاهرة، مصر',
    ]);

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
});

test('can access customer balance due report view and view customer balances', function () {
    $customer1 = Customer::create([
        'name' => 'عميل جملة تجريبي 1',
        'phone' => '01011111111',
        'current_balance' => 15000.50,
        'customer_type' => 'wholesale',
        'is_active' => true,
    ]);

    $customer2 = Customer::create([
        'name' => 'عميل تجزئة تجريبي 2',
        'phone' => '01022222222',
        'current_balance' => 0.00,
        'customer_type' => 'retail',
        'is_active' => true,
    ]);

    $response = $this->get(route('customers.balance-due-report'));
    $response->assertOk();
    $response->assertSee('تقرير أرصدة ومديونيات العملاء');
    $response->assertSee('عميل جملة تجريبي 1');
    $response->assertSee('15,000.50');

    // Test filter only_with_balance
    $filterResponse = $this->get(route('customers.balance-due-report', ['only_with_balance' => 1]));
    $filterResponse->assertOk();
    $filterResponse->assertSee('عميل جملة تجريبي 1');
    $filterResponse->assertDontSee('عميل تجزئة تجريبي 2');
});

test('can print customer balance due report in A4, A5, and Thermal formats', function () {
    Customer::create([
        'name' => 'مؤسسة النور للتجارة',
        'phone' => '01033333333',
        'current_balance' => 25000.00,
        'customer_type' => 'wholesale',
        'is_active' => true,
    ]);

    // Test A4 print
    $a4Response = $this->get(route('customers.balance-due-report.print', ['format' => 'a4']));
    $a4Response->assertOk();
    $a4Response->assertSee('مؤسسة النور للتجارة');
    $a4Response->assertSee('25,000.00');
    $a4Response->assertSee('A4 قياسي');

    // Test A5 print
    $a5Response = $this->get(route('customers.balance-due-report.print', ['format' => 'a5']));
    $a5Response->assertOk();
    $a5Response->assertSee('data-format="a5"', false);

    // Test Thermal 80mm print
    $thermalResponse = $this->get(route('customers.balance-due-report.print', ['format' => 'thermal_80mm']));
    $thermalResponse->assertOk();
    $thermalResponse->assertSee('thermal-receipt');
    $thermalResponse->assertSee('مديونيات وحسابات العملاء');
});

test('can access product price list and packing list report view', function () {
    $product1 = Product::create([
        'category_id' => $this->category->id,
        'sku' => 'PRD-001',
        'name' => 'كابل شحن سريع 65W',
        'cost_price' => 50.00,
        'retail_price' => 120.00,
        'wholesale_price' => 80.00,
        'distributor_price' => 70.00,
        'unit' => 'قطعة',
        'pieces_per_package' => 50,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.price-list'));
    $response->assertOk();
    $response->assertSee('قائمة أسعار وبيان تعبئة المنتجات للعملاء');
    $response->assertSee('كابل شحن سريع 65W');
    $response->assertSee('PRD-001');
    $response->assertSee('50 قطعة / عبوة');
    $response->assertSee('80.00');
});

test('can print product price and packing list in A4 and A5 formats', function () {
    Product::create([
        'category_id' => $this->category->id,
        'sku' => 'PRD-002',
        'name' => 'سماعة رأس لاسلكية',
        'cost_price' => 200.00,
        'retail_price' => 450.00,
        'wholesale_price' => 320.00,
        'distributor_price' => 290.00,
        'unit' => 'قطعة',
        'pieces_per_package' => 20,
        'is_active' => true,
    ]);

    // Test A4 print
    $a4Response = $this->get(route('products.price-list.print', ['format' => 'a4']));
    $a4Response->assertOk();
    $a4Response->assertSee('سماعة رأس لاسلكية');
    $a4Response->assertSee('PRD-002');
    $a4Response->assertSee('20 قطعة / عبوة');
    $a4Response->assertSee('320.00');

    // Test A5 print
    $a5Response = $this->get(route('products.price-list.print', ['format' => 'a5']));
    $a5Response->assertOk();
    $a5Response->assertSee('data-format="a5"', false);
});
