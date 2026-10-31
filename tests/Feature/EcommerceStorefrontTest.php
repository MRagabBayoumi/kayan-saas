<?php

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CompanySetting::firstOrCreate([], [
        'company_name' => 'كيان للاستيراد والتصدير',
        'is_active' => true,
    ]);
});

test('public storefront catalog loads successfully', function () {
    $response = $this->get(route('shop.catalog'));
    $response->assertOk();
});

test('products available online appear in storefront and inactive ones are hidden', function () {
    $category = Category::create([
        'name' => 'أدوات منزلية',
        'code' => 'CAT-001',
    ]);

    $onlineProduct = Product::create([
        'sku' => 'SKU-ON-01',
        'name' => 'طقم أواني فاخر',
        'category_id' => $category->id,
        'cost_price' => 100,
        'wholesale_price' => 150,
        'retail_price' => 200,
        'unit' => 'طقم',
        'min_stock_alert' => 5,
        'is_active' => true,
        'is_available_online' => true,
        'online_price' => 190,
    ]);

    $hiddenProduct = Product::create([
        'sku' => 'SKU-HD-01',
        'name' => 'صنف مخفي أوفلاين',
        'category_id' => $category->id,
        'cost_price' => 50,
        'wholesale_price' => 70,
        'retail_price' => 90,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'is_active' => true,
        'is_available_online' => false,
    ]);

    $response = $this->get(route('shop.catalog'));
    $response->assertOk();
    $response->assertSee('طقم أواني فاخر');
    $response->assertDontSee('صنف مخفي أوفلاين');
});

test('shopping cart page is accessible', function () {
    $response = $this->get(route('shop.cart'));
    $response->assertOk();
});

test('back-office ecommerce orders require authentication', function () {
    auth()->logout();
    $response = $this->get(route('ecommerce.orders.index'));
    $response->assertRedirect(route('login'));
});

test('admin can access ecommerce orders and settings', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('ecommerce.orders.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('ecommerce.settings'))
        ->assertOk();
});

test('admin can update ecommerce settings', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->post(route('ecommerce.settings.update'), [
        'store_name' => 'متجر كيان ستور',
        'store_tagline' => 'تسوق أفضل المنتجات الآن',
        'shipping_flat_rate' => 40.00,
        'free_shipping_threshold' => 600.00,
        'is_store_open' => '1',
        'allow_cod' => '1',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('ecommerce_settings', [
        'store_name' => 'متجر كيان ستور',
        'shipping_flat_rate' => 40.00,
    ]);
});

test('admin can batch toggle products online visibility', function () {
    $category = Category::create([
        'name' => 'إكسسوارات',
        'code' => 'CAT-ACC',
    ]);

    $p1 = Product::create([
        'sku' => 'SKU-B1',
        'name' => 'منتج مجمع 1',
        'category_id' => $category->id,
        'cost_price' => 10,
        'wholesale_price' => 15,
        'retail_price' => 20,
        'unit' => 'قطعة',
        'min_stock_alert' => 1,
        'is_active' => true,
        'is_available_online' => false,
    ]);

    $p2 = Product::create([
        'sku' => 'SKU-B2',
        'name' => 'منتج مجمع 2',
        'category_id' => $category->id,
        'cost_price' => 10,
        'wholesale_price' => 15,
        'retail_price' => 20,
        'unit' => 'قطعة',
        'min_stock_alert' => 1,
        'is_active' => true,
        'is_available_online' => false,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    // Batch show online
    $response = $this->actingAs($admin)->postJson(route('products.batch-toggle-online'), [
        'product_ids' => [$p1->id, $p2->id],
        'is_available_online' => true,
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'count' => 2,
        'is_available_online' => true,
    ]);

    expect($p1->fresh()->is_available_online)->toBeTrue();
    expect($p2->fresh()->is_available_online)->toBeTrue();

    // Batch hide online
    $response = $this->actingAs($admin)->postJson(route('products.batch-toggle-online'), [
        'product_ids' => [$p1->id],
        'is_available_online' => false,
    ]);

    $response->assertOk();
    expect($p1->fresh()->is_available_online)->toBeFalse();
    expect($p2->fresh()->is_available_online)->toBeTrue();
});
