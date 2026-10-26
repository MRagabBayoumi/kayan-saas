<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesRepAttendance;
use App\Models\SalesRepVisit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 1. Roles & Permissions
    $portalPerm = Permission::firstOrCreate(
        ['slug' => 'access-sales-portal'],
        ['name' => 'الوصول لبوابة المندوب', 'module' => 'مندوبي المبيعات']
    );

    $manageRepsPerm = Permission::firstOrCreate(
        ['slug' => 'manage-sales-reps'],
        ['name' => 'إدارة مناديب المبيعات', 'module' => 'مندوبي المبيعات']
    );

    $adminRole = Role::firstOrCreate(
        ['slug' => 'admin'],
        ['name' => 'مدير النظام', 'is_system' => true]
    );
    $adminRole->permissions()->sync([$portalPerm->id, $manageRepsPerm->id]);

    $repRole = Role::firstOrCreate(
        ['slug' => 'sales_rep'],
        ['name' => 'مندوب مبيعات', 'is_system' => true]
    );
    $repRole->permissions()->sync([$portalPerm->id]);

    $this->adminUser = User::factory()->create([
        'name' => 'المدير العام',
        'email' => 'admin@kayan.com',
        'role' => User::ROLE_ADMIN,
        'role_id' => $adminRole->id,
        'is_active' => true,
    ]);

    $this->salesRep = User::factory()->create([
        'name' => 'أحمد المندوب',
        'email' => 'ahmed.rep@kayan.com',
        'role' => User::ROLE_SALES_REP,
        'role_id' => $repRole->id,
        'commission_rate' => 5.00,
        'commission_type' => 'percentage',
        'is_active' => true,
    ]);

    SalesRepAttendance::create([
        'sales_rep_id' => $this->salesRep->id,
        'date' => now()->format('Y-m-d'),
        'check_in_time' => now(),
        'check_in_latitude' => 30.7865000,
        'check_in_longitude' => 31.0004000,
        'status' => 'present',
    ]);

    $this->category = Category::create([
        'name' => 'إكسسوارات حريمي',
        'code' => 'ACC',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-MAIN',
        'name' => 'المخزن الرئيسي',
        'is_active' => true,
        'is_default' => true,
    ]);
});

test('sales rep registers customer with store images which defaults to pending approval and wholesale pricing', function () {
    Storage::fake('public');

    $fakeImage1 = UploadedFile::fake()->create('store_front.jpg', 100, 'image/jpeg');
    $fakeImage2 = UploadedFile::fake()->create('store_inside.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($this->salesRep)->post(route('portal.customers.store'), [
        'name' => 'محل الأمل للملابس',
        'company_name' => 'شركة الأمل للتجارة',
        'phone' => '01099887766',
        'phone_alt' => '01299887766',
        'city' => 'طنطا',
        'address' => 'شارع البحر، بجوار البنك',
        'latitude' => 30.7865000,
        'longitude' => 31.0004000,
        'notes' => 'المحل نشط ولديه حركة ممتازة',
        'store_images' => [$fakeImage1, $fakeImage2],
    ]);

    $response->assertRedirect(route('portal.customers.index'));
    $response->assertSessionHas('status', 'success');

    $customer = Customer::where('phone', '01099887766')->first();
    expect($customer)->not->toBeNull();
    expect($customer->approval_status)->toBe(Customer::APPROVAL_PENDING);
    expect($customer->pricing_tier)->toBe('wholesale');
    expect($customer->sales_rep_id)->toBe($this->salesRep->id);
    expect($customer->isPending())->toBeTrue();
    expect($customer->isApproved())->toBeFalse();

    expect($customer->store_images)->toBeArray();
    expect(count($customer->store_images))->toBe(2);
    foreach ($customer->store_images as $path) {
        Storage::disk('public')->assertExists($path);
    }
});

