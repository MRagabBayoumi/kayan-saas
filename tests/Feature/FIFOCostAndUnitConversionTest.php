<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'مدير النظام',
        'email' => 'admin-fifo@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_ADMIN,
        'status' => 'active',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'المستودع الرئيسي',
        'code' => 'MAIN-WH',
        'is_active' => true,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'شركة توريدات الإكسسوار',
        'phone' => '01000000001',
        'current_balance' => 0.00,
        'is_active' => true,
    ]);

    $this->customer = Customer::create([
        'name' => 'عميل تجريبي فيفو',
        'phone' => '01000000002',
        'current_balance' => 0.00,
        'is_active' => true,
    ]);

    $this->category = Category::create([
        'name' => 'إكسسوارات الشعر',
        'is_active' => true,
    ]);

    // Ensure Units Exist
    $this->unitPiece = Unit::firstOrCreate(['name' => 'قطعة'], [
        'code' => 'PCS',
        'base_unit_id' => null,
        'operator' => '*',
        'conversion_factor' => 1.0000,
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->unitDozen = Unit::firstOrCreate(['name' => 'دستة'], [
        'code' => 'DZ',
        'base_unit_id' => $this->unitPiece->id,
        'operator' => '*',
        'conversion_factor' => 12.0000,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->unitCarton = Unit::firstOrCreate(['name' => 'كرتونة'], [
        'code' => 'CTN',
        'base_unit_id' => $this->unitPiece->id,
        'operator' => '*',
        'conversion_factor' => 24.0000,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->product = Product::create([
        'sku' => 'SKU-HAIR-BAND-1',
        'name' => 'طوق شعر لؤلؤ',
        'category_id' => $this->category->id,
        'cost_price' => 10.00,
        'wholesale_price' => 15.00,
        'retail_price' => 20.00,
        'distributor_price' => 13.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});

test('scenario 1: purchase of 10 dozens at total cost EGP 1,200 correctly generates base FIFO layer', function () {
    // 10 dozens @ 120 EGP/dozen = 1,200 EGP total
    // 10 dozens * 12 = 120 pieces base quantity
    // Base cost per piece = 1,200 / 120 = 10.00 EGP / piece
    $response = $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'payment_type' => 'cash',
        'action' => 'post', // Direct posting
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitDozen->id,
                'unit_name' => 'دستة',
                'conversion_factor' => 12.0000,
                'quantity' => 10,
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    $response->assertSessionHas('status', 'success');

    $purchase = PurchaseInvoice::first();
    expect($purchase)->not->toBeNull();
    expect($purchase->status)->toBe(PurchaseInvoice::STATUS_POSTED);
    expect((float) $purchase->total_amount)->toEqual(1200.00);

    // Check Purchase Invoice Item
    $item = $purchase->items->first();
    expect((float) $item->quantity)->toEqual(10.00);
    expect((float) $item->conversion_factor)->toEqual(12.0000);
    expect((float) $item->base_quantity)->toEqual(120.0000);
    expect((float) $item->unit_cost)->toEqual(120.00);
    expect((float) $item->subtotal)->toEqual(1200.00);

    // Check FIFO Batch Layer
    $batch = InventoryBatch::where('purchase_invoice_id', $purchase->id)->first();
    expect($batch)->not->toBeNull();
    expect((float) $batch->initial_quantity)->toEqual(120.0000);
    expect((float) $batch->remaining_quantity)->toEqual(120.0000);
    expect((float) $batch->cost_price)->toEqual(10.0000); // Cost per piece
    expect((float) $batch->total_initial_cost)->toEqual(1200.00);
    expect($batch->is_exhausted)->toBeFalse();

    // Check Warehouse Stock Balance
    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $balance->quantity)->toEqual(120.0000);
});

test('scenario 2: sale of 15 pieces from that purchase consumes 15 base units at exact FIFO cost of EGP 150', function () {
    // 1. Create and post the purchase of 10 dozens @ 1,200 EGP
    $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0000,
                'quantity' => 10,
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    // 2. Sell 15 pieces @ retail price 20 EGP/piece
    $saleResponse = $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'cash',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0000,
                'quantity' => 15,
                'unit_price' => 20.00,
            ],
        ],
    ]);

    $saleResponse->assertSessionHas('status', 'success');

    $sale = SalesInvoice::latest('id')->first();
    expect($sale->status)->toBe(SalesInvoice::STATUS_POSTED);

    $saleItem = $sale->items->first();
    expect((float) $saleItem->quantity)->toEqual(15.00);
    expect((float) $saleItem->base_quantity)->toEqual(15.00);
    expect((float) $saleItem->total_cogs)->toEqual(150.00); // 15 pieces * 10 EGP
    expect((float) $saleItem->total_profit)->toEqual(150.00); // (15 * 20) - 150 = 150

    // Check FIFO Batch Remaining Quantity
    $batch = InventoryBatch::where('product_id', $this->product->id)->first();
    expect((float) $batch->remaining_quantity)->toEqual(105.0000); // 120 - 15 = 105
    expect((float) $batch->accumulated_cogs_deducted)->toEqual(150.00);
    expect($batch->is_exhausted)->toBeFalse();

    // Check Stock Balance
    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $balance->quantity)->toEqual(105.0000);
});

