<?php

use App\Models\Category;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create([
        'name' => 'إكسسوارات شعر',
        'code' => 'HAIR',
        'is_active' => true,
    ]);

    $this->warehouse1 = Warehouse::create([
        'code' => 'WH-MAIN',
        'name' => 'المستودع الرئيسي',
        'location' => 'العتبة',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->warehouse2 = Warehouse::create([
        'code' => 'WH-BRANCH',
        'name' => 'مستودع الموسكي',
        'location' => 'الموسكي',
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->product = Product::create([
        'sku' => 'HAIR-CLIP-01',
        'name' => 'بنسة شعر كورية لؤلؤ',
        'category_id' => $this->category->id,
        'barcode' => '201234567890',
        'cost_price' => 15.00,
        'wholesale_price' => 25.00,
        'retail_price' => 35.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 10,
        'is_active' => true,
    ]);
});

test('warehouses page loads successfully and displays warehouses', function () {
    $response = $this->get(route('warehouses.index'));

    $response->assertStatus(200);
    $response->assertSee('المستودعات والمخازن');
    $response->assertSee('المستودع الرئيسي');
    $response->assertSee('مستودع الموسكي');
    $response->assertSee('WH-MAIN');
});

test('warehouse can be created successfully', function () {
    $response = $this->post(route('warehouses.store'), [
        'code' => 'WH-ZAMALEK',
        'name' => 'مستودع الزمالك',
        'location' => 'شارع 26 يوليو',
        'notes' => 'مستودع تجريبي',
    ]);

    $response->assertRedirect(route('warehouses.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('warehouses', [
        'code' => 'WH-ZAMALEK',
        'name' => 'مستودع الزمالك',
    ]);
});

test('warehouse duplicate code is rejected', function () {
    $response = $this->post(route('warehouses.store'), [
        'code' => 'WH-MAIN', // duplicate
        'name' => 'مستودع مكرر',
    ]);

    $response->assertSessionHasErrors(['code']);
});

test('warehouse can be updated', function () {
    $response = $this->put(route('warehouses.update', $this->warehouse2), [
        'code' => 'WH-BRANCH-UPDATED',
        'name' => 'مستودع الموسكي المطور',
        'location' => 'شارع المعز',
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('warehouses.index'));
    $this->assertDatabaseHas('warehouses', [
        'id' => $this->warehouse2->id,
        'code' => 'WH-BRANCH-UPDATED',
        'name' => 'مستودع الموسكي المطور',
    ]);
});

test('cannot delete default warehouse or warehouse with stock', function () {
    // Attempt deleting default warehouse
    $resDefault = $this->delete(route('warehouses.destroy', $this->warehouse1));
    $resDefault->assertRedirect(route('warehouses.index'));
    $resDefault->assertSessionHas('error');
    $this->assertDatabaseHas('warehouses', ['id' => $this->warehouse1->id]);

    // Add stock to warehouse 2, then try deleting it
    StockBalance::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse2->id,
        'quantity' => 20,
    ]);

    $resStock = $this->delete(route('warehouses.destroy', $this->warehouse2));
    $resStock->assertRedirect(route('warehouses.index'));
    $resStock->assertSessionHas('error');
    $this->assertDatabaseHas('warehouses', ['id' => $this->warehouse2->id]);
});

test('inventory overview page loads successfully', function () {
    $response = $this->get(route('inventory.index'));

    $response->assertStatus(200);
    $response->assertSee('نظرة عامة على المخزون');
    $response->assertSee('بنسة شعر كورية لؤلؤ');
    $response->assertSee('HAIR-CLIP-01');
    $response->assertSee('تسجيل حركة مخزون جديدة');
});

test('adding stock creates inventory batch, updates balance, and records movement', function () {
    $service = app(InventoryService::class);

    $result = $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 100,
        costPrice: 12.50,
        notes: 'شحنة تجريبية واردة'
    );

    expect($result['batch'])->toBeInstanceOf(InventoryBatch::class)
        ->and((float) $result['batch']->remaining_quantity)->toEqual(100.00)
        ->and((float) $result['batch']->cost_price)->toEqual(12.50)
        ->and((float) $result['balance']->quantity)->toEqual(100.00)
        ->and($result['movement']->type)->toBe(StockMovement::TYPE_IN);

    $this->assertDatabaseHas('stock_balances', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse1->id,
        'quantity' => 100.00,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $this->product->id,
        'to_warehouse_id' => $this->warehouse1->id,
        'type' => 'in',
        'quantity' => 100.00,
    ]);
});

test('FIFO stock deduction consumes oldest batch first', function () {
    $service = app(InventoryService::class);

    // Batch 1: Received yesterday at 10.00 EGP
    $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 50,
        costPrice: 10.00,
        notes: 'الدفعة الأولى الأقدم',
        receivedAt: Carbon::now()->subDays(2)
    );

    // Batch 2: Received today at 14.00 EGP
    $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 50,
        costPrice: 14.00,
        notes: 'الدفعة الثانية الأحدث',
        receivedAt: Carbon::now()
    );

    // Deduct 30 units (should come entirely from Batch 1)
    $deduction = $service->deductStockFIFO(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 30,
        notes: 'صرف تجريبي 30 قطعة'
    );

    expect($deduction['total_deducted'])->toBe(30.00)
        ->and($deduction['total_cogs'])->toBe(300.00); // 30 * 10.00

    // Batch 1 remaining should be 20
    $batches = InventoryBatch::where('product_id', $this->product->id)->orderBy('received_at', 'asc')->get();
    expect((float) $batches[0]->remaining_quantity)->toBe(20.00)
        ->and($batches[0]->is_exhausted)->toBeFalse();

    // Batch 2 remaining should still be 50
    expect((float) $batches[1]->remaining_quantity)->toBe(50.00);

    // Total warehouse stock should be 70
    $balance = StockBalance::where('product_id', $this->product->id)->where('warehouse_id', $this->warehouse1->id)->first();
    expect((float) $balance->quantity)->toBe(70.00);
});

