<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'مدير المستودعات',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-TEST-01',
        'name' => 'مستودع العبور الرئيسي',
        'location' => 'المنطقة الصناعية - العبور',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->category = Category::create([
        'name' => 'إكسسوارات وهدايا',
        'code' => 'ACC',
        'slug' => 'accessories-gifts',
        'is_active' => true,
    ]);

    $this->product1 = Product::create([
        'sku' => 'SKU-HAIR-01',
        'barcode' => '62210001',
        'name' => 'طوق شعر لؤلؤي ممتاز',
        'category_id' => $this->category->id,
        'unit' => 'قطعة',
        'cost_price' => 10.0,
        'retail_price' => 15.0,
        'pieces_per_package' => 12, // Dozen package
        'is_active' => true,
    ]);

    $this->product2 = Product::create([
        'sku' => 'SKU-CLIP-02',
        'barcode' => '62210002',
        'name' => 'بنس شعر معدن أسود',
        'category_id' => $this->category->id,
        'unit' => 'قطعة',
        'cost_price' => 5.0,
        'retail_price' => 8.0,
        'pieces_per_package' => 1,
        'is_active' => true,
    ]);

    // Initial stock setup
    $inventoryService = app(InventoryService::class);
    $inventoryService->addStock(
        productId: $this->product1->id,
        warehouseId: $this->warehouse->id,
        quantity: 50.0,
        costPrice: 10.0,
        notes: 'رصيد افتتاحي منتج 1'
    );

    $inventoryService->addStock(
        productId: $this->product2->id,
        warehouseId: $this->warehouse->id,
        quantity: 20.0,
        costPrice: 5.0,
        notes: 'رصيد افتتاحي منتج 2'
    );

    $this->actingAs($this->admin);
});

test('warehouse show endpoint renders real-time stock breakdown and secondary unit packaging', function () {
    $response = $this->get(route('warehouses.show', $this->warehouse));
    $response->assertOk();
    $response->assertSee('مستودع: مستودع العبور الرئيسي');
    $response->assertSee('SKU-HAIR-01');
    $response->assertSee('طوق شعر لؤلؤي ممتاز');
    // Product 1 has 50 pieces and 12 pieces/package => 4 packages + 2 pieces
    $response->assertSee('4 طرد/كرتونة + 2 قطعة');
});

test('warehouse stock print renders correctly in a4, a5, and thermal formats', function () {
    // 1. A4 Format
    $respA4 = $this->get(route('warehouses.stock.print', [$this->warehouse, 'format' => 'a4']));
    $respA4->assertOk();
    $respA4->assertSee('تقرير أرصدة وجرد المستودع');
    $respA4->assertSee('SKU-HAIR-01');
    $respA4->assertSee('data-format="a4"', false);

    // 2. A5 Format
    $respA5 = $this->get(route('warehouses.stock.print', [$this->warehouse, 'format' => 'a5']));
    $respA5->assertOk();
    $respA5->assertSee('data-format="a5"', false);

    // 3. Thermal 80mm
    $respThermal = $this->get(route('warehouses.stock.print', [$this->warehouse, 'format' => 'thermal_80mm']));
    $respThermal->assertOk();
    $respThermal->assertSee('thermal-receipt');
    $respThermal->assertSee('data-format="thermal_80mm"', false);
});

test('stocktake template download produces valid prefilled csv', function () {
    $response = $this->get(route('inventory.stocktake.template', ['warehouse_id' => $this->warehouse->id]));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->getContent();
    expect($content)->toContain('كود الصنف (SKU)')
        ->and($content)->toContain('رصيد الدفتر (النظام)')
        ->and($content)->toContain('الرصيد الفعلي بعد الجرد')
        ->and($content)->toContain('SKU-HAIR-01')
        ->and($content)->toContain('50');
});

