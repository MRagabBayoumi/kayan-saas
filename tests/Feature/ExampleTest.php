<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application returns a successful response with BayoSoft ERP layout', function () {
    $admin = User::firstOrCreate(
        ['email' => 'admin-test@kayan-erp.com'],
        [
            'name' => 'مدير النظام التجريبي',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]
    );

    $response = $this->actingAs($admin)->get('/');

    $response->assertStatus(200);
    $response->assertSee('BayoSoft ERP');
    $response->assertSee('dir="rtl"', false);
    $response->assertSee('data-theme="light"', false);
    $response->assertSee('data-density="comfortable"', false);
    $response->assertSee('appLauncherBackdrop');
    $response->assertSee('erpSidebar');
    $response->assertSee('sidebarToggleBtn');
    $response->assertSee('sidebarDesktopToggle');
    $response->assertSee('لوحة التحكم');
    $response->assertSee('Cairo');
});
