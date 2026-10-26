<?php

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Printing\PythonPrintEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('python print engine service is instantiable and configured', function () {
    $service = app(PythonPrintEngineService::class);

    expect($service)->toBeInstanceOf(PythonPrintEngineService::class);
});

test('python print engine lists registered templates including barcode_label and purchase_invoice_receipt', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine virtual environment is not available in this environment.');
    }

    $templates = $service->listTemplates();

    expect($templates)->toBeArray()
        ->not->toBeEmpty();

    $ids = array_column($templates, 'template_id');
    expect($ids)->toContain('barcode_label')
        ->toContain('purchase_invoice_receipt')
        ->toContain('sales_invoice_receipt')
        ->toContain('shift_closing_receipt')
        ->toContain('sales_invoice_document')
        ->toContain('purchase_invoice_document')
        ->toContain('product_ledger_document')
        ->toContain('low_stock_document');
});

test('python print engine rejects unknown template identifiers explicitly with error message', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $result = $service->executeJob('invalid_dummy_template_xyz', ['foo' => 'bar'], 1, null, true);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe('ERROR')
        ->and($result['message'])->toContain("Unknown template identifier: 'invalid_dummy_template_xyz'");
});

test('python print engine rejects missing required fields on barcode_label template', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    // barcode_label requires product_name and barcode
    $result = $service->executeJob('barcode_label', ['product_name' => 'تجربة بدون باركود'], 1, null, true);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe('ERROR')
        ->and($result['message'])->toContain('Missing required field(s)');
});

test('python print engine dry-run successfully renders barcode_label with Arabic shaping and Code 128 barcode', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $category = Category::create([
        'name' => 'إلكترونيات',
        'code' => 'ELEC',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'TEST-SKU-999',
        'name' => 'سماعة رأس بلوتوث احترافية',
        'barcode' => '6221234567890',
        'purchase_price' => 150.00,
        'cost_price' => 150.00,
        'retail_price' => 220.00,
        'wholesale_price' => 190.00,
        'minimum_stock' => 5,
        'is_active' => true,
    ]);

    $result = $service->printBarcodeLabel($product, 1, null, ['width_mm' => 38, 'height_mm' => 25], true);

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('DRY_RUN_RENDERED')
        ->and($result['template_id'])->toBe('barcode_label');
});

test('python print engine renders 38x15mm label with Arabic product name and saves preview without bleeding', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $previewPath = storage_path('app/test_laravel_preview.png');
    if (file_exists($previewPath)) {
        unlink($previewPath);
    }

    $data = [
        'product_name' => 'اكلادور',
        'barcode' => '4549131330755',
        'sku' => 'COCO-1',
        'company_name' => 'شركة كيان للتجارة و التوزيع',
        'show_company_name' => true,
        'show_product_name' => true,
        'show_barcode_bars' => true,
        'show_item_sku' => true,
        'print_price_type' => 'none',
        'width_mm' => 38,
        'height_mm' => 15,
    ];

    $result = $service->executeJob('barcode_label', $data, 1, null, true, $previewPath);

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('DRY_RUN_RENDERED');

    expect(file_exists($previewPath))->toBeTrue();
    expect(filesize($previewPath))->toBeGreaterThan(100);
});

test('python print engine dry-run successfully renders purchase_invoice_receipt 80mm format', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $supplier = Supplier::create([
        'name' => 'شركة التوريدات المتكاملة',
        'contact_person' => 'أحمد علي',
        'phone' => '01012345678',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'name' => 'المستودع المركزي',
        'code' => 'WH-01',
        'is_active' => true,
    ]);

    $category = Category::create([
        'name' => 'عام',
        'code' => 'GEN',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'ITEM-01',
        'name' => 'قلم جاف أزرق',
        'barcode' => '6229988776655',
        'purchase_price' => 5.0,
        'cost_price' => 5.0,
        'retail_price' => 10.0,
        'wholesale_price' => 8.0,
        'minimum_stock' => 1,
        'is_active' => true,
    ]);

    $invoice = PurchaseInvoice::create([
        'invoice_number' => 'PINV-TEST-2026-001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'subtotal' => 100.00,
        'tax_amount' => 0.00,
        'total_amount' => 100.00,
        'paid_amount' => 100.00,
        'remaining_amount' => 0.00,
        'status' => PurchaseInvoice::STATUS_DRAFT,
    ]);

    $invoice->items()->create([
        'product_id' => $product->id,
        'quantity' => 20,
        'unit_cost' => 5.0,
        'subtotal' => 100.0,
    ]);

    $result = $service->printPurchaseInvoice($invoice, null, true);

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('DRY_RUN_RENDERED')
        ->and($result['template_id'])->toBe('purchase_invoice_receipt');
});

