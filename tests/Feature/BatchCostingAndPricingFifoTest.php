<?php

use App\Models\BatchCostMovement;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoiceItemBatch;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    $this->category = Category::create([
        'name' => 'إكسسوارات كيان الراقية',
        'code' => 'ACC-LUX',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-MAIN',
        'name' => 'المستودع الرئيسي',
        'location' => 'العتبة - القاهرة',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->customer = Customer::create([
        'name' => 'شركة النور للإكسسوارات',
        'phone' => '01012345678',
        'address' => 'شارع الأزهر، القاهرة',
        'is_active' => true,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'مصنع كوانزو للمجوهرات',
        'country' => 'الصين',
        'phone' => '+8613800138000',
        'is_active' => true,
    ]);

    $this->unitPiece = Unit::create([
        'name' => 'قطعة',
        'code' => 'PCS',
        'conversion_factor' => 1.0000,
        'is_default' => true,
        'is_active' => true,
    ]);

    $this->unitDozen = Unit::create([
        'name' => 'دستة',
        'code' => 'DZN',
        'conversion_factor' => 12.0000,
        'is_default' => false,
        'is_active' => true,
    ]);

    $this->inventoryService = app(InventoryService::class);
});

test('T1: opening balance creates distinct independent batch with cost and pricing tiers', function () {
    $response = $this->post(route('products.store'), [
        'sku' => 'HAIR-001',
        'name' => 'توكة شعر لؤلؤ أبيض',
        'category_id' => $this->category->id,
        'barcode' => '692000000001',
        'cost_price' => 10.00,
        'retail_price' => 20.00,
        'wholesale_price' => 16.00,
        'distributor_price' => 14.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'opening_warehouse_id' => $this->warehouse->id,
        'opening_quantity' => 50,
        'opening_cost' => 10.00,
        'opening_balance_date' => now()->toDateString(),
    ]);

    $response->assertRedirect(route('products.index'));

    $product = Product::where('sku', 'HAIR-001')->firstOrFail();

    $batch = InventoryBatch::where('product_id', $product->id)->firstOrFail();
    expect((float) $batch->initial_quantity)->toBe(50.0)
        ->and((float) $batch->remaining_quantity)->toBe(50.0)
        ->and((float) $batch->cost_price)->toBe(10.0000)
        ->and((float) $batch->retail_price)->toBe(20.0000)
        ->and((float) $batch->wholesale_price)->toBe(16.0000)
        ->and((float) $batch->distributor_price)->toBe(14.0000)
        ->and((float) $batch->total_initial_cost)->toBe(500.00)
        ->and($batch->hasPricing())->toBeTrue();

    // Verify ledger record
    $ledger = BatchCostMovement::where('batch_id', $batch->id)->firstOrFail();
    expect($ledger->movement_type)->toBe('opening_in')
        ->and((float) $ledger->quantity_base)->toBe(50.0)
        ->and((float) $ledger->unit_cost_base)->toBe(10.0000)
        ->and((float) $ledger->total_cost)->toBe(500.00);
});

test('T2: purchase invoice creates second batch with independent cost and pricing without mutating older batch', function () {
    $product = Product::create([
        'sku' => 'CLIP-002',
        'name' => 'مشبك شعر معدني ذهبي',
        'category_id' => $this->category->id,
        'barcode' => '692000000002',
        'cost_price' => 15.00,
        'retail_price' => 30.00,
        'wholesale_price' => 25.00,
        'distributor_price' => 22.00,
        'unit' => 'قطعة',
    ]);

    // Batch 1: Opening stock (10 pieces @ 15 EGP)
    $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 10,
        costPrice: 15.00,
        referenceType: 'opening_balance',
        notes: 'الرصيد الافتتاحي',
        retailPrice: 30.00,
        wholesalePrice: 25.00,
        distributorPrice: 22.00,
        totalInitialCost: 150.00
    );

    // Batch 2: Purchase Invoice receipt (20 pieces @ 18 EGP with higher pricing tiers)
    $purchase = PurchaseInvoice::create([
        'invoice_number' => 'PINV-TEST-002',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'status' => 'draft',
        'subtotal' => 360.00,
        'total_amount' => 360.00,
        'created_by' => $this->admin->id,
    ]);

    PurchaseInvoiceItem::create([
        'purchase_invoice_id' => $purchase->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'unit_cost' => 18.00,
        'subtotal' => 360.00,
    ]);

    // Post the purchase invoice
    $response = $this->post(route('purchases.post', $purchase));
    $response->assertSessionHas('status', 'success');

    $batches = InventoryBatch::where('product_id', $product->id)->orderBy('id')->get();
    expect($batches)->toHaveCount(2);

    $batch1 = $batches[0];
    $batch2 = $batches[1];

    // Older batch remains completely untouched
    expect((float) $batch1->cost_price)->toBe(15.0000)
        ->and((float) $batch1->retail_price)->toBe(30.0000)
        ->and((float) $batch1->wholesale_price)->toBe(25.0000)
        ->and((float) $batch1->distributor_price)->toBe(22.0000);

    // New batch has its own independent cost and pricing
    expect((float) $batch2->cost_price)->toBe(18.0000)
        ->and((float) $batch2->remaining_quantity)->toBe(20.0);
});

