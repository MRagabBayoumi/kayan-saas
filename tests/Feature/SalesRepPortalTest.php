<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesCommission;
use App\Models\SalesInvoice;
use App\Models\SalesRepAttendance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed role & permission
    $portalPerm = Permission::firstOrCreate(
        ['slug' => 'access-sales-portal'],
        ['name' => 'الوصول لبوابة المندوب', 'module' => 'مندوبي المبيعات']
    );

    $this->salesRepRole = Role::firstOrCreate(
        ['slug' => 'sales_rep'],
        [
            'name' => 'مندوب مبيعات خارجي / بياع',
            'description' => 'الوصول لبوابة المندوب من الموبايل',
            'is_system' => true,
        ]
    );
    $this->salesRepRole->permissions()->sync([$portalPerm->id]);

    $this->salesRep = User::factory()->create([
        'name' => 'محمد مندوب المبيعات',
        'email' => 'rep@kayan.com',
        'password' => 'secret123',
        'role' => User::ROLE_SALES_REP,
        'role_id' => $this->salesRepRole->id,
        'commission_rate' => 10.00, // 10%
        'commission_type' => 'percentage',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-PORTAL',
        'name' => 'مستودع المعرض الرئيسي',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->category = Category::create([
        'name' => 'إكسسوارات حريمي',
        'code' => 'ACC',
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
        'sku' => 'HAIR-CLIP-01',
        'barcode' => '6228899001122',
        'name' => 'توكة شعر لؤلؤ فاخرة',
        'cost_price' => 20.00,
        'retail_price' => 50.00,
        'wholesale_price' => 40.00,
        'distributor_price' => 35.00,
        'unit' => 'قطعة',
        'unit_factor' => 1.0,
        'pieces_per_package' => 1,
        'is_active' => true,
    ]);

    // Initial stock
    app(InventoryService::class)->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 100.0,
        costPrice: 20.00,
        referenceType: 'opening_balance'
    );
});

test('unauthenticated users are redirected to login when accessing portal', function () {
    auth()->logout();
    $response = $this->get(route('portal.index'));
    $response->assertRedirect(route('login'));
});

test('users without access-sales-portal permission are forbidden', function () {
    $regularUser = User::factory()->create([
        'name' => 'مستخدم عادي',
        'role' => 'user',
        'is_active' => true,
    ]);

    $response = $this->actingAs($regularUser)->get(route('portal.index'));
    $response->assertForbidden();
});

test('sales rep can view mobile dashboard with accurate stats', function () {
    $response = $this->actingAs($this->salesRep)->get(route('portal.index'));

    $response->assertOk();
    $response->assertSee('بوابة المندوب الميداني');
    $response->assertSee('محمد مندوب المبيعات');
    $response->assertSee('نسبة العمولة: 10%');
});

test('sales rep can browse mobile catalog with wholesale pricing and stock privacy', function () {
    $response = $this->actingAs($this->salesRep)->get(route('portal.catalog'));

    $response->assertOk();
    $response->assertSee('توكة شعر لؤلؤ فاخرة');
    $response->assertSee('40.00'); // Wholesale price
    $response->assertSee('سعر الجملة');
    $response->assertSee('متوفر للطلب');

    // Crucial privacy: sales rep should NOT see retail, distributor, or raw stock numbers
    $response->assertDontSee('50.00'); // Retail
    $response->assertDontSee('35.00'); // Distributor
    $response->assertDontSee('متاح 100'); // Raw stock count
});

test('admin can see exact stock quantity in catalog while rep cannot', function () {
    $admin = User::factory()->create([
        'name' => 'مدير النظام',
        'role' => User::ROLE_ADMIN,
    ]);

    $adminResponse = $this->actingAs($admin)->get(route('portal.catalog'));
    $adminResponse->assertOk();
    $adminResponse->assertSee('100 قطعة'); // Admin sees exact count

    $repResponse = $this->actingAs($this->salesRep)->get(route('portal.catalog'));
    $repResponse->assertOk();
    $repResponse->assertDontSee('100 قطعة');
    $repResponse->assertSee('متوفر للطلب');
});

