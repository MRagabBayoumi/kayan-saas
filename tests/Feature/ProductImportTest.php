<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('user can download pre-formatted CSV template with Arabic UTF-8 BOM', function () {
    $response = $this->get(route('products.import.template'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $response->assertHeader('Content-Disposition', 'attachment; filename="kayan_products_template.csv"');

    $content = $response->getContent();
    // Must start with UTF-8 BOM for Microsoft Excel Arabic rendering
    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue()
        ->and($content)->toContain('كود الصنف')
        ->and($content)->toContain('اسم المنتج')
        ->and($content)->toContain('سعر الجملة');
});

test('import page renders upload interface and template download link', function () {
    $response = $this->get(route('products.import.form'));

    $response->assertStatus(200);
    $response->assertSee('استيراد المنتجات من إكسيل');
    $response->assertSee('تحميل القالب المنسق');
    $response->assertSee('name="import_file"', false);
});

test('uploading CSV audits rows against database and categorizes new, update, and invalid rows', function () {
    $category = Category::create(['name' => 'إكسسوارات شعر', 'slug' => 'hair']);

    // Pre-existing product in database
    Product::create([
        'sku' => 'KY-EXISTING-01',
        'name' => 'الصنف القديم المسجل',
        'category_id' => $category->id,
        'barcode' => '691112223334',
        'cost_price' => 10.00,
        'wholesale_price' => 20.00,
        'retail_price' => 30.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    // CSV with: 1 new product, 1 existing product (update), 1 invalid product (negative price)
    $csvData = "\xEF\xBB\xBF"."كود الصنف,اسم المنتج,التصنيف,الباركود,سعر التكلفة,سعر الجملة,سعر التجزئة,الوحدة,حد الطلب\n"
        ."KY-NEW-001,توكة لؤلؤ جديدة,توك شعر,699999999999,15.00,28.00,45.00,دستة,10\n"
        ."KY-EXISTING-01,الصنف القديم المحدث,إكسسوارات شعر,691112223334,12.00,25.00,38.00,قطعة,8\n"
        ."KY-INVALID-01,منتج بسعر سالب,عام,,10.00,-5.00,20.00,قطعة,5\n";

    $file = UploadedFile::fake()->createWithContent('products_test.csv', $csvData);

    $response = $this->post(route('products.import.review'), [
        'import_file' => $file,
    ]);

    $response->assertStatus(200);
    $response->assertSee('مراجعة وتدقيق بيانات الاستيراد');

    $reviewData = session('import_review_data');
    expect($reviewData)->not->toBeNull()
        ->and($reviewData['stats']['total'])->toBe(3)
        ->and($reviewData['stats']['new_count'])->toBe(1)
        ->and($reviewData['stats']['update_count'])->toBe(1)
        ->and($reviewData['stats']['invalid_count'])->toBe(1);

    // Assert UI displays status badges
    $response->assertSee('جديد');
    $response->assertSee('تحديث');
    $response->assertSee('خطأ');
    $response->assertSee('KY-NEW-001');
    $response->assertSee('KY-EXISTING-01');
    $response->assertSee('سعر الجملة غير صالح أو سالب');
});

test('confirming import inserts new records, updates existing records, and creates categories atomically', function () {
    $existingCategory = Category::create(['name' => 'خواتم', 'slug' => 'rings']);

    $existingProduct = Product::create([
        'sku' => 'KY-RING-01',
        'name' => 'خاتم كوري قديم',
        'category_id' => $existingCategory->id,
        'cost_price' => 10.00,
        'wholesale_price' => 20.00,
        'retail_price' => 30.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    // Prepare audited review session data
    $reviewData = [
        'stats' => ['total' => 2, 'new_count' => 1, 'update_count' => 1, 'invalid_count' => 0],
        'rows' => [
            [
                'line' => 2,
                'action' => 'new',
                'sku' => 'KY-BRC-01',
                'name' => 'إسورة خرز ملون',
                'category_name' => 'أساور جديدة', // category doesn't exist yet!
                'barcode' => '698887776665',
                'cost_price' => 8.50,
                'wholesale_price' => 16.00,
                'retail_price' => 25.00,
                'unit' => 'قطعة',
                'min_stock_alert' => 10,
                'errors' => [],
            ],
            [
                'line' => 3,
                'action' => 'update',
                'sku' => 'KY-RING-01',
                'name' => 'خاتم كوري محدث المواصفات',
                'category_name' => 'خواتم',
                'barcode' => '691112223334',
                'cost_price' => 12.00,
                'wholesale_price' => 24.00, // Updated wholesale price
                'retail_price' => 35.00,
                'unit' => 'قطعة',
                'min_stock_alert' => 8,
                'errors' => [],
            ],
        ],
    ];

    session(['import_review_data' => $reviewData]);

    $response = $this->post(route('products.import.confirm'), [
        'update_existing' => '1',
        'create_categories' => '1',
    ]);

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('status', 'success');

    // 1. Verify New Category was automatically created
    $this->assertDatabaseHas('categories', [
        'name' => 'أساور جديدة',
    ]);

    // 2. Verify New Product was inserted
    $this->assertDatabaseHas('products', [
        'sku' => 'KY-BRC-01',
        'name' => 'إسورة خرز ملون',
        'barcode' => '698887776665',
        'wholesale_price' => 16.00,
    ]);

    // 3. Verify Existing Product was updated
    $this->assertDatabaseHas('products', [
        'id' => $existingProduct->id,
        'sku' => 'KY-RING-01',
        'name' => 'خاتم كوري محدث المواصفات',
        'wholesale_price' => 24.00,
    ]);

    // Session review data is cleared
    expect(session('import_review_data'))->toBeNull();
});

test('import parses float and decimal numbers with commas, currency, and Arabic numerals accurately', function () {
    $service = app(ProductImportService::class);

    expect($service->parseDecimal('160.50'))->toBe(160.50)
        ->and($service->parseDecimal('160,50'))->toBe(160.50)
        ->and($service->parseDecimal('١٦٠.٥٠'))->toBe(160.50)
        ->and($service->parseDecimal('١٦٠،٥٠'))->toBe(160.50)
        ->and($service->parseDecimal('160٫50'))->toBe(160.50)
        ->and($service->parseDecimal(' 160.50 ج.م '))->toBe(160.50)
        ->and($service->parseDecimal('1,250.75'))->toBe(1250.75)
        ->and($service->parseDecimal('1.250,75'))->toBe(1250.75)
        ->and($service->parseDecimal('0.00'))->toBe(0.0)
        ->and($service->parseDecimal(''))->toBeNull();

    // Now test auditing a CSV containing floats with commas and decimals
    $csvData = "\xEF\xBB\xBF"."كود الصنف,اسم المنتج,التصنيف,الباركود,سعر التكلفة,سعر الجملة,سعر التجزئة,سعر الموزع,الوحدة,عدد القطع في الطرد,حد الطلب\n"
        ."KY-DEC-01,منتج عشري فاخر,أصناف حديثة,6900000001,160.50,\"220,50\",300.75,190.25,دستة,12,5\n";

    $file = UploadedFile::fake()->createWithContent('products_decimal.csv', $csvData);

    $response = $this->post(route('products.import.review'), [
        'import_file' => $file,
    ]);

    $response->assertStatus(200);
    $reviewData = session('import_review_data');
    expect($reviewData['stats']['invalid_count'])->toBe(0)
        ->and($reviewData['stats']['new_count'])->toBe(1);

    $row = $reviewData['rows'][0];
    expect($row['cost_price'])->toBe(160.50)
        ->and($row['wholesale_price'])->toBe(220.50)
        ->and($row['retail_price'])->toBe(300.75)
        ->and($row['distributor_price'])->toBe(190.25)
        ->and($row['pieces_per_package'])->toBe(12);
});

test('import captures full form inputs including distributor price, pieces per package, and opening stock FIFO batch', function () {
    $warehouse = Warehouse::create([
        'name' => 'مستودع العبور الرئيسي',
        'code' => 'WH-OB-01',
        'is_default' => true,
        'is_active' => true,
    ]);

    $csvData = "\xEF\xBB\xBF"."كود الصنف,اسم المنتج,التصنيف,الباركود,سعر التكلفة,سعر الجملة,سعر التجزئة,سعر الموزع,وحدة القياس,عدد القطع في الطرد,حد الطلب الأدنى,الكمية الافتتاحية,تكلفة الرصيد الافتتاحي,المستودع المستلم,تاريخ الرصيد الافتتاحي\n"
        ."KY-FULL-01,طقم إكسسوارات كامل,أطقم,695554443332,45.50,75.00,110.00,65.00,طقم,6,10,25.00,45.50,مستودع العبور الرئيسي,2026-10-14\n";

    $file = UploadedFile::fake()->createWithContent('products_full.csv', $csvData);

    // 1. Review
    $response = $this->post(route('products.import.review'), [
        'import_file' => $file,
    ]);

    $response->assertStatus(200);
    $reviewData = session('import_review_data');
    expect($reviewData['stats']['new_count'])->toBe(1)
        ->and($reviewData['rows'][0]['opening_quantity'])->toBe(25.00)
        ->and($reviewData['rows'][0]['distributor_price'])->toBe(65.00)
        ->and($reviewData['rows'][0]['pieces_per_package'])->toBe(6);

    // 2. Confirm
    $confirmResponse = $this->post(route('products.import.confirm'), [
        'update_existing' => '1',
        'create_categories' => '1',
    ]);

    $confirmResponse->assertRedirect(route('products.index'));

    // Assert product exists with all fields
    $product = Product::where('sku', 'KY-FULL-01')->first();
    expect($product)->not->toBeNull()
        ->and((float) $product->cost_price)->toBe(45.50)
        ->and((float) $product->distributor_price)->toBe(65.00)
        ->and($product->pieces_per_package)->toBe(6)
        ->and((float) $product->opening_quantity)->toBe(25.00)
        ->and((float) $product->opening_cost)->toBe(45.50)
        ->and($product->opening_warehouse_id)->toBe($warehouse->id);

    // Assert inventory FIFO batch and stock movement were automatically created (normalized to base pieces)
    $this->assertDatabaseHas('inventory_batches', [
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'initial_quantity' => 150.00,
        'remaining_quantity' => 150.00,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'to_warehouse_id' => $warehouse->id,
        'quantity' => 150.00,
        'reference_type' => 'opening_balance',
    ]);
});

test('import initializes opening stock and batch for existing product with zero stock', function () {
    $warehouse = Warehouse::firstOrCreate([
        'code' => 'WH-EXISTING-01',
    ], [
        'name' => 'المستودع الرئيسي',
        'is_default' => true,
        'is_active' => true,
    ]);

    $cat = Category::firstOrCreate(['name' => 'عام'], ['slug' => 'general']);

    // Existing product without stock or batches
    $existing = Product::create([
        'sku' => 'EXISTING-001',
        'name' => 'صنف مسجل سابقاً بدون رصيد',
        'category_id' => $cat->id,
        'cost_price' => 20.00,
        'wholesale_price' => 30.00,
        'retail_price' => 45.00,
        'opening_quantity' => 0.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    $csvData = "\xEF\xBB\xBF"."كود الصنف,اسم المنتج,التصنيف,الباركود,سعر التكلفة,سعر الجملة,سعر التجزئة,سعر الموزع,وحدة القياس,عدد القطع في الطرد,حد الطلب الأدنى,الكمية الافتتاحية,تكلفة الرصيد الافتتاحي,المستودع المستلم,تاريخ الرصيد الافتتاحي\n"
        ."EXISTING-001,صنف مسجل سابقاً بدون رصيد,عام,,20.00,30.00,45.00,28.00,قطعة,1,5,15.00,20.00,المستودع الرئيسي,2026-10-15\n";

    $file = UploadedFile::fake()->createWithContent('existing_with_stock.csv', $csvData);

    $this->post(route('products.import.review'), ['import_file' => $file])->assertStatus(200);
    $this->post(route('products.import.confirm'), ['update_existing' => '1', 'create_categories' => '1'])->assertRedirect(route('products.index'));

    $existing->refresh();
    expect((float) $existing->opening_quantity)->toBe(15.00)
        ->and((float) $existing->total_stock)->toBe(15.00);

    $this->assertDatabaseHas('inventory_batches', [
        'product_id' => $existing->id,
        'warehouse_id' => $warehouse->id,
        'remaining_quantity' => 15.00,
        'retail_price' => 45.00,
        'wholesale_price' => 30.00,
    ]);
});

test('barcode takes precedence over SKU and float numbers are sanitized to integers', function () {
    $cat = Category::firstOrCreate(['name' => 'عام'], ['slug' => 'general']);

    // Pre-existing product in DB with SKU HB-79 and barcode 10035
    $oldProduct = Product::create([
        'sku' => 'HB-79',
        'name' => 'فرشه ميكاب',
        'category_id' => $cat->id,
        'barcode' => '10035',
        'cost_price' => 20.00,
        'wholesale_price' => 30.00,
        'retail_price' => 40.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    // CSV containing a new product with new barcode 10280.0, but reusing SKU hb-79
    $csvData = "\xEF\xBB\xBF"."كود الصنف,اسم المنتج,التصنيف,الباركود,سعر التكلفة,سعر الجملة,سعر التجزئة,سعر الموزع,وحدة القياس,عدد القطع في الطرد,حد الطلب الأدنى,الكمية الافتتاحية,تكلفة الرصيد الافتتاحي,المستودع المستلم,تاريخ الرصيد الافتتاحي\n"
        ."hb-79,hb-79 اظافر صافي,عام,10280.0,25.00,35.00,45.00,,قطعة,12.0,5.0,10.0,25.00,,2026-10-19\n";

    $file = UploadedFile::fake()->createWithContent('barcode_priority_test.csv', $csvData);

    $response = $this->post(route('products.import.review'), ['import_file' => $file]);
    $response->assertStatus(200);

    $reviewData = session('import_review_data');
    expect($reviewData)->not->toBeNull()
        ->and($reviewData['stats']['new_count'])->toBe(1)
        ->and($reviewData['stats']['update_count'])->toBe(0);

    $row = $reviewData['rows'][0];
    expect($row['action'])->toBe('new')
        ->and($row['barcode'])->toBe('10280') // stripped of .0
        ->and($row['sku'])->toBe('hb-79-10280') // disambiguated to prevent collision with oldProduct
        ->and($row['sku_adjusted'])->toBeTrue()
        ->and($row['pieces_per_package'])->toBe(12) // integer, not float
        ->and((float) $row['opening_quantity'])->toBe(10.0);

    // Confirm import
    $this->post(route('products.import.confirm'), [
        'update_existing' => '1',
        'create_categories' => '1',
    ])->assertRedirect(route('products.index'));

    // Old product must remain completely unchanged!
    $oldProduct->refresh();
    expect($oldProduct->name)->toBe('فرشه ميكاب')
        ->and($oldProduct->sku)->toBe('HB-79')
        ->and($oldProduct->barcode)->toBe('10035');

    // New product must be created cleanly in DB
    $this->assertDatabaseHas('products', [
        'name' => 'hb-79 اظافر صافي',
        'sku' => 'hb-79-10280',
        'barcode' => '10280',
        'pieces_per_package' => 12,
    ]);
});