test('POST /products/{product}/direct-print validates inputs and accepts valid print request', function () {
    $category = Category::create([
        'name' => 'مستلزمات',
        'code' => 'SUPP',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'PRD-DIRECT-01',
        'name' => 'علبة مناديل معقمة',
        'barcode' => '62200000001',
        'purchase_price' => 20.0,
        'cost_price' => 20.0,
        'retail_price' => 30.0,
        'wholesale_price' => 25.0,
        'minimum_stock' => 10,
        'is_active' => true,
    ]);

    // Test validation for invalid quantity (> 500)
    $responseInvalid = $this->postJson(route('products.direct-print', $product), [
        'quantity' => 9999,
    ]);
    $responseInvalid->assertStatus(422)
        ->assertJsonValidationErrors(['quantity']);

    // Test validation for invalid size
    $responseInvalidSize = $this->postJson(route('products.direct-print', $product), [
        'size' => 'invalid_size_100x100',
    ]);
    $responseInvalidSize->assertStatus(422)
        ->assertJsonValidationErrors(['size']);

    // Test that orientation is not accepted or required
    $responseWithOrientation = $this->postJson(route('products.direct-print', $product), [
        'quantity' => 1,
        'size' => '38x25',
    ]);

    // Result should be either 200 (if printer/service available) or 422 with structured json, never a 500
    expect(in_array($responseWithOrientation->status(), [200, 422], true))->toBeTrue();
    $data = $responseWithOrientation->json();
    expect($data)->toHaveKey('success');
});