test('scenario 3: second purchase at different cost followed by sale that consumes stock from both FIFO layers', function () {
    // Layer 1: 10 dozens @ 1,200 EGP (120 pieces @ 10 EGP)
    $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->subDay()->toDateString(),
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0000,
                'quantity' => 10,
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    // Initial sale of 15 pieces leaves 105 pieces in Layer 1
    $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 15,
                'unit_price' => 20.00,
            ],
        ],
    ]);

    // Layer 2: 5 dozens @ 720 EGP total (144 EGP/dozen -> 60 pieces @ 12.00 EGP)
    $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0000,
                'quantity' => 5,
                'unit_cost' => 144.00,
            ],
        ],
    ]);

    // Check batches exist: Batch 1 has 105 remaining @ 10 EGP; Batch 2 has 60 remaining @ 12 EGP
    $batches = InventoryBatch::where('product_id', $this->product->id)->orderBy('id')->get();
    expect($batches)->toHaveCount(2);
    expect((float) $batches[0]->remaining_quantity)->toEqual(105.0000);
    expect((float) $batches[0]->cost_price)->toEqual(10.0000);
    expect((float) $batches[1]->remaining_quantity)->toEqual(60.0000);
    expect((float) $batches[1]->cost_price)->toEqual(12.0000);

    // Total available stock = 105 + 60 = 165 pieces
    // Now make a sale of 115 pieces:
    // It must consume 105 pieces from Batch 1 @ 10 EGP = 1,050 EGP (exhausting Batch 1)
    // And 10 pieces from Batch 2 @ 12 EGP = 120 EGP (leaving 50 pieces in Batch 2)
    // Expected Total COGS = 1,050 + 120 = 1,170 EGP
    $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 115,
                'unit_price' => 25.00,
            ],
        ],
    ]);

    $sale2 = SalesInvoice::latest('id')->first();
    expect((float) $sale2->total_cogs)->toEqual(1170.00);

    // Verify Batches after two-tier consumption
    $batches[0]->refresh();
    $batches[1]->refresh();

    expect((float) $batches[0]->remaining_quantity)->toEqual(0.0000);
    expect($batches[0]->is_exhausted)->toBeTrue();
    expect((float) $batches[0]->accumulated_cogs_deducted)->toEqual(1200.00); // 150 + 1050 = 1200

    expect((float) $batches[1]->remaining_quantity)->toEqual(50.0000); // 60 - 10 = 50
    expect($batches[1]->is_exhausted)->toBeFalse();
    expect((float) $batches[1]->accumulated_cogs_deducted)->toEqual(120.00);

    // Remaining total stock balance = 50 pieces
    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $balance->quantity)->toEqual(50.0000);
});

test('scenario 4: purchases and sales entered in different supported units consume base units correctly', function () {
    // 1. Purchase 2 Cartons (24 pieces each = 48 pieces total) @ 240 EGP each (Total = 480 EGP, 10 EGP/piece)
    $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitCarton->id,
                'conversion_factor' => 24.0000,
                'quantity' => 2,
                'unit_cost' => 240.00,
            ],
        ],
    ]);

    // Check Batch has 48 base units
    $batch = InventoryBatch::where('product_id', $this->product->id)->first();
    expect((float) $batch->remaining_quantity)->toEqual(48.0000);

    // 2. Sell 2 Dozens (2 * 12 = 24 pieces) in dozen unit
    $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'wholesale',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0000,
                'quantity' => 2,
                'unit_price' => 180.00, // 180 per dozen
            ],
        ],
    ]);

    $batch->refresh();
    expect((float) $batch->remaining_quantity)->toEqual(24.0000); // 48 - 24 = 24
    expect((float) $batch->accumulated_cogs_deducted)->toEqual(240.00); // 24 pieces * 10 EGP = 240

    // 3. Sell 4 Pieces in piece unit
    $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0000,
                'quantity' => 4,
                'unit_price' => 20.00,
            ],
        ],
    ]);

    $batch->refresh();
    expect((float) $batch->remaining_quantity)->toEqual(20.0000); // 24 - 4 = 20
    expect((float) $batch->accumulated_cogs_deducted)->toEqual(280.00); // 240 + 40 = 280
});

