<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create([
        'name' => 'إكسسوارات شعر',
        'code' => 'HAIR',
        'is_active' => true,
    ]);

    $this->warehouse1 = Warehouse::create([
        'name' => 'المستودع الرئيسي (العاشر)',
        'code' => 'WH-MAIN',
        'is_default' => true,
        'is_active' => true,
    ]);

    $this->warehouse2 = Warehouse::create([
        'name' => 'مستودع العتبة للمبيعات',
        'code' => 'WH-ATABA',
        'is_default' => false,
        'is_active' => true,
    ]);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'name' => 'توكة لؤلؤ فاخرة سواريه',
        'sku' => 'TK-PRL-99',
        'barcode' => '62210009999',
        'cost_price' => 20.00,
        'wholesale_price' => 35.00,
        'retail_price' => 50.00,
        'unit' => 'دستة',
        'min_stock_alert' => 10,
        'is_active' => true,
    ]);

    $this->mv1 = StockMovement::create([
        'movement_number' => 'MV-20261010-0001',
        'type' => StockMovement::TYPE_IN,
        'product_id' => $this->product->id,
        'to_warehouse_id' => $this->warehouse1->id,
        'quantity' => 50.00,
        'running_balance' => 50.00,
        'unit_cost' => 20.00,
        'reference_type' => 'opening_balance',
        'notes' => 'رصيد افتتاحي معتمد',
    ]);
    $this->mv1->forceFill(['created_at' => '2026-10-10 10:00:00'])->save();

    $this->mv2 = StockMovement::create([
        'movement_number' => 'MV-20261011-0002',
        'type' => StockMovement::TYPE_OUT,
        'product_id' => $this->product->id,
        'from_warehouse_id' => $this->warehouse1->id,
        'quantity' => 15.00,
        'running_balance' => 35.00,
        'unit_cost' => 20.00,
        'reference_type' => 'sales_order',
        'notes' => 'صرف طلبيات جملة',
    ]);
    $this->mv2->forceFill(['created_at' => '2026-10-11 14:00:00'])->save();

    $this->mv3 = StockMovement::create([
        'movement_number' => 'MV-20261012-0003',
        'type' => StockMovement::TYPE_TRANSFER,
        'product_id' => $this->product->id,
        'from_warehouse_id' => $this->warehouse1->id,
        'to_warehouse_id' => $this->warehouse2->id,
        'quantity' => 10.00,
        'running_balance' => 35.00,
        'unit_cost' => 20.00,
        'reference_type' => 'warehouse_transfer',
        'notes' => 'تحويل لفرع العتبة',
    ]);
    $this->mv3->forceFill(['created_at' => '2026-10-12 16:00:00'])->save();

    // Set stock balances
    StockBalance::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse1->id,
        'quantity' => 25.00,
    ]);

    StockBalance::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse2->id,
        'quantity' => 10.00,
    ]);
});

test('1. print button appears clearly on product details page', function () {
    $response = $this->get(route('products.show', $this->product));

    $response->assertOk()
        ->assertSee(route('products.ledger.print', $this->product))
        ->assertSee('طباعة حركة الصنف')
        ->assertSee('سجل حركة المخزون للصنف');
});

test('2. print button appears clearly on product ledger page', function () {
    $response = $this->get(route('products.ledger', $this->product));

    $response->assertOk()
        ->assertSee(route('products.ledger.print', $this->product))
        ->assertSee('طباعة كشف الحركة');
});

test('3. successful opening of standalone item movement print document', function () {
    $response = $this->get(route('products.ledger.print', $this->product));

    $response->assertOk()
        ->assertViewIs('products.ledger-print')
        ->assertSee('طباعة كشف حركة الصنف: توكة لؤلؤ فاخرة سواريه')
        ->assertSee('كشف حركة الصنف المخزنية')
        ->assertSee('data-format="a4"', false);
});

test('4. prints correct product details and identification', function () {
    $response = $this->get(route('products.ledger.print', $this->product));

    $response->assertOk()
        ->assertSee('توكة لؤلؤ فاخرة سواريه')
        ->assertSee('TK-PRL-99')
        ->assertSee('62210009999')
        ->assertSee('إكسسوارات شعر')
        ->assertSee('دستة');
});

test('5. prints actual movement records and accurate running balances', function () {
    $response = $this->get(route('products.ledger.print', $this->product));

    $response->assertOk()
        ->assertSee('MV-20261010-0001')
        ->assertSee('+50.00')
        ->assertSee('MV-20261011-0002')
        ->assertSee('-15.00')
        ->assertSee('MV-20261012-0003')
        ->assertSee('رصيد افتتاحي معتمد')
        ->assertSee('صرف طلبيات جملة')
        ->assertSee('تحويل لفرع العتبة');
});

