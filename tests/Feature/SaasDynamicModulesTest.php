<?php

use App\Models\SaasModule;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('saas super admin can view modules catalog and pricing dashboard', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('saas.modules.index'));

    $response->assertOk();
    $response->assertSee('كتالوج الموديولات والتطبيقات');
    $response->assertSee('نقطة البيع السريعة');
});

test('saas super admin can create a new custom priced module', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $uniqueCode = 'loyalty_'.uniqid();

    $response = $this->actingAs($admin)->post(route('saas.modules.store'), [
        'code' => $uniqueCode,
        'name' => 'نظام نقاط الولاء والمكافآت',
        'description' => 'منح العملاء نقاطاً عند كل عملية شراء واستبدالها بخصومات حصرية.',
        'features' => "تجميع النقاط آلياً\nكوبونات خصم مخصصة\nإرسال رسائل تهنئة",
        'price_monthly' => 120.00,
        'price_yearly' => 1200.00,
        'icon' => 'store',
        'color' => '#f59e0b',
        'is_core' => 0,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('saas.modules.index'));

    $created = SaasModule::where('code', $uniqueCode)->first();
    expect($created)->not->toBeNull();
    expect($created->name)->toBe('نظام نقاط الولاء والمكافآت');
    expect((float) $created->price_monthly)->toBe(120.00);
    expect((float) $created->price_yearly)->toBe(1200.00);
    expect($created->features)->toHaveCount(3);
});

test('saas super admin can update module pricing and details', function () {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $module = SaasModule::firstOrCreate(
        ['code' => 'test_custom_'.uniqid()],
        [
            'name' => 'موديول للاختبار',
            'price_monthly' => 50,
            'price_yearly' => 500,
            'is_active' => true,
        ]
    );

    $response = $this->actingAs($admin)->put(route('saas.modules.update', $module), [
        'name' => 'موديول تم تحديثه',
        'description' => 'وصف محدث',
        'price_monthly' => 75.00,
        'price_yearly' => 750.00,
        'icon' => 'box',
        'color' => '#10b981',
        'is_active' => 1,
    ]);

    $response->assertRedirect();

    $module->refresh();
    expect($module->name)->toBe('موديول تم تحديثه');
    expect((float) $module->price_monthly)->toBe(75.00);
});

test('non-admin user is forbidden from accessing or modifying saas modules', function () {
    $cashier = User::factory()->create([
        'role' => User::ROLE_CASHIER,
        'is_active' => true,
    ]);

    $response = $this->actingAs($cashier)->get(route('saas.modules.index'));
    $response->assertForbidden();
});

test('tenant attempting to access disabled module receives dynamic upgrade prompt with custom name and price', function () {
    $subdomain = 'client'.uniqid();
    $tenant = Tenant::create([
        'name' => 'مؤسسة السلام',
        'subdomain' => $subdomain,
        'database_name' => 'kayan_tenant_'.$subdomain,
        'is_active' => true,
        'modules' => [
            'ecommerce' => false,
        ],
    ]);

    auth()->logout();

    // Custom module pricing in DB
    $ecomModule = SaasModule::where('code', 'ecommerce')->first();
    if ($ecomModule) {
        $ecomModule->update([
            'name' => 'المتجر الإلكتروني المتقدم',
            'price_monthly' => 320.00,
        ]);
    }

    $response = $this->get("http://{$subdomain}.lvh.me/shop");

    // The middleware blocks with 403 and renders module-disabled with the dynamic name & price
    $response->assertStatus(403);
    $response->assertSee('المتجر الإلكتروني المتقدم');
    $response->assertSee('320 ج.م');

    $tenant->delete();
});
