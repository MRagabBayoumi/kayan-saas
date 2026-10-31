<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated guest is blocked from central saas control plane and redirected to login', function () {
    auth()->logout();

    // When an unauthenticated visitor tries to access /saas
    $response = $this->get('/saas');

    // Must be redirected to login
    $response->assertRedirect(route('login'));
});

test('non-admin user is forbidden from accessing saas control plane', function () {
    $cashier = User::factory()->create([
        'role' => User::ROLE_CASHIER,
        'is_active' => true,
    ]);

    $response = $this->actingAs($cashier)->get('/saas');

    // Forbidden
    $response->assertForbidden();
});

test('system administrator is authorized to access central saas control plane', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get('/saas');

    $response->assertOk();
    $response->assertSee('منصة إدارة المشتركين السحابية');
});