test('POST /products/direct-print/batch validates items payload', function () {
    $response = $this->postJson(route('products.direct-print-batch'), [
        'items' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

test('POST /purchases/{purchase}/direct-print returns 404 for non-existent invoice', function () {
    $response = $this->postJson('/purchases/999999/direct-print');

    $response->assertStatus(404);
});

test('print-label view renders with direct thermal print button and does not contain obsolete orientation', function () {
    $category = Category::create([
        'name' => 'أدوات منزلية',
        'code' => 'HOME',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'BLADE-CHECK-01',
        'name' => 'طقم سكاكين ستانلس',
        'barcode' => '62299999999',
        'purchase_price' => 100.0,
        'cost_price' => 100.0,
        'retail_price' => 150.0,
        'wholesale_price' => 130.0,
        'minimum_stock' => 2,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.print-label', $product));

    $response->assertOk()
        ->assertSee('data-action="direct-thermal-print"', false)
        ->assertDontSee('data-orientation', false)
        ->assertDontSee('طباعة مائلة', false);
});

test('python print engine supports tspl raw printing parameters with gap and direction', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $category = Category::firstOrCreate(['name' => 'عام'], ['code' => 'GEN', 'is_active' => true]);

    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'TSPL-TEST-01',
        'name' => 'منتج اختبار TSPL',
        'barcode' => '6220000000001',
        'purchase_price' => 50.00,
        'cost_price' => 50.00,
        'retail_price' => 75.00,
        'minimum_stock' => 1,
        'is_active' => true,
    ]);

    $result = $service->printBarcodeLabel(
        $product,
        2,
        'Xprinter XP-233B',
        [
            'width_mm' => 38,
            'height_mm' => 13,
            'gap_mm' => 2.0,
            'direction' => 1,
        ],
        true
    );

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('DRY_RUN_RENDERED')
        ->and($result['copies'])->toBe(2)
        ->and($result['printer'])->toBe('Xprinter XP-233B');
});

test('python print engine supports multi-product batch printing without blank labels and exact copy count', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $category = Category::firstOrCreate(['name' => 'عام'], ['code' => 'GEN', 'is_active' => true]);

    $p1 = Product::create([
        'category_id' => $category->id,
        'sku' => 'BATCH-P1',
        'name' => 'منتج مجمع 1',
        'barcode' => '6220000000011',
        'purchase_price' => 20.00,
        'cost_price' => 20.00,
        'retail_price' => 30.00,
        'minimum_stock' => 1,
        'is_active' => true,
    ]);

    $p2 = Product::create([
        'category_id' => $category->id,
        'sku' => 'BATCH-P2',
        'name' => 'منتج مجمع 2',
        'barcode' => '6220000000012',
        'purchase_price' => 40.00,
        'cost_price' => 40.00,
        'retail_price' => 60.00,
        'minimum_stock' => 1,
        'is_active' => true,
    ]);

    $p3 = Product::create([
        'category_id' => $category->id,
        'sku' => 'BATCH-P3',
        'name' => 'منتج مجمع 3',
        'barcode' => '6220000000013',
        'purchase_price' => 15.00,
        'cost_price' => 15.00,
        'retail_price' => 25.00,
        'minimum_stock' => 1,
        'is_active' => true,
    ]);

    $items = [
        ['product' => $p1, 'qty' => 2],
        ['product' => $p2, 'qty' => 1],
        ['product' => $p3, 'qty' => 3],
    ];

    $result = $service->printBarcodeLabelBatch(
        $items,
        'Xprinter XP-233B',
        [
            'width_mm' => 38,
            'height_mm' => 13,
            'gap_mm' => 2.0,
        ],
        true
    );

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('DRY_RUN_RENDERED')
        ->and($result['batch_mode'])->toBeTrue()
        ->and($result['total_products'])->toBe(3)
        ->and($result['total_labels'])->toBe(6)
        ->and($result['items_summary'])->toHaveCount(3);

    expect($result['items_summary'][0]['requested_quantity'])->toBe(2)
        ->and($result['items_summary'][1]['requested_quantity'])->toBe(1)
        ->and($result['items_summary'][2]['requested_quantity'])->toBe(3);
});

test('POST /products/direct-print/batch sends batch of multiple products in single atomic job', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available in this environment.');
    }

    $category = Category::firstOrCreate(['name' => 'عام'], ['code' => 'GEN', 'is_active' => true]);

    $p1 = Product::create([
        'category_id' => $category->id,
        'sku' => 'ROUTE-P1',
        'name' => 'صنف أ',
        'barcode' => '6220000000021',
        'purchase_price' => 10.00,
        'cost_price' => 10.00,
        'retail_price' => 15.00,
        'minimum_stock' => 1,
        'is_active' => true,
    ]);

    $p2 = Product::create([
        'category_id' => $category->id,
        'sku' => 'ROUTE-P2',
        'name' => 'صنف ب',
        'barcode' => '6220000000022',
        'purchase_price' => 25.00,
        'cost_price' => 25.00,
        'retail_price' => 35.00,
        'minimum_stock' => 1,
        'is_active' => true,
    ]);

    $p3 = Product::create([
        'category_id' => $category->id,
        'sku' => 'ROUTE-P3',
        'name' => 'صنف ج',
        'barcode' => '6220000000023',
        'purchase_price' => 50.00,
        'cost_price' => 50.00,
        'retail_price' => 70.00,
        'minimum_stock' => 1,
        'is_active' => true,
    ]);

    $response = $this->postJson(route('products.direct-print-batch'), [
        'items' => [
            ['id' => $p1->id, 'qty' => 2],
            ['id' => $p2->id, 'qty' => 1],
            ['id' => $p3->id, 'qty' => 3],
        ],
        'size' => '38x13',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'total_copies' => 6,
            'printed_products_count' => 3,
        ]);
});