test('T3: dozen sold individually preserves exact cost conservation invariant down to the penny', function () {
    $product = Product::create([
        'sku' => 'RING-DOZEN-003',
        'name' => 'خاتم كريستال مستورد',
        'category_id' => $this->category->id,
        'barcode' => '692000000003',
        'cost_price' => 8.3333,
        'retail_price' => 15.00,
        'wholesale_price' => 12.00,
        'distributor_price' => 10.00,
        'unit' => 'قطعة',
    ]);

    // Purchased 1 dozen = 12 pieces @ 100.00 EGP total initial cost
    $res = $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 12,
        costPrice: 100.00 / 12, // 8.333333...
        referenceType: 'purchase_invoice',
        notes: 'شراء دستة بـ 100 جنيه',
        retailPrice: 15.00,
        wholesalePrice: 12.00,
        distributorPrice: 10.00,
        totalInitialCost: 100.00
    );

    $batch = $res['batch'];

    expect((float) $batch->total_initial_cost)->toBe(100.00)
        ->and((float) $batch->remaining_quantity)->toBe(12.0);

    $totalExpensedCogs = 0.00;
    $cogsHistory = [];

    // Sell 1 piece individually 12 times
    for ($i = 1; $i <= 12; $i++) {
        $result = $this->inventoryService->deductStockFIFOWithPricing(
            productId: $product->id,
            warehouseId: $this->warehouse->id,
            baseQuantity: 1.0,
            priceTier: 'retail',
            notes: "بيع قطعة رقم {$i}",
            referenceType: 'test_sale',
            referenceId: $i,
            userId: $this->admin->id
        );

        $lineCogs = (float) $result['total_cogs'];
        $cogsHistory[] = $lineCogs;
        $totalExpensedCogs += $lineCogs;
    }

    // Pieces 1 through 11 each cost round(100/12, 2) = 8.33 EGP
    for ($i = 0; $i < 11; $i++) {
        expect($cogsHistory[$i])->toBe(8.33);
    }

    // Piece 12 must absorb the remaining penny discrepancy: 100.00 - (11 * 8.33) = 8.37 EGP!
    expect($cogsHistory[11])->toBe(8.37);

    // Sum of all 12 COGS entries strictly and exactly matches 100.00 EGP!
    expect(round($totalExpensedCogs, 2))->toBe(100.00);

    // Batch must now be exhausted
    $batch->refresh();
    expect((float) $batch->remaining_quantity)->toBe(0.0)
        ->and($batch->is_exhausted)->toBeTrue()
        ->and((float) $batch->accumulated_cogs_deducted)->toBe(100.00);
});

