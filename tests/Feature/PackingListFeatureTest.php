<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);
});

test('authenticated user can view packing list builder page', function () {
    $response = $this->actingAs($this->admin)->get(route('packing-list.index'));

    $response->assertOk();
    $response->assertSee('قائمة التعبئة والتجهيز (Packing List)');
    $response->assertSee('تصدير البيانات');
    $response->assertSee('تضمين الكمية');
    $response->assertSee('تضمين السعر والإجمالي');
    $response->assertSee('طباعة 80 مم حراري');
});

test('products packing list alias redirects to main packing list route', function () {
    $response = $this->actingAs($this->admin)->get('/products/packing-list');

    $response->assertRedirect(route('packing-list.index'));
});

test('user can search products for packing list by name, sku, or barcode', function () {
    $category = Category::create([
        'name' => 'إكسسوارات',
        'is_active' => true,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'ساعة ذكية فاخرة',
        'sku' => 'SW-9900',
        'barcode' => '9988776655',
        'wholesale_price' => 150.00,
        'retail_price' => 200.00,
        'distributor_price' => 140.00,
        'cost_price' => 100.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    // Search by SKU
    $response = $this->actingAs($this->admin)
        ->getJson(route('packing-list.search-products', ['q' => 'SW-9900']));

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('products.0.sku', 'SW-9900');
    $response->assertJsonPath('products.0.name', 'ساعة ذكية فاخرة');
    $response->assertJsonPath('products.0.wholesale_price', fn ($val) => (float) $val === 150.0);

    // Search by Barcode
    $responseBarcode = $this->actingAs($this->admin)
        ->getJson(route('packing-list.search-products', ['barcode' => '9988776655']));

    $responseBarcode->assertOk();
    $responseBarcode->assertJsonPath('products.0.barcode', '9988776655');
});

test('user can export packing list to excel format with or without price and quantity', function () {
    $items = [
        [
            'sku' => 'ITM-01',
            'barcode' => '10001',
            'name' => 'طقم كاسات زجاج',
            'unit' => 'طقم',
            'qty' => 10,
            'packages_count' => 2,
            'price' => 85.50,
            'total' => 855.00,
            'notes' => 'كرتونة رقم 1',
        ],
    ];

    // Export with all columns
    $response = $this->actingAs($this->admin)->post(route('packing-list.export-excel'), [
        'doc_number' => 'PL-TEST-001',
        'doc_date' => '2026-10-31',
        'customer_name' => 'تاجر الجملة الشروق',
        'warehouse_name' => 'المخزن الرئيسي',
        'price_type' => 'wholesale',
        'show_quantity' => '1',
        'show_price' => '1',
        'show_packages' => '1',
        'notes' => 'شحن سريع',
        'items' => json_encode($items),
    ]);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
    $response->assertHeader('Content-Disposition', 'attachment; filename="packing_list_PL-TEST-001.xls"');
    $this->assertStringContainsString('طقم كاسات زجاج', $response->getContent());
    $this->assertStringContainsString('تاجر الجملة الشروق', $response->getContent());
    $this->assertStringContainsString('855.00', $response->getContent());

    // Export WITHOUT price (pure packing list)
    $responseNoPrice = $this->actingAs($this->admin)->post(route('packing-list.export-excel'), [
        'doc_number' => 'PL-TEST-NOPRICE',
        'show_quantity' => '1',
        'show_price' => '0',
        'show_packages' => '1',
        'items' => json_encode($items),
    ]);
    $responseNoPrice->assertOk();
    $this->assertStringNotContainsString('سعر الوحدة', $responseNoPrice->getContent());
    $this->assertStringNotContainsString('الإجمالي</th>', $responseNoPrice->getContent());

    // Export WITHOUT quantity (quotation / price list mode)
    $responseNoQty = $this->actingAs($this->admin)->post(route('packing-list.export-excel'), [
        'doc_number' => 'PL-TEST-NOQTY',
        'show_quantity' => '0',
        'show_price' => '1',
        'show_packages' => '0',
        'items' => json_encode($items),
    ]);
    $responseNoQty->assertOk();
    $this->assertStringContainsString('سعر الوحدة', $responseNoQty->getContent());
    $this->assertStringNotContainsString('الكمية', $responseNoQty->getContent());
    $this->assertStringNotContainsString('الإجمالي</th>', $responseNoQty->getContent());
});

test('user can export packing list to csv format with conditional columns', function () {
    $items = [
        [
            'sku' => 'SKU-55',
            'barcode' => '55001',
            'name' => 'علبة مناديل فاخرة',
            'unit' => 'علبة',
            'qty' => 50,
            'packages_count' => 5,
            'price' => 12.00,
            'total' => 600.00,
            'notes' => 'تعبئة كرتونة 1 إلى 5',
        ],
    ];

    $response = $this->actingAs($this->admin)->post(route('packing-list.export-csv'), [
        'doc_number' => 'PL-CSV-001',
        'items' => json_encode($items),
        'show_quantity' => '1',
        'show_price' => '1',
        'show_packages' => '1',
    ]);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $this->assertStringContainsString('علبة مناديل فاخرة', $response->getContent());
    $this->assertStringContainsString('SKU-55', $response->getContent());
});

test('user can render print view in a4, a5, and thermal 80mm formats respecting toggles', function () {
    $items = [
        [
            'sku' => 'PRD-100',
            'barcode' => '778899',
            'name' => 'صينية تقديم استيل',
            'unit' => 'قطعة',
            'qty' => 6,
            'packages_count' => 1,
            'price' => 95.00,
            'total' => 570.00,
            'notes' => 'تغليف نايلون فقاعات',
        ],
    ];

    // 1. A4 Format
    $respA4 = $this->actingAs($this->admin)->post(route('packing-list.print'), [
        'doc_number' => 'PL-PRINT-A4',
        'format' => 'a4',
        'customer_name' => 'مؤسسة النور',
        'show_quantity' => '1',
        'show_price' => '1',
        'show_packages' => '1',
        'items' => json_encode($items),
    ]);
    $respA4->assertOk();
    $respA4->assertSee('data-format="a4"', false);
    $respA4->assertSee('صينية تقديم استيل');
    $respA4->assertSee('PL-PRINT-A4');

    // 2. A5 Format
    $respA5 = $this->actingAs($this->admin)->post(route('packing-list.print'), [
        'doc_number' => 'PL-PRINT-A5',
        'format' => 'a5',
        'show_quantity' => '1',
        'show_price' => '0',
        'show_packages' => '1',
        'items' => json_encode($items),
    ]);
    $respA5->assertOk();
    $respA5->assertSee('data-format="a5"', false);

    // 3. Thermal 80mm Format
    $respThermal = $this->actingAs($this->admin)->post(route('packing-list.print'), [
        'doc_number' => 'PL-PRINT-80MM',
        'format' => 'thermal_80mm',
        'show_quantity' => '1',
        'show_price' => '1',
        'show_packages' => '1',
        'items' => json_encode($items),
    ]);
    $respThermal->assertOk();
    $respThermal->assertSee('data-format="thermal_80mm"', false);
    $respThermal->assertSee('قائمة التعبئة والتجهيز (PACKING)');
});

test('user can save packing list to database archive and search by customer name like الحافظ', function () {
    $items = [
        [
            'sku' => 'HAF-01',
            'barcode' => '62210001',
            'name' => 'طقم صحون سيراميك الحافظ',
            'category_name' => 'أدوات منزلية',
            'unit' => 'طقم',
            'qty' => 12,
            'packages_count' => 3,
            'price' => 150.00,
            'total' => 1800.00,
            'notes' => 'توصيل مخزن الحافظ الرئيسي',
        ],
    ];

    // 1. Save packing list via API
    $saveResponse = $this->actingAs($this->admin)->postJson(route('packing-list.save'), [
        'doc_number' => 'PL-HAFEZ-001',
        'doc_date' => '2026-10-31',
        'customer_name' => 'شركة الحافظ للتجارة',
        'warehouse_name' => 'المخزن الرئيسي',
        'price_type' => 'wholesale',
        'show_quantity' => true,
        'show_price' => true,
        'show_packages' => true,
        'notes' => 'عرض أسعار وباكنج شحن خاص بشركة الحافظ',
        'items' => $items,
    ]);

    $saveResponse->assertOk();
    $saveResponse->assertJsonPath('success', true);
    $saveResponse->assertJsonPath('packing_number', 'PL-HAFEZ-001');

    $listId = $saveResponse->json('id');
    $this->assertDatabaseHas('packing_lists', [
        'id' => $listId,
        'packing_number' => 'PL-HAFEZ-001',
        'customer_name' => 'شركة الحافظ للتجارة',
        'total_quantity' => 12,
        'total_packages' => 3,
        'total_amount' => 1800,
    ]);

    $this->assertDatabaseHas('packing_list_items', [
        'packing_list_id' => $listId,
        'product_name' => 'طقم صحون سيراميك الحافظ',
        'quantity' => 12,
        'unit_price' => 150,
    ]);

    // 2. View Archive page and search for الحافظ
    $archiveResponse = $this->actingAs($this->admin)->get(route('packing-list.archive', ['search' => 'الحافظ']));
    $archiveResponse->assertOk();
    $archiveResponse->assertSee('شركة الحافظ للتجارة');
    $archiveResponse->assertSee('PL-HAFEZ-001');
    $archiveResponse->assertSee('سجل قوائم التعبئة والتجهيز');

    // 3. Pre-load into builder
    $loadResponse = $this->actingAs($this->admin)->get(route('packing-list.index', ['load_id' => $listId]));
    $loadResponse->assertOk();
    $loadResponse->assertSee('أنت تقوم باستعراض وتعديل قائمة محفوظة');
    $loadResponse->assertSee('PL-HAFEZ-001');
    $loadResponse->assertSee('شركة الحافظ للتجارة');

    // 4. Direct print from model
    $printModelResponse = $this->actingAs($this->admin)->get(route('packing-list.print-model', ['packingList' => $listId, 'format' => 'a4']));
    $printModelResponse->assertOk();
    $printModelResponse->assertSee('شركة الحافظ للتجارة');
    $printModelResponse->assertSee('PL-HAFEZ-001');

    // 5. Direct Excel export from model
    $excelModelResponse = $this->actingAs($this->admin)->get(route('packing-list.export-excel-model', ['packingList' => $listId]));
    $excelModelResponse->assertOk();
    $excelModelResponse->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
    $this->assertStringContainsString('شركة الحافظ للتجارة', $excelModelResponse->getContent());

    // 6. Delete from archive
    $deleteResponse = $this->actingAs($this->admin)->delete(route('packing-list.destroy', ['packingList' => $listId]));
    $deleteResponse->assertRedirect(route('packing-list.archive'));
    $this->assertDatabaseMissing('packing_lists', ['id' => $listId]);
    $this->assertDatabaseMissing('packing_list_items', ['packing_list_id' => $listId]);
});