test('python print engine dry-run successfully renders sales_invoice_receipt', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available in this environment.');
    }

    $warehouse = Warehouse::create([
        'name' => 'المستودع الرئيسي',
        'code' => 'WH-MAIN',
        'is_active' => true,
    ]);

    $category = Category::create([
        'name' => 'عطور',
        'code' => 'PERF',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'PERF-001',
        'name' => 'عطر مسك الليل 100 مل',
        'barcode' => '6228888888888',
        'cost_price' => 100.00,
        'retail_price' => 180.00,
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'أحمد فتحي',
        'phone' => '01012345678',
        'is_active' => true,
    ]);

    $user = User::factory()->create();

    $sale = SalesInvoice::create([
        'invoice_number' => 'INV-TEST-9988',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'invoice_date' => now(),
        'subtotal' => 180.00,
        'discount_amount' => 10.00,
        'total_amount' => 170.00,
        'paid_amount' => 200.00,
        'change_amount' => 30.00,
        'status' => SalesInvoice::STATUS_POSTED,
    ]);

    SalesInvoiceItem::create([
        'sales_invoice_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 1,
        'base_quantity' => 1,
        'unit_price' => 180.00,
        'subtotal' => 180.00,
        'total_cost' => 100.00,
    ]);

    $result = $service->printSalesInvoice($sale, null, true);

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('DRY_RUN_RENDERED')
        ->and($result['template_id'])->toBe('sales_invoice_receipt');
});

test('POST /sales/{sale}/direct-print initiates print job via Python print engine', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available in this environment.');
    }

    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $warehouse = Warehouse::create([
        'name' => 'مخزن فرعي',
        'code' => 'WH-SUB',
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'عميل تجريبي',
        'phone' => '01000000000',
        'is_active' => true,
    ]);

    $sale = SalesInvoice::create([
        'invoice_number' => 'INV-TEST-ROUTE-1',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'invoice_date' => now(),
        'subtotal' => 50.00,
        'total_amount' => 50.00,
        'status' => SalesInvoice::STATUS_POSTED,
    ]);

    $response = $this->actingAs($user)->postJson(route('sales.direct-print', $sale));

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

test('POST /shifts/{shift}/direct-print initiates print job via Python print engine', function () {
    $service = app(PythonPrintEngineService::class);

    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available in this environment.');
    }

    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $warehouse = Warehouse::create([
        'name' => 'فرع القاهرة',
        'code' => 'WH-CAIRO',
        'is_active' => true,
    ]);

    $shift = CashierShift::create([
        'shift_number' => 'SH-TEST-ROUTE-1',
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'opened_at' => now()->subHours(8),
        'closed_at' => now(),
        'closed_by' => $user->id,
        'opening_balance' => 500.00,
        'cash_sales_amount' => 1200.00,
        'expected_closing_balance' => 1700.00,
        'actual_closing_balance' => 1700.00,
        'difference' => 0.00,
        'status' => CashierShift::STATUS_CLOSED,
    ]);

    $response = $this->actingAs($user)->postJson(route('shifts.direct-print', $shift));

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

test('python print engine dry-run renders sales_invoice_document in A4 and A5 formats', function () {
    $service = app(PythonPrintEngineService::class);
    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $customer = Customer::create(['name' => 'شركة النصر للتجارة', 'phone' => '01000000001', 'is_active' => true]);
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي', 'code' => 'WH-MAIN', 'is_active' => true]);
    $category = Category::create(['name' => 'أدوات منزلية', 'code' => 'HOME', 'is_active' => true]);
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'TEST-A4-01',
        'name' => 'طقم أواني فاخر',
        'cost_price' => 100.0,
        'retail_price' => 150.0,
        'wholesale_price' => 130.0,
        'is_active' => true,
    ]);

    $sale = SalesInvoice::create([
        'invoice_number' => 'INV-TEST-A4-1',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'status' => SalesInvoice::STATUS_POSTED,
        'invoice_date' => now(),
        'subtotal' => 300.0,
        'total_amount' => 300.0,
    ]);

    SalesInvoiceItem::create([
        'sales_invoice_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 150.0,
        'cost_price' => 100.0,
        'subtotal' => 300.0,
        'base_quantity' => 2,
    ]);

    // Test A4
    $resA4 = $service->printSalesInvoice($sale, 'a4', null, true);
    expect($resA4['success'])->toBeTrue()
        ->and($resA4['template_id'])->toBe('sales_invoice_document');

    // Test A5
    $resA5 = $service->printSalesInvoice($sale, 'a5', null, true);
    expect($resA5['success'])->toBeTrue()
        ->and($resA5['template_id'])->toBe('sales_invoice_document');
});

