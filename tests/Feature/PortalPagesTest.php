<?php

use App\Models\Role;
use App\Models\SalesRepAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'مدير النظام',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->salesRepRole = Role::firstOrCreate(
        ['slug' => 'sales_rep'],
        [
            'name' => 'مندوب مبيعات خارجي / بياع',
            'description' => 'الوصول لبوابة المندوب من الموبايل',
            'is_system' => true,
        ]
    );

    $this->salesRep = User::factory()->create([
        'name' => 'أحمد المندوب',
        'email' => 'rep@kayan.com',
        'role' => User::ROLE_SALES_REP,
        'role_id' => $this->salesRepRole->id,
        'commission_rate' => 5.00,
        'commission_type' => 'percentage',
        'is_active' => true,
    ]);
});

test('admin can access all portal pages without 500 errors', function () {
    $this->actingAs($this->admin);

    $this->get(route('portal.index'))->assertOk();
    $this->get(route('portal.catalog'))->assertOk();
    $this->get(route('portal.customers.index'))->assertOk();
    $this->get(route('portal.customers.create'))->assertOk();
    $this->get(route('portal.orders.index'))->assertOk();
    $this->get(route('portal.orders.create'))->assertOk();
    $this->get(route('portal.commissions'))->assertOk();
});

test('sales rep without active attendance session is redirected from creation routes to attendance', function () {
    $this->actingAs($this->salesRep);

    $this->get(route('portal.index'))->assertOk();
    $this->get(route('portal.catalog'))->assertOk();
    $this->get(route('portal.attendance.index'))->assertOk();
    $this->get(route('portal.customers.index'))->assertOk();
    $this->get(route('portal.orders.index'))->assertOk();
    $this->get(route('portal.commissions'))->assertOk();

    // Gated routes require attendance session
    $this->get(route('portal.customers.create'))->assertRedirect(route('portal.attendance.index'));
    $this->get(route('portal.orders.create'))->assertRedirect(route('portal.attendance.index'));
});

test('sales rep with active GPS attendance session can access all portal pages without 500 errors', function () {
    SalesRepAttendance::create([
        'sales_rep_id' => $this->salesRep->id,
        'date' => now()->format('Y-m-d'),
        'check_in_time' => now(),
        'check_in_latitude' => 30.0444,
        'check_in_longitude' => 31.2357,
        'status' => 'present',
    ]);

    $this->actingAs($this->salesRep);

    $this->get(route('portal.index'))->assertOk();
    $this->get(route('portal.catalog'))->assertOk();
    $this->get(route('portal.attendance.index'))->assertOk();
    $this->get(route('portal.customers.index'))->assertOk();
    $this->get(route('portal.customers.create'))->assertOk();
    $this->get(route('portal.orders.index'))->assertOk();
    $this->get(route('portal.orders.create'))->assertOk();
    $this->get(route('portal.commissions'))->assertOk();
});