test('T4: consolidated sales line item correctly splits across multiple batches internally', function () {
    $product = Product::create([
        'sku' => 'EARRING-004',
        'name' => 'حلق فضي سواريه',
        'category_id' => $this->category->id,
        'cost_price' => 10.00,
        'retail_price' => 20.00,
        'wholesale_price' => 16.00,
        'distributor_price' => 14.00,
        'unit' => 'قطعة',
    ]);

    // Batch 1: 10 pieces @ cost 10.00 (retail 20.00)
    $r1 = $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 10,
        costPrice: 10.00,
        retailPrice: 20.00,
        wholesalePrice: 16.00,
        distributorPrice: 14.00,
        totalInitialCost: 100.00
    );
    $b1 = $r1['batch'];

    // Batch 2: 20 pieces @ cost 12.00 (retail 25.00)
    $r2 = $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 20,
        costPrice: 12.00,
        retailPrice: 25.00,
        wholesalePrice: 20.00,
        distributorPrice: 18.00,
        totalInitialCost: 240.00
    );
    $b2 = $r2['batch'];

    // Create Sales Invoice for 15 pieces (should take 10 from Batch 1 and 5 from Batch 2)
    $sale = SalesInvoice::create([
        'invoice_number' => 'SINV-TEST-004',
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'status' => SalesInvoice::STATUS_DRAFT,
        'subtotal' => 300.00, // 15 * 20.00
        'total_amount' => 300.00,
        'created_by' => $this->admin->id,
    ]);

    $item = SalesInvoiceItem::create([
        'sales_invoice_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 15,
        'base_quantity' => 15,
        'conversion_factor' => 1.0,
        'unit_price' => 20.00,
        'line_discount' => 0.00,
        'subtotal' => 300.00,
    ]);

    // Post the sale invoice
    $response = $this->post(route('sales.post', $sale));
    $response->assertSessionHas('status', 'success');

    $item->refresh();
    $sale->refresh();

    // Check consolidated item: total COGS = (10 * 10.00) + (5 * 12.00) = 160.00 EGP
    expect((float) $item->total_cogs)->toBe(160.00)
        ->and((float) $item->total_profit)->toBe(140.00)
        ->and((float) $sale->total_cogs)->toBe(160.00)
        ->and((float) $sale->total_profit)->toBe(140.00);

    // Check batch allocations: exactly 2 allocations
    $allocations = SalesInvoiceItemBatch::where('sales_invoice_item_id', $item->id)->orderBy('id')->get();
    expect($allocations)->toHaveCount(2);

    expect($allocations[0]->inventory_batch_id)->toBe($b1->id)
        ->and((float) $allocations[0]->quantity_base)->toBe(10.0)
        ->and((float) $allocations[0]->unit_cost_base)->toBe(10.0000)
        ->and((float) $allocations[0]->total_cogs)->toBe(100.00);

    expect($allocations[1]->inventory_batch_id)->toBe($b2->id)
        ->and((float) $allocations[1]->quantity_base)->toBe(5.0)
        ->and((float) $allocations[1]->unit_cost_base)->toBe(12.0000)
        ->and((float) $allocations[1]->total_cogs)->toBe(60.00);

    // Batch 1 is depleted; Batch 2 has 15 remaining
    $b1->refresh();
    $b2->refresh();
    expect((float) $b1->remaining_quantity)->toBe(0.0)
        ->and($b1->is_exhausted)->toBeTrue()
        ->and((float) $b2->remaining_quantity)->toBe(15.0)
        ->and($b2->is_exhausted)->toBeFalse();
});

test('T5: price tiers (retail, wholesale, distributor) are loaded from batch', function () {
    $product = Product::create([
        'sku' => 'NECK-005',
        'name' => 'سلسلة لؤلؤ شوكر',
        'category_id' => $this->category->id,
        'cost_price' => 50.00,
        'retail_price' => 100.00,
        'wholesale_price' => 80.00,
        'distributor_price' => 70.00,
        'unit' => 'قطعة',
    ]);

    $res = $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 30,
        costPrice: 50.00,
        retailPrice: 100.00,
        wholesalePrice: 80.00,
        distributorPrice: 70.00,
        totalInitialCost: 1500.00
    );
    $batch = $res['batch'];

    expect($batch->getPriceForTier('retail'))->toBe(100.00)
        ->and($batch->getPriceForTier('wholesale'))->toBe(80.00)
        ->and($batch->getPriceForTier('distributor'))->toBe(70.00);
});

test('T6: unit conversions (dozens vs pieces) deduct correct base units from batch', function () {
    $product = Product::create([
        'sku' => 'BROOCH-006',
        'name' => 'بروش فراشة كريستال',
        'category_id' => $this->category->id,
        'cost_price' => 5.00,
        'retail_price' => 10.00,
        'wholesale_price' => 8.00,
        'distributor_price' => 7.00,
        'unit' => 'قطعة',
    ]);

    $res = $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 24, // 2 dozens
        costPrice: 5.00,
        retailPrice: 10.00,
        wholesalePrice: 8.00,
        distributorPrice: 7.00,
        totalInitialCost: 120.00
    );
    $batch = $res['batch'];

    // Sell 1.5 dozens = 18 pieces
    $result = $this->inventoryService->deductStockFIFOWithPricing(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        baseQuantity: 18.0,
        priceTier: 'retail',
        notes: 'بيع دستة ونصف',
        referenceType: 'sales_invoice',
        referenceId: 99,
        userId: $this->admin->id
    );

    expect((float) $result['total_cogs'])->toBe(90.00); // 18 * 5.00

    $batch->refresh();
    expect((float) $batch->remaining_quantity)->toBe(6.0); // 24 - 18 = 6 pieces left
});

