<?php

use App\Models\Category;
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

test('multiple products can share the exact same sku model code', function () {
    $product1 = Product::create([
        'category_id' => $this->category->id,
        'sku' => 'HB-84',
        'barcode' => '10001',
        'name' => 'اظافر صافي مورد أ',
        'cost_price' => 10.00,
        'retail_price' => 20.00,
        'wholesale_price' => 15.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    $response = $this->post(route('products.store'), [
        'category_id' => $this->category->id,
        'sku' => 'HB-84', // Same SKU
        'barcode' => '10002', // Different unique barcode
        'name' => 'اظافر صافي مورد ب',
        'cost_price' => 12.00,
        'retail_price' => 22.00,
        'wholesale_price' => 17.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('products', [
        'name' => 'اظافر صافي مورد ب',
        'sku' => 'HB-84',
        'barcode' => '10002',
    ]);
});

test('duplicate barcodes are rejected while barcode is automatically generated if omitted', function () {
    Product::create([
        'category_id' => $this->category->id,
        'sku' => 'MOD-1',
        'barcode' => '10005',
        'name' => 'منتج 1',
        'cost_price' => 10.00,
        'retail_price' => 20.00,
        'wholesale_price' => 15.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    // Attempt to store product with duplicate barcode
    $dupResponse = $this->post(route('products.store'), [
        'category_id' => $this->category->id,
        'sku' => 'MOD-2',
        'barcode' => '10005',
        'name' => 'منتج مكرر الباركود',
        'cost_price' => 10.00,
        'retail_price' => 20.00,
        'wholesale_price' => 15.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);
    $dupResponse->assertSessionHasErrors(['barcode']);

    // Store product without barcode -> should auto-generate sequential barcode
    $autoResponse = $this->post(route('products.store'), [
        'category_id' => $this->category->id,
        'sku' => 'MOD-3',
        'barcode' => '',
        'name' => 'منتج بتوليد تلقائي',
        'cost_price' => 10.00,
        'retail_price' => 20.00,
        'wholesale_price' => 15.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);
    $autoResponse->assertSessionHasNoErrors();

    $created = Product::where('name', 'منتج بتوليد تلقائي')->first();
    expect($created)->not->toBeNull();
    expect($created->barcode)->toBe('10006');
});

test('lookup barcode finds exact product by unique barcode and searches by sku', function () {
    $p1 = Product::create([
        'category_id' => $this->category->id,
        'sku' => 'X-19',
        'barcode' => '10010',
        'name' => 'منظم مكياج ومرايا',
        'cost_price' => 50.00,
        'retail_price' => 100.00,
        'wholesale_price' => 80.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    $p2 = Product::create([
        'category_id' => $this->category->id,
        'sku' => 'X-19',
        'barcode' => '10011',
        'name' => 'منظم مكياج إضاءة ليد',
        'cost_price' => 70.00,
        'retail_price' => 130.00,
        'wholesale_price' => 100.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    // Lookup by exact unique barcode
    $resBarcode = $this->getJson(route('products.lookup-barcode', ['barcode' => '10011']));
    $resBarcode->assertOk();
    $resBarcode->assertJson([
        'found' => true,
        'product' => [
            'id' => $p2->id,
            'barcode' => '10011',
            'name' => 'منظم مكياج إضاءة ليد',
        ],
    ]);

    // Lookup by SKU finds matching product
    $resSku = $this->getJson(route('products.lookup-barcode', ['barcode' => 'X-19']));
    $resSku->assertOk();
    $resSku->assertJson([
        'found' => true,
    ]);
});