test('6. respects warehouse filter during print generation', function () {
    $response = $this->get(route('products.ledger.print', [
        'product' => $this->product,
        'warehouse_id' => $this->warehouse2->id,
    ]));

    $response->assertOk()
        ->assertSee('مستودع العتبة للمبيعات')
        ->assertSee('MV-20261012-0003')
        // mv1 was strictly to warehouse1
        ->assertDontSee('MV-20261010-0001');
});

test('7. respects movement type filter during print generation', function () {
    $response = $this->get(route('products.ledger.print', [
        'product' => $this->product,
        'type' => 'out',
    ]));

    $response->assertOk()
        ->assertSee('منصرف / مبيعات فقط')
        ->assertSee('MV-20261011-0002')
        ->assertDontSee('MV-20261010-0001')
        ->assertDontSee('MV-20261012-0003');
});

test('8. respects date range filters during print generation', function () {
    $response = $this->get(route('products.ledger.print', [
        'product' => $this->product,
        'date_from' => '2026-10-12',
        'date_to' => '2026-10-13',
    ]));

    $response->assertOk()
        ->assertSee('MV-20261012-0003')
        ->assertDontSee('MV-20261010-0001')
        ->assertDontSee('MV-20261011-0002');
});

test('9. supports A4, A5, and Thermal 80mm paper formats', function () {
    // A4
    $resA4 = $this->get(route('products.ledger.print', [$this->product, 'format' => 'a4']));
    $resA4->assertOk()->assertSee('data-format="a4"', false);

    // A5
    $resA5 = $this->get(route('products.ledger.print', [$this->product, 'format' => 'a5']));
    $resA5->assertOk()->assertSee('data-format="a5"', false);

    // Thermal 80mm
    $resThermal = $this->get(route('products.ledger.print', [$this->product, 'format' => 'thermal_80mm']));
    $resThermal->assertOk()->assertSee('data-format="thermal_80mm"', false);
});

test('10. non-existent product returns 404', function () {
    $response = $this->get('/products/999999/ledger/print');

    $response->assertNotFound();
});

test('11. isolation: does not leak movements belonging to other products', function () {
    $otherProduct = Product::create([
        'category_id' => $this->category->id,
        'name' => 'منتج مختلف تماماً وسري',
        'sku' => 'SECRET-999',
        'cost_price' => 10,
        'wholesale_price' => 15,
        'retail_price' => 20,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    StockMovement::create([
        'movement_number' => 'MV-SECRET-8888',
        'type' => StockMovement::TYPE_IN,
        'product_id' => $otherProduct->id,
        'to_warehouse_id' => $this->warehouse1->id,
        'quantity' => 999.00,
        'running_balance' => 999.00,
        'unit_cost' => 10.00,
        'reference_type' => 'secret_import',
        'notes' => 'شحنة سرية للمنتج الآخر',
    ]);

    $response = $this->get(route('products.ledger.print', $this->product));

    $response->assertOk()
        ->assertSee('TK-PRL-99')
        ->assertDontSee('MV-SECRET-8888')
        ->assertDontSee('منتج مختلف تماماً وسري');
});

test('12. read-only guarantee: print request never alters stock quantities or movements count', function () {
    $initialStockCount = StockMovement::where('product_id', $this->product->id)->count();
    $initialStockTotal = (float) $this->product->fresh()->total_stock;

    $this->get(route('products.ledger.print', $this->product))->assertOk();
    $this->get(route('products.ledger.print', [$this->product, 'format' => 'thermal_80mm']))->assertOk();

    expect(StockMovement::where('product_id', $this->product->id)->count())->toBe($initialStockCount)
        ->and((float) $this->product->fresh()->total_stock)->toBe($initialStockTotal);
});

test('13. prints complete unpaginated movement history matching filters', function () {
    // Screen pagination is 25 per page; verify print returns all 30 records without cutoff
    for ($i = 4; $i <= 30; $i++) {
        StockMovement::create([
            'movement_number' => "MV-BULK-TEST-{$i}",
            'type' => StockMovement::TYPE_IN,
            'product_id' => $this->product->id,
            'to_warehouse_id' => $this->warehouse1->id,
            'quantity' => 1.00,
            'running_balance' => 35.00 + $i,
            'unit_cost' => 20.00,
            'reference_type' => 'bulk_batch',
            'notes' => 'حركة مجمعة لاختبار الطباعة الكاملة',
        ]);
    }

    $response = $this->get(route('products.ledger.print', $this->product));

    $response->assertOk()
        ->assertSee('30 حركات مسجلة')
        ->assertSee('MV-BULK-TEST-4')
        ->assertSee('MV-BULK-TEST-30');
});

test('14. supports sorting chronologically (asc) or newest first (desc)', function () {
    $resAsc = $this->get(route('products.ledger.print', [$this->product, 'sort' => 'asc']));
    $resAsc->assertOk();

    $resDesc = $this->get(route('products.ledger.print', [$this->product, 'sort' => 'desc']));
    $resDesc->assertOk();
});