test('T7: voiding a posted invoice restores batches, creates compensating movements, and enforces double-reversal guard', function () {
    $product = Product::create([
        'sku' => 'ANKLET-007',
        'name' => 'خلخال بوهيمي',
        'category_id' => $this->category->id,
        'cost_price' => 20.00,
        'retail_price' => 40.00,
        'wholesale_price' => 32.00,
        'distributor_price' => 28.00,
        'unit' => 'قطعة',
    ]);

    $res = $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 10,
        costPrice: 20.00,
        retailPrice: 40.00,
        wholesalePrice: 32.00,
        distributorPrice: 28.00,
        totalInitialCost: 200.00
    );
    $batch = $res['batch'];

    // Create and post sales invoice for 6 pieces
    $sale = SalesInvoice::create([
        'invoice_number' => 'SINV-TEST-007',
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'status' => SalesInvoice::STATUS_DRAFT,
        'subtotal' => 240.00,
        'total_amount' => 240.00,
        'created_by' => $this->admin->id,
    ]);

    SalesInvoiceItem::create([
        'sales_invoice_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 6,
        'base_quantity' => 6,
        'conversion_factor' => 1.0,
        'unit_price' => 40.00,
        'subtotal' => 240.00,
    ]);

    $this->post(route('sales.post', $sale));

    $batch->refresh();
    expect((float) $batch->remaining_quantity)->toBe(4.0);

    // Now void the sales invoice
    $response = $this->post(route('sales.void', $sale), [
        'void_reason' => 'إلغاء الطلب من قبل العميل قبل الشحن',
    ]);

    $response->assertSessionHas('status', 'success');

    $sale->refresh();
    $batch->refresh();

    expect($sale->status)->toBe(SalesInvoice::STATUS_VOIDED)
        ->and($sale->void_reason)->toBe('إلغاء الطلب من قبل العميل قبل الشحن')
        ->and((float) $batch->remaining_quantity)->toBe(10.0); // Restored!

    // Check allocations marked as reversed
    $alloc = SalesInvoiceItemBatch::where('sales_invoice_item_id', $sale->items->first()->id)->firstOrFail();
    expect($alloc->is_reversed)->toBeTrue()
        ->and($alloc->reversed_at)->not->toBeNull();

    // Check compensating ledger row
    $compensatingLedger = BatchCostMovement::where('batch_id', $batch->id)
        ->where('movement_type', 'sale_cancellation_reversal')
        ->firstOrFail();

    expect((float) $compensatingLedger->quantity_base)->toBe(6.0)
        ->and((float) $compensatingLedger->total_cost)->toBe(120.00);

    // Double-reversal guard: voiding again must be rejected
    $doubleVoidResponse = $this->post(route('sales.void', $sale), [
        'void_reason' => 'محاولة إلغاء مكررة',
    ]);

    $doubleVoidResponse->assertSessionHas('error');
});

test('T8: Option A prevents sales from unpriced legacy batches', function () {
    $product = Product::create([
        'sku' => 'LEGACY-008',
        'name' => 'صنف قديم غير مسعر',
        'category_id' => $this->category->id,
        'cost_price' => 10.00,
        'retail_price' => 20.00,
        'unit' => 'قطعة',
    ]);

    StockBalance::create([
        'product_id' => $product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
    ]);

    // Create legacy batch without pricing tiers (retail_price = null)
    $legacyBatch = InventoryBatch::create([
        'batch_number' => 'BATCH-LEGACY-008',
        'product_id' => $product->id,
        'warehouse_id' => $this->warehouse->id,
        'initial_quantity' => 10,
        'remaining_quantity' => 10,
        'cost_price' => 10.00,
        'retail_price' => null, // Option A: unpriced
        'wholesale_price' => null,
        'distributor_price' => null,
        'received_at' => now(),
        'is_exhausted' => false,
    ]);

    expect($legacyBatch->hasPricing())->toBeFalse();

    // Expect InvalidArgumentException when attempting to sell without pricing
    expect(function () use ($product) {
        $this->inventoryService->deductStockFIFOWithPricing(
            productId: $product->id,
            warehouseId: $this->warehouse->id,
            baseQuantity: 1.0,
            priceTier: 'retail',
            allowUnpricedBatches: false
        );
    })->toThrow(InvalidArgumentException::class, 'ليس لها أسعار بيع معتمدة');
});