test('product is marked out of stock for rep when below min stock threshold', function () {
    $productWithMinStock = Product::create([
        'category_id' => $this->category->id,
        'sku' => 'CLIP-MIN-50',
        'barcode' => '6228899009999',
        'name' => 'صنف بحد أدنى مرتفع',
        'cost_price' => 20.00,
        'retail_price' => 60.00,
        'wholesale_price' => 45.00,
        'distributor_price' => 40.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 50, // Min order/stock required is 50
        'is_active' => true,
    ]);

    // Add only 30 in stock (less than min_stock_alert 50)
    app(InventoryService::class)->addStock(
        productId: $productWithMinStock->id,
        warehouseId: $this->warehouse->id,
        quantity: 30.0,
        costPrice: 20.00,
        referenceType: 'opening_balance'
    );

    $response = $this->actingAs($this->salesRep)->get(route('portal.catalog', ['search' => 'CLIP-MIN-50']));
    $response->assertOk();
    $response->assertSee('غير متوفر حالياً');
    $response->assertDontSee('متوفر للطلب');
});

test('sales rep cannot create customer or order without an active GPS attendance session', function () {
    // 1. Trying to view customer creation form
    $respCustomerForm = $this->actingAs($this->salesRep)->get(route('portal.customers.create'));
    $respCustomerForm->assertRedirect(route('portal.attendance.index'));
    $respCustomerForm->assertSessionHas('status', 'warning');

    // 2. Trying to store customer
    $respCustomerStore = $this->actingAs($this->salesRep)->post(route('portal.customers.store'), [
        'name' => 'عميل بدون حضور',
        'phone' => '01011112222',
    ]);
    $respCustomerStore->assertRedirect(route('portal.attendance.index'));
    $respCustomerStore->assertSessionHas('status', 'warning');

    // 3. Trying to view order creation form
    $respOrderForm = $this->actingAs($this->salesRep)->get(route('portal.orders.create'));
    $respOrderForm->assertRedirect(route('portal.attendance.index'));
    $respOrderForm->assertSessionHas('status', 'warning');

    // 4. Trying to store order
    $respOrderStore = $this->actingAs($this->salesRep)->post(route('portal.orders.store'), [
        'customer_id' => 1,
    ]);
    $respOrderStore->assertRedirect(route('portal.attendance.index'));
    $respOrderStore->assertSessionHas('status', 'warning');
});

test('sales rep must provide GPS coordinates to check in', function () {
    // Missing coordinates fails validation
    $response = $this->actingAs($this->salesRep)->post(route('portal.attendance.check-in'), []);
    $response->assertSessionHasErrors(['latitude', 'longitude']);

    // Valid coordinates succeeds and creates attendance session
    $validResponse = $this->actingAs($this->salesRep)->post(route('portal.attendance.check-in'), [
        'latitude' => 30.7865000,
        'longitude' => 31.0004000,
        'address' => 'ميدان المحطة، طنطا',
    ]);
    $validResponse->assertRedirect();
    $validResponse->assertSessionHas('status', 'success');

    $this->assertDatabaseHas('sales_rep_attendances', [
        'sales_rep_id' => $this->salesRep->id,
        'status' => 'present',
        'check_in_latitude' => 30.7865000,
        'check_in_longitude' => 31.0004000,
    ]);

    expect($this->salesRep->fresh()->hasActiveAttendanceSession())->toBeTrue();
});

test('sales rep can register new field customer with GPS location after active checkin', function () {
    SalesRepAttendance::create([
        'sales_rep_id' => $this->salesRep->id,
        'date' => now()->format('Y-m-d'),
        'check_in_time' => now(),
        'check_in_latitude' => 30.7865,
        'check_in_longitude' => 31.0004,
        'status' => 'present',
    ]);

    $response = $this->actingAs($this->salesRep)->post(route('portal.customers.store'), [
        'name' => 'سنتر الأمل للإكسسوارات',
        'company_name' => 'شركة الأمل للتجارة',
        'phone' => '01099887766',
        'phone_alt' => '01122334455',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'city' => 'طنطا',
        'address' => 'شارع البحر، بجوار المحطة',
        'latitude' => 30.7865,
        'longitude' => 31.0004,
        'credit_limit' => 15000,
        'notes' => 'عميل جملة ممتاز',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'name' => 'سنتر الأمل للإكسسوارات',
        'phone' => '01099887766',
        'sales_rep_id' => $this->salesRep->id,
        'pricing_tier' => 'wholesale',
        'latitude' => 30.7865,
        'longitude' => 31.0004,
    ]);
});

