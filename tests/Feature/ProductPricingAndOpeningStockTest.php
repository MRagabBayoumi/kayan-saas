<?php

use App\Models\Category;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create([
        'name' => 'إكسسوارات حريمي فاخرة',
        'code' => 'ACC-LUX',
        'is_active' => true,
    ]);

    $this->defaultWarehouse = Warehouse::create([
        'code' => 'WH-MAIN',
        'name' => 'المستودع الرئيسي',
        'location' => 'العتبة - القاهرة',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->branchWarehouse = Warehouse::create([
        'code' => 'WH-BRANCH',
        'name' => 'مستودع الموسكي',
        'location' => 'الموسكي - القاهرة',
        'is_active' => true,
        'is_default' => false,
    ]);
});

test('product create page passes categories and warehouses with default pre-selected', function () {
    $response = $this->get(route('products.create'));

    $response->assertStatus(200);
    $response->assertSee('إضافة منتج جديد');
    $response->assertSee('المستودع الرئيسي (الافتراضي)');
    $response->assertSee('سعر الموزع');
    $response->assertSee('الرصيد الافتتاحي للصنف');
});

test('product can be created with distributor price, pieces per package, and atomic opening balance', function () {
    $response = $this->post(route('products.store'), [
        'sku' => 'HAIR-PEARL-001',
        'name' => 'توكة شعر لؤلؤ أبيض كوري',
        'category_id' => $this->category->id,
        'barcode' => '6921234567890',
        'cost_price' => 18.50,
        'wholesale_price' => 28.00,
        'retail_price' => 45.00,
        'distributor_price' => 24.00,
        'unit' => 'قطعة',
        'pieces_per_package' => 12,
        'min_stock_alert' => 15,
        'is_active' => 1,
        // Opening balance
        'opening_quantity' => 150,
        'opening_cost' => 18.50,
        'opening_warehouse_id' => $this->defaultWarehouse->id,
        'opening_balance_date' => '2026-10-01',
    ]);

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('status', 'success');

    $product = Product::where('sku', 'HAIR-PEARL-001')->first();
    expect($product)->not->toBeNull()
        ->and((float) $product->cost_price)->toEqual(18.50)
        ->and((float) $product->wholesale_price)->toEqual(28.00)
        ->and((float) $product->retail_price)->toEqual(45.00)
        ->and((float) $product->distributor_price)->toEqual(24.00)
        ->and($product->pieces_per_package)->toBe(12)
        ->and((float) $product->opening_quantity)->toEqual(150.00)
        ->and((float) $product->opening_cost)->toEqual(18.50)
        ->and($product->opening_warehouse_id)->toBe($this->defaultWarehouse->id);

    // Verify FIFO Batch created
    $batch = InventoryBatch::where('product_id', $product->id)->first();
    expect($batch)->not->toBeNull()
        ->and($batch->warehouse_id)->toBe($this->defaultWarehouse->id)
        ->and((float) $batch->initial_quantity)->toEqual(150.00)
        ->and((float) $batch->remaining_quantity)->toEqual(150.00)
        ->and((float) $batch->cost_price)->toEqual(18.50)
        ->and($batch->is_exhausted)->toBeFalse();

    // Verify Stock Movement created with running balance
    $movement = StockMovement::where('product_id', $product->id)
        ->where('reference_type', 'opening_balance')
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe(StockMovement::TYPE_IN)
        ->and($movement->to_warehouse_id)->toBe($this->defaultWarehouse->id)
        ->and((float) $movement->quantity)->toEqual(150.00)
        ->and((float) $movement->running_balance)->toEqual(150.00)
        ->and((float) $movement->unit_cost)->toEqual(18.50)
        ->and($movement->batch_id)->toBe($batch->id);

    // Verify StockBalance updated
    $balance = StockBalance::where('product_id', $product->id)
        ->where('warehouse_id', $this->defaultWarehouse->id)
        ->first();

    expect($balance)->not->toBeNull()
        ->and((float) $balance->quantity)->toEqual(150.00);
});

