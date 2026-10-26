<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'مدير النظام',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    $this->category = Category::create([
        'name' => 'إكسسوارات',
        'code' => 'CAT-01',
        'slug' => 'accessories',
        'is_active' => true,
    ]);

    Unit::create([
        'name' => 'قطعة',
        'code' => 'PCS',
        'conversion_factor' => 1.0,
        'is_active' => true,
    ]);

    Unit::create([
        'name' => 'دستة',
        'code' => 'DZ',
        'conversion_factor' => 12.0,
        'is_active' => true,
    ]);

    $this->productPiece = Product::create([
        'category_id' => $this->category->id,
        'name' => 'طوق شعر لؤلؤ',
        'sku' => 'SKU-PIECE-1',
        'unit' => 'قطعة',
        'cost_price' => 10,
        'retail_price' => 20,
        'is_active' => true,
    ]);

    $this->productDozen = Product::create([
        'category_id' => $this->category->id,
        'name' => 'دبوس شعر كريستال',
        'sku' => 'SKU-DOZEN-1',
        'unit' => 'دستة',
        'cost_price' => 60,
        'retail_price' => 120,
        'is_active' => true,
    ]);
});

test('products index page displays unit filter options and filters products by unit', function () {
    // 1. Visit products page without filter - both should be visible
    $response = $this->get(route('products.index'));
    $response->assertOk();
    $response->assertSee('طوق شعر لؤلؤ');
    $response->assertSee('دبوس شعر كريستال');
    $response->assertSee('كل وحدات القياس');
    $response->assertSee('قطعة');
    $response->assertSee('دستة');

    // 2. Filter by "قطعة"
    $responsePiece = $this->get(route('products.index', ['unit' => 'قطعة']));
    $responsePiece->assertOk();
    $responsePiece->assertSee('طوق شعر لؤلؤ');
    $responsePiece->assertDontSee('دبوس شعر كريستال');
    $responsePiece->assertSee('الوحدة: قطعة');

    // 3. Filter by "دستة"
    $responseDozen = $this->get(route('products.index', ['unit' => 'دستة']));
    $responseDozen->assertOk();
    $responseDozen->assertDontSee('طوق شعر لؤلؤ');
    $responseDozen->assertSee('دبوس شعر كريستال');
    $responseDozen->assertSee('الوحدة: دستة');
});