test('sales rep can submit a field order as draft', function () {
    SalesRepAttendance::create([
        'sales_rep_id' => $this->salesRep->id,
        'date' => now()->format('Y-m-d'),
        'check_in_time' => now(),
        'check_in_latitude' => 30.7865,
        'check_in_longitude' => 31.0004,
        'status' => 'present',
    ]);
    $customer = Customer::create([
        'name' => 'معرض السعادة',
        'phone' => '01000000001',
        'pricing_tier' => 'wholesale',
        'sales_rep_id' => $this->salesRep->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->salesRep)->post(route('portal.orders.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'wholesale',
        'payment_type' => 'credit',
        'action' => 'draft',
        'notes' => 'طلب مسودة للتوصيل غداً',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'unit_price' => 40.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_DRAFT);
    expect((float) $invoice->total_amount)->toBe(400.00);
    expect((int) $invoice->sales_rep_id)->toBe($this->salesRep->id);

    $response->assertRedirect(route('portal.orders.show', $invoice));

    // View order on mobile
    $showResponse = $this->actingAs($this->salesRep)->get(route('portal.orders.show', $invoice));
    $showResponse->assertOk();
    $showResponse->assertSee($invoice->invoice_number);
    $showResponse->assertSee('مسودة طلب معلقة');
});

test('sales rep can submit and post order directly, generating commission', function () {
    SalesRepAttendance::create([
        'sales_rep_id' => $this->salesRep->id,
        'date' => now()->format('Y-m-d'),
        'check_in_time' => now(),
        'check_in_latitude' => 30.7865,
        'check_in_longitude' => 31.0004,
        'status' => 'present',
    ]);

    $customer = Customer::create([
        'name' => 'محل ريتاج',
        'phone' => '01000000002',
        'pricing_tier' => 'retail',
        'sales_rep_id' => $this->salesRep->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->salesRep)->post(route('portal.orders.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'payment_type' => 'cash',
        'action' => 'post',
        'notes' => 'طلب نقدي تم تسليمه مباشرة',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'unit_price' => 50.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED);
    expect((float) $invoice->total_amount)->toBe(250.00);

    // 10% commission on 250.00 = 25.00 EGP
    $commission = SalesCommission::where('sales_invoice_id', $invoice->id)->first();
    expect($commission)->not->toBeNull();
    expect((float) $commission->commission_amount)->toBe(25.00);
    expect($commission->sales_rep_id)->toBe($this->salesRep->id);

    // Rep Commissions statement view
    $commResponse = $this->actingAs($this->salesRep)->get(route('portal.commissions'));
    $commResponse->assertOk();
    $commResponse->assertSee('25.00');
    $commResponse->assertSee($invoice->invoice_number);
});

test('sales rep is strictly redirected to portal upon login even if url.intended was set', function () {
    auth()->logout();
    session(['url.intended' => route('dashboard')]);

    $response = $this->post(route('login.post'), [
        'login' => $this->salesRep->email,
        'password' => 'secret123',
    ]);

    $response->assertRedirect(route('portal.index'));
    expect(session()->has('url.intended'))->toBeFalse();
});

test('sales rep accessing non-portal routes or home is strictly redirected to portal', function (string $route) {
    $response = $this->actingAs($this->salesRep)->get($route);

    $response->assertRedirect(route('portal.index'));
})->with([
    '/',
    '/dashboard',
    '/products',
    '/settings',
    '/customers',
]);

test('sales rep accessing login page while authenticated is redirected to portal', function () {
    $response = $this->actingAs($this->salesRep)->get(route('login'));

    $response->assertRedirect(route('portal.index'));
});