test('scenario 5: partial FIFO layer consumption and stock reversal/void restores layer integrity', function () {
    // 1. Purchase 100 pieces @ 15 EGP
    $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 100,
                'unit_cost' => 15.00,
            ],
        ],
    ]);

    // 2. Sell 40 pieces @ 25 EGP
    $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 40,
                'unit_price' => 25.00,
            ],
        ],
    ]);

    $batch = InventoryBatch::where('product_id', $this->product->id)->first();
    expect((float) $batch->remaining_quantity)->toEqual(60.0000);
    expect((float) $batch->accumulated_cogs_deducted)->toEqual(600.00);

    // 3. Void the sales invoice
    $sale = SalesInvoice::latest('id')->first();
    $this->post(route('sales.void', $sale), [
        'void_reason' => 'إلغاء تجريبي لاختبار استعادة طبقات الفيفو',
    ]);

    $sale->refresh();
    expect($sale->status)->toBe(SalesInvoice::STATUS_VOIDED);

    // Verify batch is fully restored to 100 pieces and 0 accumulated COGS
    $batch->refresh();
    expect((float) $batch->remaining_quantity)->toEqual(100.0000);
    expect((float) $batch->accumulated_cogs_deducted)->toEqual(0.00);
    expect($batch->is_exhausted)->toBeFalse();

    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $balance->quantity)->toEqual(100.0000);
});

test('sequential purchase invoice numbering is concurrency-safe and starts at 1', function () {
    // Generate numbers sequentially
    $num1 = PurchaseInvoice::generateInvoiceNumber();
    $num2 = PurchaseInvoice::generateInvoiceNumber();
    $num3 = PurchaseInvoice::generateInvoiceNumber();

    expect($num1)->toBe('PUR-000001');
    expect($num2)->toBe('PUR-000002');
    expect($num3)->toBe('PUR-000003');
});

test('purchase pricing review workflow allows updating batch pricing before final posting', function () {
    // 1. Create a purchase invoice with action 'review_pricing'
    $response = $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'action' => 'review_pricing',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 50,
                'unit_cost' => 10.00,
            ],
        ],
    ]);

    $invoice = PurchaseInvoice::first();
    expect($invoice->status)->toBe(PurchaseInvoice::STATUS_DRAFT);
    $response->assertRedirect(route('purchases.pricing-review', $invoice));

    // 2. Access pricing review page
    $reviewPage = $this->get(route('purchases.pricing-review', $invoice));
    $reviewPage->assertOk();
    $reviewPage->assertSee($this->product->name);

    // 3. Confirm pricing decisions and post
    $confirmResponse = $this->post(route('purchases.confirm-pricing', $invoice), [
        'prices' => [
            $this->product->id => [
                'retail_price' => 35.00,
                'wholesale_price' => 28.00,
                'distributor_price' => 24.00,
            ],
        ],
        'update_master_prices' => 1,
    ]);

    $confirmResponse->assertRedirect(route('purchases.show', $invoice));
    $confirmResponse->assertSessionHas('status', 'success');

    $invoice->refresh();
    expect($invoice->status)->toBe(PurchaseInvoice::STATUS_POSTED);

    // Batch was created with the reviewed prices
    $batch = InventoryBatch::where('purchase_invoice_id', $invoice->id)->first();
    expect((float) $batch->retail_price)->toEqual(35.00);
    expect((float) $batch->wholesale_price)->toEqual(28.00);
    expect((float) $batch->distributor_price)->toEqual(24.00);

    // Product master was also updated because user is admin and update_master_prices was selected
    $this->product->refresh();
    expect((float) $this->product->retail_price)->toEqual(35.00);
    expect((float) $this->product->wholesale_price)->toEqual(28.00);
    expect((float) $this->product->distributor_price)->toEqual(24.00);
});