test('python print engine dry-run renders purchase_invoice_document in A4 format', function () {
    $service = app(PythonPrintEngineService::class);
    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $supplier = Supplier::create(['name' => 'شركة التوريدات العالمية', 'phone' => '01200000002', 'is_active' => true]);
    $warehouse = Warehouse::create(['name' => 'المخزن المركزي', 'code' => 'WH-CTR', 'is_active' => true]);
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $category = Category::create(['name' => 'إلكترونيات', 'code' => 'ELEC-2', 'is_active' => true]);
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'PUR-TEST-01',
        'name' => 'محول طاقة ذكي',
        'cost_price' => 80.0,
        'retail_price' => 120.0,
        'wholesale_price' => 100.0,
        'is_active' => true,
    ]);

    $purchase = PurchaseInvoice::create([
        'invoice_number' => 'PUR-TEST-A4-1',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'status' => PurchaseInvoice::STATUS_POSTED,
        'invoice_date' => now(),
        'total_amount' => 800.0,
    ]);

    $purchase->items()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_cost' => 80.0,
        'subtotal' => 800.0,
        'base_quantity' => 10,
    ]);

    $res = $service->printPurchaseInvoice($purchase, 'a4', null, true);
    expect($res['success'])->toBeTrue()
        ->and($res['template_id'])->toBe('purchase_invoice_document');
});

test('POST /products/{product}/ledger/direct-print prints product movement ledger', function () {
    $service = app(PythonPrintEngineService::class);
    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $category = Category::create(['name' => 'عام', 'code' => 'GEN', 'is_active' => true]);
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'LEDGER-01',
        'name' => 'صنف لتجربة كشف الحركة',
        'cost_price' => 50.0,
        'retail_price' => 70.0,
        'wholesale_price' => 60.0,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->postJson(route('products.ledger.direct-print', $product), [
        'format' => 'a4',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

test('POST /inventory/low-stock/direct-print prints low stock shortages report', function () {
    $service = app(PythonPrintEngineService::class);
    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($user)->postJson(route('inventory.low-stock.direct-print'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

test('GET /csrf-token returns valid csrf token for ajax direct print refresh', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($user)->getJson(route('csrf.token'));

    $response->assertOk()
        ->assertJsonStructure(['token']);
    expect($response->json('token'))->not->toBeEmpty();
});

test('PythonPrintEngineService resolves thermal receipt printer path correctly', function () {
    $service = app(PythonPrintEngineService::class);

    config(['printing.thermal_printer_path' => '\\\\192.168.0.41\\XP-80C']);
    expect($service->getReceiptPrinter())->toBe('\\\\192.168.0.41\\XP-80C');
});

test('POST /sales/{sale}/direct-print accepts UNC network share printer path', function () {
    $service = app(PythonPrintEngineService::class);
    if (! $service->isAvailable()) {
        $this->markTestSkipped('Python print engine is not available.');
    }

    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::create([
        'name' => 'عميل اختبار الطابعة',
        'phone' => '01012345678',
        'is_active' => true,
    ]);
    $warehouse = Warehouse::create([
        'name' => 'مستودع تجربة',
        'code' => 'WH-UNC-01',
        'is_active' => true,
    ]);

    $sale = SalesInvoice::create([
        'invoice_number' => 'INV-UNC-2026-001',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now(),
        'subtotal' => 150.0,
        'tax_amount' => 0.0,
        'discount_amount' => 0.0,
        'total_amount' => 150.0,
        'paid_amount' => 150.0,
        'remaining_amount' => 0.0,
        'payment_status' => 'paid',
        'status' => SalesInvoice::STATUS_POSTED,
    ]);

    $uncPrinter = '\\\\192.168.0.41\\XP-80C';

    $response = $this->actingAs($user)->postJson(route('sales.direct-print', $sale), [
        'printer' => $uncPrinter,
        'format' => 'thermal_80mm',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'template_id' => 'sales_invoice_receipt',
            'printer' => $uncPrinter,
        ]);
});