test('stocktake preview identifies surplus, deficit, and matches accurately', function () {
    // CSV with:
    // SKU-HAIR-01: counted 55 (surplus +5)
    // SKU-CLIP-02: counted 15 (deficit -5)
    $csvRows = [
        ['كود المستودع', 'اسم المستودع', 'كود الصنف (SKU)', 'الباركود', 'اسم المنتج', 'التصنيف', 'الوحدة', 'رصيد الدفتر', 'الرصيد الفعلي بعد الجرد', 'ملاحظات'],
        ['WH-TEST-01', 'مستودع العبور الرئيسي', 'SKU-HAIR-01', '62210001', 'طوق شعر لؤلؤي ممتاز', 'ACC', 'قطعة', 50, 55, 'فائض جرد معتمد'],
        ['WH-TEST-01', 'مستودع العبور الرئيسي', 'SKU-CLIP-02', '62210002', 'بنس شعر معدن أسود', 'ACC', 'قطعة', 20, 15, 'عجز تالف أثناء النقل'],
    ];

    $csvContent = "\xEF\xBB\xBF";
    $handle = fopen('php://temp', 'r+');
    foreach ($csvRows as $r) {
        fputcsv($handle, $r);
    }
    rewind($handle);
    $csvContent .= stream_get_contents($handle);
    fclose($handle);

    $file = UploadedFile::fake()->createWithContent('stocktake.csv', $csvContent);

    $response = $this->post(route('inventory.stocktake.preview'), [
        'warehouse_id' => $this->warehouse->id,
        'stocktake_file' => $file,
    ]);

    $response->assertRedirect(route('inventory.stocktake', ['warehouse_id' => $this->warehouse->id]));
    $response->assertSessionHas('stocktake_preview');

    $preview = session('stocktake_preview');
    expect($preview['success'])->toBeTrue()
        ->and($preview['summary']['discrepancies_count'])->toBe(2)
        ->and($preview['summary']['surplus_items_count'])->toBe(1)
        ->and($preview['summary']['deficit_items_count'])->toBe(1)
        ->and($preview['summary']['total_surplus_cost'])->toEqual(50.0) // 5 * 10.0
        ->and($preview['summary']['total_deficit_cost'])->toEqual(25.0) // 5 * 5.0
        ->and($preview['summary']['net_cost_impact'])->toEqual(25.0); // +50 - 25 = +25
});

test('stocktake reconcile applies inventory adjustments and updates balances atomically', function () {
    $discrepancies = [
        [
            'product_id' => $this->product1->id,
            'product_sku' => 'SKU-HAIR-01',
            'product_name' => 'طوق شعر لؤلؤي ممتاز',
            'counted_quantity' => 55.0,
            'book_balance' => 50.0,
            'difference' => 5.0,
            'notes' => 'فائض بعد الجرد',
        ],
        [
            'product_id' => $this->product2->id,
            'product_sku' => 'SKU-CLIP-02',
            'product_name' => 'بنس شعر معدن أسود',
            'counted_quantity' => 15.0,
            'book_balance' => 20.0,
            'difference' => -5.0,
            'notes' => 'عجز تالف',
        ],
    ];

    $response = $this->post(route('inventory.stocktake.reconcile'), [
        'warehouse_id' => $this->warehouse->id,
        'discrepancies' => json_encode($discrepancies),
        'reason' => 'تسوية جرد ربع سنوي رسمي',
    ]);

    $response->assertRedirect(route('inventory.stocktake', ['warehouse_id' => $this->warehouse->id]));
    $response->assertSessionHas('success');

    // Verify updated stock balances in warehouse
    $bal1 = StockBalance::where('warehouse_id', $this->warehouse->id)
        ->where('product_id', $this->product1->id)
        ->value('quantity');
    $bal2 = StockBalance::where('warehouse_id', $this->warehouse->id)
        ->where('product_id', $this->product2->id)
        ->value('quantity');

    expect((float) $bal1)->toEqual(55.0)
        ->and((float) $bal2)->toEqual(15.0);
});