test('sales rep cannot create order for pending customer', function () {
    $pendingCustomer = Customer::create([
        'name' => 'عميل تحت الفحص',
        'phone' => '01011112222',
        'sales_rep_id' => $this->salesRep->id,
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'approval_status' => Customer::APPROVAL_PENDING,
        'is_active' => true,
    ]);

    $product = Product::create([
        'name' => 'شنطة حريمي',
        'category_id' => $this->category->id,
        'sku' => 'BAG-001',
        'wholesale_price' => 150.00,
        'retail_price' => 200.00,
        'distributor_price' => 130.00,
        'purchase_price' => 100.00,
        'is_active' => true,
    ]);

    // View order creation page - pending customer should not appear in options
    $viewResponse = $this->actingAs($this->salesRep)->get(route('portal.orders.create'));
    $viewResponse->assertOk();
    $viewResponse->assertDontSee('عميل تحت الفحص');

    // Attempt direct post order for pending customer
    $response = $this->actingAs($this->salesRep)->post(route('portal.orders.store'), [
        'customer_id' => $pendingCustomer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'wholesale',
        'payment_type' => 'cash',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 150.00,
            ],
        ],
    ]);

    $response->assertSessionHas('status', 'danger');
    $response->assertSessionHas('message', 'لا يمكن تحرير طلب بيع لعميل ما زال قيد المراجعة أو مرفوض. يجب اعتماد العميل أولاً من إدارة النظام.');
});