test('T9: full HTTP sales workflow (create, list, show, and print views)', function () {
    $product = Product::create([
        'sku' => 'HTTP-009',
        'name' => 'ساعة نسائية ماسية',
        'category_id' => $this->category->id,
        'cost_price' => 100.00,
        'retail_price' => 250.00,
        'wholesale_price' => 200.00,
        'distributor_price' => 180.00,
        'unit' => 'قطعة',
    ]);

    $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 10,
        costPrice: 100.00,
        retailPrice: 250.00,
        wholesalePrice: 200.00,
        distributorPrice: 180.00,
        totalInitialCost: 1000.00
    );

    // 1. Visit create page
    $this->get(route('sales.create'))->assertStatus(200);

    // 2. Store new invoice
    $response = $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'wholesale',
        'shipping_cost' => 30.00,
        'discount_type' => 'fixed',
        'discount_value' => 20.00,
        'notes' => 'تجربة الفاتورة الكاملة',
        'action' => 'post', // direct posting
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 2,
                'unit_price' => 200.00,
                'line_discount' => 10.00,
                'notes' => 'بند تجريبي',
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->firstOrFail();
    $response->assertRedirect(route('sales.show', $invoice));

    // Verify invoice fields:
    // Subtotal: (2 * 200) - 10 = 390.00
    // Invoice discount: 20.00
    // Shipping: 30.00
    // Total Amount: 390 - 20 + 30 = 400.00
    // Total COGS: 2 * 100 = 200.00
    // Total Profit: (390 - 200) - 20 = 170.00
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED)
        ->and((float) $invoice->subtotal)->toBe(390.00)
        ->and((float) $invoice->total_amount)->toBe(400.00)
        ->and((float) $invoice->total_cogs)->toBe(200.00)
        ->and((float) $invoice->total_profit)->toBe(170.00);

    // 3. Visit show page
    $this->get(route('sales.show', $invoice))
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number)
        ->assertSee('شركة النور للإكسسوارات');

    // 4. Visit print views (A4 and thermal)
    $this->get(route('sales.print', [$invoice, 'format' => 'a4']))
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number);

    $this->get(route('sales.print', [$invoice, 'format' => 'thermal_80mm']))
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number);

    // 5. Visit index page
    $this->get(route('sales.index'))
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number);
});

test('T10: sales return creates restitution batch with original COGS and audit records', function () {
    $product = Product::create([
        'sku' => 'RETURN-010',
        'name' => 'خاتم سولتير ذهبي',
        'category_id' => $this->category->id,
        'cost_price' => 50.00,
        'retail_price' => 100.00,
        'wholesale_price' => 80.00,
        'distributor_price' => 70.00,
        'unit' => 'قطعة',
    ]);

    $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 5,
        costPrice: 50.00,
        retailPrice: 100.00,
        wholesalePrice: 80.00,
        distributorPrice: 70.00,
        totalInitialCost: 250.00
    );

    // Deduct 2 pieces for sale
    $this->inventoryService->deductStockFIFOWithPricing(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        baseQuantity: 2.0,
        priceTier: 'retail',
        notes: 'بيع خاتمين',
        referenceType: 'sales_invoice',
        referenceId: 101,
        userId: $this->admin->id
    );

    // Customer returns 1 piece: restore it via addStock with referenceType sales_return
    $returnResult = $this->inventoryService->addStock(
        productId: $product->id,
        warehouseId: $this->warehouse->id,
        quantity: 1.0,
        costPrice: 50.00, // exact original unit cost
        referenceType: 'sales_return',
        notes: 'مرتجع مبيعات فاتورة 101',
        retailPrice: 100.00,
        wholesalePrice: 80.00,
        distributorPrice: 70.00,
        totalInitialCost: 50.00
    );

    $restitutionBatch = $returnResult['batch'];
    expect((float) $restitutionBatch->remaining_quantity)->toBe(1.0)
        ->and((float) $restitutionBatch->cost_price)->toBe(50.0000)
        ->and((float) $restitutionBatch->total_initial_cost)->toBe(50.00);

    // Check ledger recorded as sale_return_in
    $returnLedger = BatchCostMovement::where('batch_id', $restitutionBatch->id)->firstOrFail();
    expect($returnLedger->movement_type)->toBe('sale_return_in')
        ->and((float) $returnLedger->quantity_base)->toBe(1.0)
        ->and((float) $returnLedger->total_cost)->toBe(50.00);
});

test('sales create screen renders successfully and auto-creates default cash customer when none exists', function () {
    Customer::query()->forceDelete();
    expect(Customer::count())->toBe(0);

    $response = $this->get(route('sales.create'));

    $response->assertOk();
    $response->assertSee('عميل نقدي');
    $response->assertSee('شاشة نقاط البيع وفاتورة المبيعات (POS)');

    $cashCustomer = Customer::where('name', 'عميل نقدي')->first();
    expect($cashCustomer)->not->toBeNull()
        ->and($cashCustomer->phone)->toBe('0000000000')
        ->and($cashCustomer->pricing_tier)->toBe('retail');
});
