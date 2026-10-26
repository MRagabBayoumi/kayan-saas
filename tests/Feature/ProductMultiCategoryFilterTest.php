<?php

use App\Models\Category;
use App\Models\Product;
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

    $this->cat1 = Category::create(['name' => 'تصنيف توك شعر', 'code' => 'CAT-01', 'slug' => 'cat-01', 'is_active' => true]);
    $this->cat2 = Category::create(['name' => 'تصنيف أطواق وبكلات', 'code' => 'CAT-02', 'slug' => 'cat-02', 'is_active' => true]);
    $this->cat3 = Category::create(['name' => 'تصنيف مكياج', 'code' => 'CAT-03', 'slug' => 'cat-03', 'is_active' => true]);

    $this->prod1 = Product::create([
        'category_id' => $this->cat1->id,
        'name' => 'بند من تصنيف 1',
        'sku' => 'SKU-001',
        'cost_price' => 10,
        'retail_price' => 20,
        'is_active' => true,
    ]);

    $this->prod2 = Product::create([
        'category_id' => $this->cat2->id,
        'name' => 'بند من تصنيف 2',
        'sku' => 'SKU-002',
        'cost_price' => 15,
        'retail_price' => 30,
        'is_active' => true,
    ]);

    $this->prod3 = Product::create([
        'category_id' => $this->cat3->id,
        'name' => 'بند من تصنيف 3',
        'sku' => 'SKU-003',
        'cost_price' => 25,
        'retail_price' => 50,
        'is_active' => true,
    ]);
});

test('products index filters by multiple category_ids array', function () {
    $response = $this->get(route('products.index', [
        'category_ids' => [$this->cat1->id, $this->cat2->id],
    ]));

    $response->assertOk();
    $response->assertSee('بند من تصنيف 1');
    $response->assertSee('بند من تصنيف 2');
    $response->assertDontSee('بند من تصنيف 3');
});

test('products index filters by comma-separated categories parameter', function () {
    $response = $this->get(route('products.index', [
        'categories' => "{$this->cat2->id},{$this->cat3->id}",
    ]));

    $response->assertOk();
    $response->assertDontSee('بند من تصنيف 1');
    $response->assertSee('بند من تصنيف 2');
    $response->assertSee('بند من تصنيف 3');
});

test('products index preserves backward compatibility with single category_id', function () {
    $response = $this->get(route('products.index', [
        'category_id' => $this->cat3->id,
    ]));

    $response->assertOk();
    $response->assertDontSee('بند من تصنيف 1');
    $response->assertDontSee('بند من تصنيف 2');
    $response->assertSee('بند من تصنيف 3');
});