test('manager can review customer requests, approve them, and assign credit limit and pricing tier', function () {
    $customer = Customer::create([
        'name' => 'سوبر ماركت البركة',
        'phone' => '01122334455',
        'sales_rep_id' => $this->salesRep->id,
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'approval_status' => Customer::APPROVAL_PENDING,
        'is_active' => true,
    ]);

    // Manager views the pending requests
    $indexResponse = $this->actingAs($this->adminUser)->get(route('sales-reps.customer-requests.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee('سوبر ماركت البركة');

    // Manager approves customer with 10,000 credit limit and distributor pricing
    $approveResponse = $this->actingAs($this->adminUser)->post(route('sales-reps.customer-requests.approve', $customer), [
        'credit_limit' => 10000.00,
        'pricing_tier' => 'distributor',
    ]);

    $approveResponse->assertRedirect();
    $approveResponse->assertSessionHas('status', 'success');

    $customer->refresh();
    expect($customer->approval_status)->toBe(Customer::APPROVAL_APPROVED);
    expect($customer->isApproved())->toBeTrue();
    expect((float) $customer->credit_limit)->toBe(10000.00);
    expect($customer->pricing_tier)->toBe('distributor');
    expect($customer->approved_by)->toBe($this->adminUser->id);
    expect($customer->approved_at)->not->toBeNull();

    // Now sales rep can see customer in order create page
    $orderPage = $this->actingAs($this->salesRep)->get(route('portal.orders.create'));
    $orderPage->assertOk();
    $orderPage->assertSee('سوبر ماركت البركة');
});

test('manager can reject customer request with reason', function () {
    $customer = Customer::create([
        'name' => 'محل وهمي',
        'phone' => '01500000000',
        'sales_rep_id' => $this->salesRep->id,
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'approval_status' => Customer::APPROVAL_PENDING,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)->post(route('sales-reps.customer-requests.reject', $customer), [
        'rejection_reason' => 'الصور المرفقة غير واضحة والنشاط مغلق أثناء التحقق.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'warning');

    $customer->refresh();
    expect($customer->approval_status)->toBe(Customer::APPROVAL_REJECTED);
    expect($customer->isRejected())->toBeTrue();
    expect($customer->is_active)->toBeFalse();
    expect($customer->rejection_reason)->toBe('الصور المرفقة غير واضحة والنشاط مغلق أثناء التحقق.');
});

test('sales rep GPS attendance check-in and check-out calculates duration accurately', function () {
    // Reset any existing attendance for fresh checkin test
    SalesRepAttendance::where('sales_rep_id', $this->salesRep->id)->delete();

    // Check In
    $checkInResponse = $this->actingAs($this->salesRep)->post(route('portal.attendance.check-in'), [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'address' => 'ميدان التحرير، القاهرة',
        'notes' => 'بدء يوم العمل والتحرك نحو العملاء',
    ]);

    $checkInResponse->assertRedirect();
    $checkInResponse->assertSessionHas('status', 'success');

    $attendance = SalesRepAttendance::where('sales_rep_id', $this->salesRep->id)
        ->whereDate('date', now()->format('Y-m-d'))
        ->first();

    expect($attendance)->not->toBeNull();
    expect($attendance->status)->toBe('present');
    expect((float) $attendance->check_in_latitude)->toBe(30.0444);
    expect((float) $attendance->check_in_longitude)->toBe(31.2357);
    expect($attendance->check_out_time)->toBeNull();

    // Prevent duplicate check-in while present
    $dupResponse = $this->actingAs($this->salesRep)->post(route('portal.attendance.check-in'));
    $dupResponse->assertSessionHas('status', 'warning');

    // Simulate 3 hours elapsed
    $attendance->update([
        'check_in_time' => now()->subHours(3)->subMinutes(15),
    ]);

    // Check Out
    $checkOutResponse = $this->actingAs($this->salesRep)->post(route('portal.attendance.check-out'), [
        'latitude' => 30.0500,
        'longitude' => 31.2400,
        'address' => 'رمسيس، القاهرة',
    ]);

    $checkOutResponse->assertRedirect();
    $checkOutResponse->assertSessionHas('status', 'success');

    $attendance->refresh();
    expect($attendance->status)->toBe('checked_out');
    expect($attendance->check_out_time)->not->toBeNull();
    expect((float) $attendance->check_out_latitude)->toBe(30.0500);
    expect($attendance->work_duration_minutes)->toBeGreaterThanOrEqual(194);
    expect($attendance->formatted_work_duration)->toContain('س');

    // Manager can view attendance
    $adminView = $this->actingAs($this->adminUser)->get(route('sales-reps.attendance.index'));
    $adminView->assertOk();
    $adminView->assertSee($this->salesRep->name);
});

test('sales rep GPS field visits tracking, check-in, completion, and outcome redirection', function () {
    Storage::fake('public');

    $approvedCustomer = Customer::create([
        'name' => 'معرض العالمية',
        'phone' => '01055554444',
        'sales_rep_id' => $this->salesRep->id,
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'approval_status' => Customer::APPROVAL_APPROVED,
        'is_active' => true,
    ]);

    // Rep visits page
    $visitsIndex = $this->actingAs($this->salesRep)->get(route('portal.visits.index'));
    $visitsIndex->assertOk();

    // Start visit with GPS
    $startResponse = $this->actingAs($this->salesRep)->post(route('portal.visits.store'), [
        'customer_id' => $approvedCustomer->id,
        'purpose' => 'order',
        'latitude' => 30.1234567,
        'longitude' => 31.7654321,
        'notes' => 'زيارة لعرض الأصناف الجديدة وتحرير طلب',
    ]);

    $visit = SalesRepVisit::where('customer_id', $approvedCustomer->id)->first();
    expect($visit)->not->toBeNull();
    $startResponse->assertRedirect(route('portal.visits.show', $visit));

    expect($visit->sales_rep_id)->toBe($this->salesRep->id);
    expect($visit->purpose)->toBe('order');
    expect($visit->check_out_at)->toBeNull();

    // Simulate 45 minutes duration
    $visit->update([
        'check_in_at' => now()->subMinutes(45),
    ]);

    $visitPhoto = UploadedFile::fake()->create('visit_proof.jpg', 100, 'image/jpeg');

    // Complete visit with outcome: order_placed
    $completeResponse = $this->actingAs($this->salesRep)->post(route('portal.visits.complete', $visit), [
        'outcome' => SalesRepVisit::OUTCOME_ORDER_PLACED,
        'notes' => 'تم الاتفاق مع صاحب المحل على طلبيات بقيمة 5000 ج.م',
        'latitude' => 30.1235000,
        'longitude' => 31.7654000,
        'visit_photo' => $visitPhoto,
    ]);

    // Because outcome is order_placed, rep is redirected directly to order create
    $completeResponse->assertRedirect(route('portal.orders.create', ['customer_id' => $approvedCustomer->id]));

    $visit->refresh();
    expect($visit->outcome)->toBe(SalesRepVisit::OUTCOME_ORDER_PLACED);
    expect($visit->check_out_at)->not->toBeNull();
    expect($visit->duration_minutes)->toBeGreaterThanOrEqual(44);
    expect($visit->visit_image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($visit->visit_image_path);

    // Manager can view visits report
    $adminVisits = $this->actingAs($this->adminUser)->get(route('sales-reps.visits.index'));
    $adminVisits->assertOk();
    $adminVisits->assertSee('معرض العالمية');
    $adminVisits->assertSee($this->salesRep->name);
});
