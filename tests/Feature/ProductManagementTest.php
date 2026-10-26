<?php

use App\Models\BarcodeSetting;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('category can be created with valid data and hierarchy', function () {
    $parent = Category::create([
        'name' => 'إكسسوارات شعر',
        'slug' => 'hair-accessories',
        'description' => 'كافة مستلزمات وتوك الشعر الحريمي والأطفال',
    ]);

    $response = $this->post(route('categories.store'), [
        'name' => 'توك وبكلات لؤلؤ',
        'parent_id' => $parent->id,
        'description' => 'بكلات لؤلؤ مستوردة من الصين',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('status', 'success');

    $this->assertDatabaseHas('categories', [
        'name' => 'توك وبكلات لؤلؤ',
        'parent_id' => $parent->id,
    ]);

    $child = Category::where('name', 'توك وبكلات لؤلؤ')->first();
    expect($child->parent->id)->toBe($parent->id)
        ->and($parent->children)->toHaveCount(1);
});

test('category creation fails when required name is missing', function () {
    $response = $this->from(route('categories.index'))->post(route('categories.store'), [
        'name' => '',
    ]);

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHasErrors(['name']);
});

test('category deletion is restricted when products reference it', function () {
    $category = Category::create([
        'name' => 'أطواق وسلاسل',
        'slug' => 'necklaces',
    ]);

    Product::create([
        'sku' => 'KY-NCK-001',
        'name' => 'سلسلة لؤلؤ مطلية ذهب',
        'category_id' => $category->id,
        'cost_price' => 25.00,
        'wholesale_price' => 45.00,
        'retail_price' => 70.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 10,
    ]);

    $response = $this->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('status', 'error');

    // Category must not be deleted
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
    $this->assertDatabaseHas('products', ['sku' => 'KY-NCK-001']);
});

test('product can be created with full details and valid prices', function () {
    Storage::fake('public');

    $category = Category::create([
        'name' => 'توك شعر',
        'slug' => 'hair-clips',
    ]);

    $response = $this->post(route('products.store'), [
        'sku' => 'KY-26-0001',
        'name' => 'توكة شعر لؤلؤ كوري أبيض',
        'category_id' => $category->id,
        'barcode' => '6921234567890',
        'cost_price' => 15.50,
        'wholesale_price' => 28.00,
        'retail_price' => 45.00,
        'unit' => 'دستة',
        'min_stock_alert' => 10,
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('status', 'success');

    $this->assertDatabaseHas('products', [
        'sku' => 'KY-26-0001',
        'name' => 'توكة شعر لؤلؤ كوري أبيض',
        'barcode' => '6921234567890',
        'cost_price' => 15.50,
        'wholesale_price' => 28.00,
        'retail_price' => 45.00,
        'unit' => 'دستة',
        'min_stock_alert' => 10,
        'is_active' => 1,
    ]);
});

test('product validation rejects missing required fields and negative prices', function () {
    $category = Category::create(['name' => 'تصنيف عام', 'slug' => 'general']);

    // 1. Missing required fields
    $response1 = $this->post(route('products.store'), [
        'sku' => '',
        'name' => '',
        'category_id' => '',
        'cost_price' => '',
        'wholesale_price' => '',
        'retail_price' => '',
        'unit' => '',
    ]);

    $response1->assertSessionHasErrors(['sku', 'name', 'category_id', 'cost_price', 'wholesale_price', 'retail_price', 'unit']);

    // 2. Negative numbers
    $response2 = $this->post(route('products.store'), [
        'sku' => 'KY-TEST-NEG',
        'name' => 'منتج تجريبي',
        'category_id' => $category->id,
        'cost_price' => -10,
        'wholesale_price' => -5,
        'retail_price' => -2,
        'unit' => 'قطعة',
        'min_stock_alert' => -1,
    ]);

    $response2->assertSessionHasErrors(['cost_price', 'wholesale_price', 'retail_price', 'min_stock_alert']);
});

test('duplicate SKU is strictly rejected', function () {
    $category = Category::create(['name' => 'تصنيف عام', 'slug' => 'general']);

    Product::create([
        'sku' => 'KY-DUP-01',
        'name' => 'منتج أول',
        'category_id' => $category->id,
        'cost_price' => 10,
        'wholesale_price' => 20,
        'retail_price' => 30,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    $response = $this->post(route('products.store'), [
        'sku' => 'KY-DUP-01', // duplicate!
        'name' => 'منتج ثان بنفس الكود',
        'category_id' => $category->id,
        'cost_price' => 12,
        'wholesale_price' => 22,
        'retail_price' => 32,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    $response->assertSessionHasErrors(['sku']);
});

test('duplicate non-null barcode is strictly rejected', function () {
    $category = Category::create(['name' => 'تصنيف عام', 'slug' => 'general']);

    Product::create([
        'sku' => 'KY-BAR-01',
        'name' => 'منتج باركود 1',
        'category_id' => $category->id,
        'barcode' => '690000000001',
        'cost_price' => 10,
        'wholesale_price' => 20,
        'retail_price' => 30,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    $response = $this->post(route('products.store'), [
        'sku' => 'KY-BAR-02',
        'name' => 'منتج باركود 2 مكرر',
        'category_id' => $category->id,
        'barcode' => '690000000001', // duplicate barcode!
        'cost_price' => 15,
        'wholesale_price' => 25,
        'retail_price' => 35,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    $response->assertSessionHasErrors(['barcode']);
});

test('multiple products without barcode are allowed simultaneously without collision', function () {
    $category = Category::create(['name' => 'تصنيف عام', 'slug' => 'general']);

    $product1 = Product::create([
        'sku' => 'KY-NOBAR-01',
        'name' => 'منتج بدون باركود 1',
        'category_id' => $category->id,
        'barcode' => null,
        'cost_price' => 10,
        'wholesale_price' => 20,
        'retail_price' => 30,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    $product2 = Product::create([
        'sku' => 'KY-NOBAR-02',
        'name' => 'منتج بدون باركود 2',
        'category_id' => $category->id,
        'barcode' => null,
        'cost_price' => 15,
        'wholesale_price' => 25,
        'retail_price' => 35,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    expect($product1->barcode)->toBeNull()
        ->and($product2->barcode)->toBeNull();

    $this->assertDatabaseCount('products', 2);
});

test('product can be edited and retains existing barcode and sku', function () {
    $category = Category::create(['name' => 'تصنيف عام', 'slug' => 'general']);

    $product = Product::create([
        'sku' => 'KY-EDIT-01',
        'name' => 'الاسم القديم',
        'category_id' => $category->id,
        'barcode' => '691112223334',
        'cost_price' => 10.00,
        'wholesale_price' => 20.00,
        'retail_price' => 30.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    $response = $this->put(route('products.update', $product), [
        'sku' => 'KY-EDIT-01', // unchanged SKU
        'name' => 'الاسم الجديد المحدث',
        'category_id' => $category->id,
        'barcode' => '691112223334', // unchanged Barcode
        'cost_price' => 12.00,
        'wholesale_price' => 24.00,
        'retail_price' => 36.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 8,
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('status', 'success');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'الاسم الجديد المحدث',
        'cost_price' => 12.00,
        'wholesale_price' => 24.00,
    ]);
});

test('product search filters accurately by name, sku, and barcode', function () {
    $category = Category::create(['name' => 'إكسسوارات شعر', 'slug' => 'hair']);

    Product::create([
        'sku' => 'KY-SRCH-A',
        'name' => 'طوق شعر ذهبي مرصع',
        'category_id' => $category->id,
        'barcode' => '111122223333',
        'cost_price' => 10,
        'wholesale_price' => 20,
        'retail_price' => 30,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    Product::create([
        'sku' => 'KY-SRCH-B',
        'name' => 'بكلة شعر لؤلؤ فضي',
        'category_id' => $category->id,
        'barcode' => '444455556666',
        'cost_price' => 15,
        'wholesale_price' => 25,
        'retail_price' => 35,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    // Search by name
    $resName = $this->get(route('products.index', ['search' => 'طوق']));
    $resName->assertSee('طوق شعر ذهبي مرصع');
    $resName->assertDontSee('بكلة شعر لؤلؤ فضي');

    // Search by SKU
    $resSku = $this->get(route('products.index', ['search' => 'KY-SRCH-B']));
    $resSku->assertSee('بكلة شعر لؤلؤ فضي');
    $resSku->assertDontSee('طوق شعر ذهبي مرصع');

    // Search by Barcode
    $resBarcode = $this->get(route('products.index', ['search' => '111122223333']));
    $resBarcode->assertSee('طوق شعر ذهبي مرصع');
    $resBarcode->assertDontSee('بكلة شعر لؤلؤ فضي');
});

test('category filter isolates products belonging to selected category', function () {
    $cat1 = Category::create(['name' => 'أطواق', 'slug' => 'headbands']);
    $cat2 = Category::create(['name' => 'أساور', 'slug' => 'bracelets']);

    Product::create([
        'sku' => 'KY-CAT1',
        'name' => 'طوق وردي',
        'category_id' => $cat1->id,
        'cost_price' => 10,
        'wholesale_price' => 20,
        'retail_price' => 30,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    Product::create([
        'sku' => 'KY-CAT2',
        'name' => 'إسورة خرز',
        'category_id' => $cat2->id,
        'cost_price' => 10,
        'wholesale_price' => 20,
        'retail_price' => 30,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    $res = $this->get(route('products.index', ['category_id' => $cat1->id]));
    $res->assertSee('طوق وردي');
    $res->assertDontSee('إسورة خرز');
});

test('barcode generation endpoint returns valid internal barcode', function () {
    $response = $this->get(route('products.generate-barcode'));

    $response->assertStatus(200);
    $response->assertJsonStructure(['barcode']);

    $barcode = $response->json('barcode');
    $prefix = BarcodeSetting::getSettings()->barcode_prefix;
    expect(str_starts_with($barcode, $prefix))->toBeTrue()
        ->and(strlen($barcode))->toBeGreaterThanOrEqual(12);
});

test('barcode lookup endpoint returns product details and executes under 50ms benchmark', function () {
    $category = Category::create(['name' => 'خواتم وإكسسوار', 'slug' => 'rings']);

    $product = Product::create([
        'sku' => 'KY-RNG-099',
        'name' => 'خاتم عقلة كوري موديل 2026',
        'category_id' => $category->id,
        'barcode' => '698889991112',
        'cost_price' => 8.00,
        'wholesale_price' => 15.00,
        'retail_price' => 25.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 15,
        'is_active' => true,
    ]);

    // Warm-up query
    $response = $this->get(route('products.lookup-barcode', ['barcode' => '698889991112']));
    $response->assertStatus(200);
    $response->assertJson([
        'found' => true,
        'product' => [
            'id' => $product->id,
            'sku' => 'KY-RNG-099',
            'name' => 'خاتم عقلة كوري موديل 2026',
            'category_name' => 'خواتم وإكسسوار',
            'barcode' => '698889991112',
            'wholesale_price' => 15.00,
        ],
    ]);

    // Benchmark local indexed lookup time over 5 iterations
    $times = [];
    for ($i = 0; $i < 5; $i++) {
        $start = microtime(true);
        $res = $this->get(route('products.lookup-barcode', ['barcode' => '698889991112']));
        $durationMs = (microtime(true) - $start) * 1000;
        $times[] = $durationMs;
        $res->assertStatus(200);
    }

    $avgDuration = array_sum($times) / count($times);
    // Document and assert that average lookup duration is fast (under 50ms in testing environment)
    expect($avgDuration)->toBeLessThan(50.0);
});

test('barcode lookup returns 404 for non-existent barcode', function () {
    $response = $this->get(route('products.lookup-barcode', ['barcode' => '999999999999']));

    $response->assertStatus(404);
    $response->assertJson([
        'found' => false,
    ]);
});

test('viewing non-existent product returns 404 not found', function () {
    $response = $this->get('/products/999999');

    $response->assertStatus(404);
});