test('product creation without opening stock creates 0 stock balances and 0 batches', function () {
    $response = $this->post(route('products.store'), [
        'sku' => 'HAIR-CLIP-NOSTOCK',
        'name' => 'مشبك شعر معدني ذهبي',
        'category_id' => $this->category->id,
        'cost_price' => 10.00,
        'wholesale_price' => 15.00,
        'retail_price' => 25.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'opening_quantity' => 0,
    ]);

    $response->assertRedirect(route('products.index'));

    $product = Product::where('sku', 'HAIR-CLIP-NOSTOCK')->first();
    expect($product)->not->toBeNull()
        ->and((float) $product->opening_quantity)->toEqual(0.00)
        ->and($product->total_stock)->toEqual(0.00);

    expect(InventoryBatch::where('product_id', $product->id)->count())->toBe(0);
    expect(StockMovement::where('product_id', $product->id)->count())->toBe(0);
});

test('negative distributor price is rejected by validation', function () {
    $response = $this->post(route('products.store'), [
        'sku' => 'INVALID-PRICE-PROD',
        'name' => 'منتج بسعر سالب',
        'category_id' => $this->category->id,
        'cost_price' => 10.00,
        'wholesale_price' => 15.00,
        'retail_price' => 25.00,
        'distributor_price' => -5.00, // Invalid
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    $response->assertSessionHasErrors(['distributor_price']);
});

test('opening stock with missing warehouse returns validation error', function () {
    $response = $this->post(route('products.store'), [
        'sku' => 'OPENING-NOWH-PROD',
        'name' => 'صنف برصيد بدون مستودع',
        'category_id' => $this->category->id,
        'cost_price' => 10.00,
        'wholesale_price' => 15.00,
        'retail_price' => 25.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'opening_quantity' => 50,
        'opening_warehouse_id' => '', // Missing
    ]);

    $response->assertSessionHasErrors(['opening_warehouse_id']);
});

test('product can be updated by non-admin while opening balance remains locked and immutable', function () {
    $warehouseUser = User::firstOrCreate(
        ['email' => 'warehouse-keeper@kayan-erp.com'],
        [
            'name' => 'أمين مخزن تجريبي',
            'password' => bcrypt('password'),
            'role' => User::ROLE_WAREHOUSE,
        ]
    );

    $product = Product::create([
        'sku' => 'IMMUTABLE-PROD',
        'name' => 'صنف تجريبي محمي',
        'category_id' => $this->category->id,
        'cost_price' => 20.00,
        'wholesale_price' => 30.00,
        'retail_price' => 40.00,
        'distributor_price' => 26.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 10,
        'opening_quantity' => 100.00,
        'opening_cost' => 20.00,
        'opening_warehouse_id' => $this->defaultWarehouse->id,
        'opening_balance_date' => '2026-10-01',
    ]);

    $response = $this->actingAs($warehouseUser)->put(route('products.update', $product), [
        'sku' => 'IMMUTABLE-PROD',
        'name' => 'صنف تجريبي تم تحديث اسمه',
        'category_id' => $this->category->id,
        'cost_price' => 20.00,
        'wholesale_price' => 30.00,
        'retail_price' => 40.00,
        'distributor_price' => 26.00,
        'unit' => 'دستة',
        'pieces_per_package' => 24,
        'min_stock_alert' => 15,
        // Attempting to inject different opening values:
        'opening_quantity' => 999.00,
        'opening_cost' => 1.00,
    ]);

    $response->assertRedirect(route('products.index'));

    $freshProduct = $product->fresh();
    expect($freshProduct->name)->toBe('صنف تجريبي تم تحديث اسمه')
        ->and($freshProduct->pieces_per_package)->toBe(24)
        // Opening balance fields are strictly preserved for non-admin
        ->and((float) $freshProduct->opening_quantity)->toEqual(100.00)
        ->and((float) $freshProduct->opening_cost)->toEqual(20.00);
});

test('system administrator can view and edit opening stock balance in edit product page', function () {
    $admin = User::firstOrCreate(
        ['email' => 'admin-test@kayan-erp.com'],
        [
            'name' => 'مدير النظام التجريبي',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]
    );

    $product = Product::create([
        'sku' => 'ADMIN-OPENING-PROD',
        'name' => 'صنف تعديل رصيد افتتاحي',
        'category_id' => $this->category->id,
        'cost_price' => 50.00,
        'wholesale_price' => 70.00,
        'retail_price' => 90.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'opening_quantity' => 10.00,
        'opening_cost' => 50.00,
        'opening_warehouse_id' => $this->defaultWarehouse->id,
        'opening_balance_date' => '2026-10-01',
    ]);

    // Create opening batch and stock balance
    $service = app(InventoryService::class);
    $service->addStock(
        productId: $product->id,
        warehouseId: $this->defaultWarehouse->id,
        quantity: 10.00,
        costPrice: 50.00,
        referenceType: 'opening_balance',
        referenceId: $product->id,
        sourceType: 'opening_balance',
        sourceId: $product->id
    );

    // 1. Admin views edit page: sees editable opening inputs and badge
    $viewResponse = $this->actingAs($admin)->get(route('products.edit', $product));
    $viewResponse->assertStatus(200);
    $viewResponse->assertSee('صلاحية خاصة بمدير النظام');
    $viewResponse->assertSee('name="opening_quantity"', false);
    $viewResponse->assertSee('name="opening_cost"', false);

    // 2. Non-admin views edit page: sees read-only text and locked badge
    $warehouseUser = User::firstOrCreate(
        ['email' => 'warehouse-keeper@kayan-erp.com'],
        [
            'name' => 'أمين مخزن تجريبي',
            'password' => bcrypt('password'),
            'role' => User::ROLE_WAREHOUSE,
        ]
    );
    $nonAdminView = $this->actingAs($warehouseUser)->get(route('products.edit', $product));
    $nonAdminView->assertStatus(200);
    $nonAdminView->assertSee('مغلق ضد التعديل حفاظاً على سلامة المخزون');
    $nonAdminView->assertDontSee('name="opening_quantity"', false);

    // 3. Admin updates opening stock from 10 to 25 @ 48 EGP
    $updateResponse = $this->actingAs($admin)->put(route('products.update', $product), [
        'sku' => 'ADMIN-OPENING-PROD',
        'name' => 'صنف تعديل رصيد افتتاحي',
        'category_id' => $this->category->id,
        'cost_price' => 50.00,
        'wholesale_price' => 70.00,
        'retail_price' => 90.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'opening_quantity' => 25.00,
        'opening_cost' => 48.00,
        'opening_warehouse_id' => $this->defaultWarehouse->id,
        'opening_balance_date' => '2026-10-05',
    ]);

    $updateResponse->assertRedirect(route('products.index'));

    $freshProduct = $product->fresh();
    expect((float) $freshProduct->opening_quantity)->toEqual(25.00)
        ->and((float) $freshProduct->opening_cost)->toEqual(48.00);

    // Verify batch was updated
    $batch = InventoryBatch::where('product_id', $product->id)->first();
    expect((float) $batch->initial_quantity)->toEqual(25.00)
        ->and((float) $batch->remaining_quantity)->toEqual(25.00)
        ->and((float) $batch->cost_price)->toEqual(48.00);

    // Verify stock balance was incremented to 25
    $balance = StockBalance::where('product_id', $product->id)->where('warehouse_id', $this->defaultWarehouse->id)->first();
    expect((float) $balance->quantity)->toEqual(25.00);
});

test('per-warehouse FIFO inventory correctly deducts from chosen warehouse and tracks running balance', function () {
    $service = app(InventoryService::class);

    $product = Product::create([
        'sku' => 'FIFO-TEST-PROD',
        'name' => 'طوق شعر زهور حرير',
        'category_id' => $this->category->id,
        'cost_price' => 10.00,
        'wholesale_price' => 16.00,
        'retail_price' => 25.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);

    // Batch 1 in WH-MAIN: 50 units @ 10 EGP
    $service->addStock(
        productId: $product->id,
        warehouseId: $this->defaultWarehouse->id,
        quantity: 50,
        costPrice: 10.00,
        notes: 'الدفعة الأولى المستودع الرئيسي'
    );

    // Batch 2 in WH-MAIN: 50 units @ 12 EGP
    $service->addStock(
        productId: $product->id,
        warehouseId: $this->defaultWarehouse->id,
        quantity: 50,
        costPrice: 12.00,
        notes: 'الدفعة الثانية المستودع الرئيسي'
    );

    // Batch in WH-BRANCH: 30 units @ 14 EGP
    $service->addStock(
        productId: $product->id,
        warehouseId: $this->branchWarehouse->id,
        quantity: 30,
        costPrice: 14.00,
        notes: 'دفعة مستودع الموسكي'
    );

    // Total stock before deduction: 50 + 50 + 30 = 130
    expect($product->fresh()->total_stock)->toEqual(130.00);

    // Deduct 70 units from WH-MAIN via FIFO (should consume 50 from batch 1 and 20 from batch 2)
    $deduction = $service->deductStockFIFO(
        productId: $product->id,
        warehouseId: $this->defaultWarehouse->id,
        quantity: 70,
        notes: 'صرف لتاجر جملة'
    );

    expect($deduction['total_deducted'])->toEqual(70.00);
    // Cost of goods sold: 50 * 10 + 20 * 12 = 500 + 240 = 740
    expect($deduction['total_cogs'])->toEqual(740.00);
    expect($deduction['movements'])->toHaveCount(2);

    // Check movements recorded running_balance
    expect((float) $deduction['movements'][0]->running_balance)->toEqual(80.00); // 130 - 50 = 80
    expect((float) $deduction['movements'][1]->running_balance)->toEqual(60.00); // 80 - 20 = 60

    // WH-MAIN balance: 100 - 70 = 30
    $mainBal = StockBalance::where('product_id', $product->id)->where('warehouse_id', $this->defaultWarehouse->id)->first();
    expect((float) $mainBal->quantity)->toEqual(30.00);

    // WH-BRANCH balance remains untouched at 30
    $branchBal = StockBalance::where('product_id', $product->id)->where('warehouse_id', $this->branchWarehouse->id)->first();
    expect((float) $branchBal->quantity)->toEqual(30.00);

    // Product total stock is now 60
    expect($product->fresh()->total_stock)->toEqual(60.00);
});

test('admin can update product name even when opening_balance_date has legacy 1970 date', function () {
    $admin = User::firstOrCreate(
        ['email' => 'admin-test@kayan-erp.com'],
        [
            'name' => 'مدير النظام التجريبي',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]
    );

    $product = Product::create([
        'sku' => 'LEGACY-DATE-PROD',
        'name' => 'صنف برصيد تاريخ قديم',
        'category_id' => $this->category->id,
        'cost_price' => 15.00,
        'wholesale_price' => 20.00,
        'retail_price' => 25.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'opening_quantity' => 10.00,
        'opening_cost' => 15.00,
        'opening_warehouse_id' => $this->defaultWarehouse->id,
        'opening_balance_date' => '1970-01-01',
    ]);

    $service = app(InventoryService::class);
    $service->addStock(
        productId: $product->id,
        warehouseId: $this->defaultWarehouse->id,
        quantity: 10.00,
        costPrice: 15.00,
        referenceType: 'opening_balance',
        referenceId: $product->id,
        sourceType: 'opening_balance',
        sourceId: $product->id
    );

    // Edit page loads without error and displays a valid fallback date instead of 1970
    $viewResponse = $this->actingAs($admin)->get(route('products.edit', $product));
    $viewResponse->assertStatus(200);
    $viewResponse->assertDontSee('value="1970-01-01"', false);

    // Admin updates product name with legacy opening_balance_date passed
    $updateResponse = $this->actingAs($admin)->put(route('products.update', $product), [
        'sku' => 'LEGACY-DATE-PROD',
        'name' => 'اسم صنف جديد ومعدل بنجاح',
        'category_id' => $this->category->id,
        'cost_price' => 15.00,
        'wholesale_price' => 20.00,
        'retail_price' => 25.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'opening_quantity' => 10.00,
        'opening_cost' => 15.00,
        'opening_warehouse_id' => $this->defaultWarehouse->id,
        'opening_balance_date' => '1970-01-01',
    ]);

    $updateResponse->assertRedirect(route('products.index'));
    expect($product->fresh()->name)->toBe('اسم صنف جديد ومعدل بنجاح');
});

test('admin updating product with dozen unit properly converts opening stock to base pieces and cost', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $product = Product::create([
        'sku' => 'DOZEN-TEST-001',
        'name' => 'فرشه صافي ومبرد قدم تست',
        'category_id' => $this->category->id,
        'cost_price' => 135.00,
        'wholesale_price' => 162.00,
        'retail_price' => 240.00,
        'distributor_price' => 150.00,
        'unit' => 'دستة',
        'pieces_per_package' => 12,
        'min_stock_alert' => 5,
        'opening_quantity' => 10.00,
        'opening_cost' => 135.00,
        'opening_warehouse_id' => $this->defaultWarehouse->id,
        'opening_balance_date' => '2026-10-15',
    ]);

    $service = app(InventoryService::class);
    $service->addStock(
        productId: $product->id,
        warehouseId: $this->defaultWarehouse->id,
        quantity: 120.00, // 10 dozens = 120 pieces
        costPrice: 11.25, // 135 / 12
        referenceType: 'opening_balance',
        referenceId: $product->id,
        sourceType: 'opening_balance',
        sourceId: $product->id
    );

    // Now admin updates product opening stock to 23 dozens at 135 EGP per dozen
    $response = $this->actingAs($admin)->put(route('products.update', $product), [
        'sku' => 'DOZEN-TEST-001',
        'name' => 'فرشه صافي ومبرد قدم تست',
        'category_id' => $this->category->id,
        'cost_price' => 135.00,
        'wholesale_price' => 162.00,
        'retail_price' => 240.00,
        'distributor_price' => 150.00,
        'unit' => 'دستة',
        'pieces_per_package' => 12,
        'min_stock_alert' => 5,
        'opening_quantity' => 23.00,
        'opening_cost' => 135.00,
        'opening_warehouse_id' => $this->defaultWarehouse->id,
        'opening_balance_date' => '2026-10-19',
    ]);

    $response->assertRedirect(route('products.index'));

    $product->refresh();
    // In product model, user inputs are retained
    expect((float) $product->opening_quantity)->toEqual(23.00)
        ->and((float) $product->opening_cost)->toEqual(135.00)
        ->and((float) $product->total_stock)->toEqual(276.00) // 23 * 12
        ->and($product->formatStockBreakdown())->toBe('23 دستة (276 قطعة)');

    // In batch, converted to base pieces and base piece cost
    $batch = InventoryBatch::where('product_id', $product->id)->first();
    expect($batch)->not->toBeNull()
        ->and((float) $batch->initial_quantity)->toEqual(276.00)
        ->and((float) $batch->remaining_quantity)->toEqual(276.00)
        ->and((float) $batch->cost_price)->toEqual(11.25)
        ->and((float) $batch->total_initial_cost)->toEqual(3105.00);

    // In stock balance
    $balance = StockBalance::where('product_id', $product->id)->where('warehouse_id', $this->defaultWarehouse->id)->first();
    expect($balance)->not->toBeNull()
        ->and((float) $balance->quantity)->toEqual(276.00);
});