test('FIFO stock deduction splits across multiple batches when needed', function () {
    $service = app(InventoryService::class);

    // Batch 1: 20 units @ 10 EGP
    $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 20,
        costPrice: 10.00,
        receivedAt: Carbon::now()->subDays(2)
    );

    // Batch 2: 30 units @ 15 EGP
    $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 30,
        costPrice: 15.00,
        receivedAt: Carbon::now()
    );

    // Deduct 35 units: 20 from Batch 1 (exhausting it) + 15 from Batch 2
    $deduction = $service->deductStockFIFO(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 35
    );

    // Expected COGS: (20 * 10) + (15 * 15) = 200 + 225 = 425
    expect($deduction['total_deducted'])->toBe(35.00)
        ->and($deduction['total_cogs'])->toBe(425.00)
        ->and(count($deduction['movements']))->toBe(2);

    $batches = InventoryBatch::where('product_id', $this->product->id)->orderBy('received_at', 'asc')->get();
    expect($batches[0]->is_exhausted)->toBeTrue()
        ->and((float) $batches[0]->remaining_quantity)->toBe(0.00)
        ->and($batches[1]->is_exhausted)->toBeFalse()
        ->and((float) $batches[1]->remaining_quantity)->toBe(15.00);
});

test('stock deduction fails when requested quantity exceeds available stock', function () {
    $service = app(InventoryService::class);

    $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 10
    );

    expect(fn () => $service->deductStockFIFO(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 15
    ))->toThrow(InvalidArgumentException::class);
});

test('stock transfer moves stock between warehouses atomically', function () {
    $service = app(InventoryService::class);

    // Add 50 units to Warehouse 1
    $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 50,
        costPrice: 12.00
    );

    // Transfer 20 units from Warehouse 1 to Warehouse 2
    $result = $service->transferStock(
        productId: $this->product->id,
        fromWarehouseId: $this->warehouse1->id,
        toWarehouseId: $this->warehouse2->id,
        quantity: 20,
        notes: 'تحويل بين المستودعات'
    );

    expect($result['transfer_movement'])->toBeInstanceOf(StockMovement::class)
        ->and($result['transfer_movement']->type)->toBe(StockMovement::TYPE_TRANSFER);

    $wh1Balance = StockBalance::where('product_id', $this->product->id)->where('warehouse_id', $this->warehouse1->id)->first();
    $wh2Balance = StockBalance::where('product_id', $this->product->id)->where('warehouse_id', $this->warehouse2->id)->first();

    expect((float) $wh1Balance->quantity)->toBe(30.00)
        ->and((float) $wh2Balance->quantity)->toBe(20.00);
});

test('inventory adjustment handles surplus and deficit correctly', function () {
    $service = app(InventoryService::class);

    // 1. Initial 40 units
    $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 40
    );

    // 2. Adjust count to 45 (surplus +5)
    $adjUp = $service->adjustStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        actualQuantity: 45,
        reason: 'فروق جرد إيجابية'
    );

    expect($adjUp['status'])->toBe('increased');
    $b1 = StockBalance::where('product_id', $this->product->id)->where('warehouse_id', $this->warehouse1->id)->first();
    expect((float) $b1->quantity)->toBe(45.00);

    // 3. Adjust count to 38 (deficit -7)
    $adjDown = $service->adjustStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        actualQuantity: 38,
        reason: 'تلفيات وبضاعة هالكة'
    );

    expect($adjDown['status'])->toBe('decreased');
    $b2 = StockBalance::where('product_id', $this->product->id)->where('warehouse_id', $this->warehouse1->id)->first();
    expect((float) $b2->quantity)->toBe(38.00);
});

test('low stock screen displays items below min_stock_alert', function () {
    // Current stock is 0 (<= 10), so it should appear
    $response = $this->get(route('inventory.low-stock'));

    $response->assertStatus(200);
    $response->assertSee('نواقص وتنبيهات المخزون');
    $response->assertSee('HAIR-CLIP-01');
    $response->assertSee('نفد بالكامل');

    // Add 100 units (stock becomes 100 > 10)
    $service = app(InventoryService::class);
    $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 100
    );

    $responseAfter = $this->get(route('inventory.low-stock'));
    $responseAfter->assertStatus(200);
    $responseAfter->assertSee('لا توجد نواقص في المخزون حالياً');
});

test('movements audit log displays and filters movements', function () {
    $service = app(InventoryService::class);
    $service->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        quantity: 25,
        notes: 'حركة إيداع موثقة'
    );

    $response = $this->get(route('inventory.movements'));
    $response->assertStatus(200);
    $response->assertSee('سجل حركات المخزون');
    $response->assertSee('إيداع / وارد');
    $response->assertSee('حركة إيداع موثقة');
});

test('storeMovement endpoint handles web requests properly', function () {
    $response = $this->post(route('inventory.movements.store'), [
        'movement_type' => 'in',
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse1->id,
        'quantity' => 60,
        'unit_cost' => 14.50,
        'notes' => 'وارد عبر نموذج الويب',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('stock_balances', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse1->id,
        'quantity' => 60.00,
    ]);
});
