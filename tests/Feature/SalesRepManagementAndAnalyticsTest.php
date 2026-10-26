<?php

use App\Models\Customer;
use App\Models\Role;
use App\Models\SalesCommission;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::firstOrCreate(
        ['slug' => User::ROLE_ADMIN],
        ['name' => 'مدير النظام', 'is_system' => true]
    );

    $this->salesRepRole = Role::firstOrCreate(
        ['slug' => User::ROLE_SALES_REP],
        ['name' => 'مندوب مبيعات', 'is_system' => true]
    );

    $this->admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::first() ?? Warehouse::create([
        'name' => 'المستودع الرئيسي',
        'code' => 'WH-MAIN',
        'is_active' => true,
    ]);

    $this->salesRep = User::create([
        'name' => 'مندوب تجريبي',
        'username' => 'rep_test_'.uniqid(),
        'email' => 'rep_test_'.uniqid().'@example.com',
        'phone' => '01099998888',
        'password' => bcrypt('password123'),
        'role_id' => $this->salesRepRole->id,
        'role' => User::ROLE_SALES_REP,
        'commission_rate' => 5.00,
        'commission_type' => 'percentage',
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);
});

test('admin can access sales reps management page and view KPIs', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('sales-reps.index'));

    $response->assertOk()
        ->assertSee('إدارة وتحليل أداء مناديب المبيعات')
        ->assertSee('مندوب تجريبي')
        ->assertSee('@'.$this->salesRep->username)
        ->assertDontSee('{{ $rep->username }}');
});

test('admin can access sales reps create page', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('sales-reps.create'));

    $response->assertOk()
        ->assertSee('إضافة مندوب مبيعات جديد')
        ->assertSee('اسم المستخدم (لتسجيل الدخول)');
});

test('admin can create a new sales representative with username and password without email', function () {
    $this->actingAs($this->admin);

    $username = 'rep_user_'.uniqid();

    $response = $this->post(route('sales-reps.store'), [
        'name' => 'مندوب جديد معتمد',
        'username' => $username,
        'password' => 'secret123',
        'phone' => '01122334455',
        'commission_rate' => 7.5,
        'commission_type' => 'percentage',
        'warehouse_id' => $this->warehouse->id,
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('sales-reps.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'name' => 'مندوب جديد معتمد',
        'username' => $username,
        'email' => null,
        'role' => User::ROLE_SALES_REP,
        'commission_rate' => 7.50,
        'commission_type' => 'percentage',
    ]);

    // Test that the newly created rep can log in using username and password
    auth()->logout();

    $this->post(route('login'), [
        'login' => $username,
        'password' => 'secret123',
    ])->assertRedirect(route('portal.index'));
});

test('admin can access sales rep deep performance analytics page', function () {
    $this->actingAs($this->admin);

    // Create a customer for this rep
    $customer = Customer::create([
        'name' => 'عميل للمندوب',
        'phone' => '01011112222',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'sales_rep_id' => $this->salesRep->id,
        'is_active' => true,
    ]);

    // Create an invoice
    $invoice = SalesInvoice::create([
        'invoice_number' => 'INV-REP-'.uniqid(),
        'customer_id' => $customer->id,
        'user_id' => $this->admin->id,
        'sales_rep_id' => $this->salesRep->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'total_amount' => 1000.00,
        'paid_amount' => 1000.00,
        'remaining_amount' => 0.00,
        'payment_type' => 'cash',
        'status' => SalesInvoice::STATUS_POSTED,
    ]);

    // Create commission
    $commission = SalesCommission::create([
        'sales_rep_id' => $this->salesRep->id,
        'sales_invoice_id' => $invoice->id,
        'customer_id' => $customer->id,
        'invoice_total' => 1000.00,
        'commission_rate' => 5.00,
        'commission_amount' => 50.00,
        'status' => SalesCommission::STATUS_PENDING,
    ]);

    $response = $this->get(route('sales-reps.show', $this->salesRep));

    $response->assertOk()
        ->assertSee('تحليل أداء المندوب')
        ->assertSee('مندوب تجريبي')
        ->assertSee('عميل للمندوب')
        ->assertSee(number_format(1000, 2))
        ->assertSee(number_format(50, 2));

    // Admin can pay commission
    $payResponse = $this->post(route('sales-reps.commissions.pay', $commission));
    $payResponse->assertRedirect();
    $payResponse->assertSessionHas('success');

    expect($commission->fresh()->status)->toBe(SalesCommission::STATUS_PAID);
});

test('admin can access customer creation page and store customer without 500 error', function () {
    $this->actingAs($this->admin);

    $this->get(route('customers.create'))->assertOk();

    $response = $this->post(route('customers.store'), [
        'name' => 'محل الأمل للتجارة',
        'phone' => '01055554444',
        'customer_type' => 'retail',
        'pricing_tier' => 'retail',
        'sales_rep_id' => $this->salesRep->id,
        'city' => 'القاهرة',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('customers', [
        'name' => 'محل الأمل للتجارة',
        'phone' => '01055554444',
        'sales_rep_id' => $this->salesRep->id,
    ]);
});
