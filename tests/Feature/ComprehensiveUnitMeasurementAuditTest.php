<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\StocktakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'مدير النظام',
        'email' => 'admin-units@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_ADMIN,
        'status' => 'active',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'مستودع الوحدات الرئيسي',
        'code' => 'WH-UNITS-01',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'مورد إكسسوارات الجملة',
        'phone' => '01011112222',
        'current_balance' => 0.00,
        'is_active' => true,
    ]);

    $this->customer = Customer::create([
        'name' => 'عميل قطاعي وجملة',
        'phone' => '01033334444',
        'current_balance' => 0.00,
        'is_active' => true,
    ]);

    $this->category = Category::create([
        'name' => 'إكسسوارات وبكلات',
        'code' => 'CAT-ACC',
        'is_active' => true,
    ]);

    // System Units
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

    $this->unitCarton = Unit::firstOrCreate(['name' => 'طرد 24'], [
        'code' => 'CTN24',
        'base_unit_id' => $this->unitPiece->id,
        'operator' => '*',
        'conversion_factor' => 24.0000,
        'is_active' => true,
        'is_default' => false,
    ]);

    // Product registered in Dozens (دستة)
    // 1 Dozen = 12 pieces
    // Cost per Dozen = 120 EGP -> Base cost per piece = 10 EGP
    // Retail per Dozen = 240 EGP -> Base retail per piece = 20 EGP
    $this->productDozen = Product::create([
        'sku' => 'SKU-DOZEN-PROD-01',
        'barcode' => '622000000001',
        'name' => 'توكة شعر مشجرة دستة',
        'category_id' => $this->category->id,
        'cost_price' => 120.00,
        'retail_price' => 240.00,
        'wholesale_price' => 180.00,
        'distributor_price' => 150.00,
        'unit' => 'دستة',
        'unit_factor' => 12.0,
        'pieces_per_package' => 12,
        'is_active' => true,
    ]);

    // Product registered in Pieces (قطعة)
    $this->productPiece = Product::create([
        'sku' => 'SKU-PIECE-PROD-01',
        'barcode' => '622000000002',
        'name' => 'مشط شعر حراري فردي',
        'category_id' => $this->category->id,
        'cost_price' => 15.00,
        'retail_price' => 25.00,
        'wholesale_price' => 20.00,
        'distributor_price' => 18.00,
        'unit' => 'قطعة',
        'unit_factor' => 1.0,
        'pieces_per_package' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});

test('audit 1: product model calculates base cost price and unit factors correctly', function () {
    // 1. Dozen Product
    expect($this->productDozen->unit_factor)->toEqual(12.0);
    expect($this->productDozen->package_factor)->toEqual(12.0);
    // Base cost price must be 120 / 12 = 10.00 EGP
    expect($this->productDozen->base_cost_price)->toEqual(10.00);

    // Dynamic price for unit factor 1 (single piece)
    expect($this->productDozen->getPriceForUnit('retail', 1.0))->toEqual(20.00);
    expect($this->productDozen->getPriceForUnit('wholesale', 1.0))->toEqual(15.00);

    // Stock breakdown formatting
    expect($this->productDozen->formatStockBreakdown(25))->toBe('2 دستة و 1 قطعة (25 قطعة)');
    expect($this->productDozen->formatStockBreakdown(24))->toBe('2 دستة (24 قطعة)');
    expect($this->productDozen->formatStockBreakdown(5))->toBe('5 قطعة');
    expect($this->productDozen->formatStockBreakdown(0))->toBe('0 دستة');

    // 2. Piece Product
    expect($this->productPiece->unit_factor)->toEqual(1.0);
    expect($this->productPiece->base_cost_price)->toEqual(15.00);
    expect($this->productPiece->formatStockBreakdown(10))->toBe('10 قطعة');
});

test('audit 2: purchase invoice converts dozen unit into base pieces and sets piece FIFO batch cost', function () {
    // Supplier purchase of 10 dozens of the dozen product
    // 10 dozens @ 120 EGP = 1,200 EGP
    // Total base pieces = 10 * 12 = 120 pieces
    // FIFO batch unit cost must be 1,200 / 120 = 10.00 EGP/piece
    $response = $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->productDozen->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0,
                'quantity' => 10,
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    $response->assertSessionHas('status', 'success');

    // Verify stock balance is in base pieces (120)
    $balance = StockBalance::where('product_id', $this->productDozen->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($balance)->not->toBeNull();
    expect((float) $balance->quantity)->toEqual(120.0000);

    // Verify inventory batch
    $batch = InventoryBatch::where('product_id', $this->productDozen->id)->first();
    expect($batch)->not->toBeNull();
    expect((float) $batch->initial_quantity)->toEqual(120.0000);
    expect((float) $batch->remaining_quantity)->toEqual(120.0000);
    expect((float) $batch->cost_price)->toEqual(10.0000); // 10 EGP per base piece!
    expect((float) $batch->total_initial_cost)->toEqual(1200.00);
});

test('audit 3: purchase invoice falls back to product unit factor when unit is omitted', function () {
    // If an item is posted without unit_id or conversion factor, it must adopt the product unit factor (12.0)
    $response = $this->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->productDozen->id,
                'quantity' => 2, // 2 dozens
                'unit_cost' => 120.00,
            ],
        ],
    ]);

    $response->assertSessionHas('status', 'success');

    $purchase = PurchaseInvoice::latest('id')->first();
    $item = $purchase->items->first();
    expect((float) $item->conversion_factor)->toEqual(12.0000);
    expect((float) $item->base_quantity)->toEqual(24.0000);

    $batch = InventoryBatch::where('purchase_invoice_id', $purchase->id)->first();
    expect((float) $batch->initial_quantity)->toEqual(24.0000);
    expect((float) $batch->cost_price)->toEqual(10.0000);
});

test('audit 4: sales invoice selling in dozens and pieces deducts correct base pieces and COGS', function () {
    // 1. Stock 120 base pieces @ 10 EGP
    $inventoryService = app(InventoryService::class);
    $inventoryService->addStock(
        productId: $this->productDozen->id,
        warehouseId: $this->warehouse->id,
        quantity: 120.0,
        costPrice: 10.00,
        retailPrice: 20.00,
        referenceType: 'opening_balance'
    );

    // 2. Sell 2 dozens (2 * 12 = 24 pieces) @ 240 EGP per dozen
    // Plus 3 individual pieces @ 20 EGP per piece
    // Total base pieces to deduct = 27 pieces
    // Subtotal = (2 * 240) + (3 * 20) = 480 + 60 = 540 EGP
    // COGS = 27 pieces * 10 EGP = 270 EGP
    // Total profit = 540 - 270 = 270 EGP
    $response = $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->productDozen->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0,
                'quantity' => 2,
                'unit_price' => 240.00,
            ],
            [
                'product_id' => $this->productDozen->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 3,
                'unit_price' => 20.00,
            ],
        ],
    ]);

    $response->assertSessionHas('status', 'success');

    $sale = SalesInvoice::latest('id')->first();
    expect((float) $sale->total_amount)->toEqual(540.00);
    expect((float) $sale->total_cogs)->toEqual(270.00);
    expect((float) $sale->total_profit)->toEqual(270.00);

    // Warehouse stock remaining: 120 - 27 = 93 pieces
    $balance = StockBalance::where('product_id', $this->productDozen->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $balance->quantity)->toEqual(93.0000);

    // Batch remaining
    $batch = InventoryBatch::where('product_id', $this->productDozen->id)->first();
    expect((float) $batch->remaining_quantity)->toEqual(93.0000);
    expect((float) $batch->accumulated_cogs_deducted)->toEqual(270.00);
});

test('audit 5: sales return in pieces calculates correct reversed COGS without dozen price inflation', function () {
    // 1. Stock 120 base pieces @ 10 EGP
    $inventoryService = app(InventoryService::class);
    $inventoryService->addStock(
        productId: $this->productDozen->id,
        warehouseId: $this->warehouse->id,
        quantity: 120.0,
        costPrice: 10.00,
        retailPrice: 20.00,
        referenceType: 'opening_balance'
    );

    // 2. Sell 1 dozen (12 pieces) @ 240 EGP
    $this->post(route('sales.store'), [
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->productDozen->id,
                'unit_id' => $this->unitDozen->id,
                'conversion_factor' => 12.0,
                'quantity' => 1,
                'unit_price' => 240.00,
            ],
        ],
    ]);

    $sale = SalesInvoice::latest('id')->first();
    $saleItem = $sale->items->first();

    // 3. Customer returns 0.5 dozen (6 pieces)
    // Refund price = 240 / 12 = 20 EGP/piece
    // Unit refund price = 240 for 0.5 dozen -> refund = 120 EGP
    // Reversed COGS must be 6 pieces * 10 EGP = 60 EGP (NOT 6 * 120 = 720 EGP!)
    $returnResponse = $this->post(route('sales-returns.store'), [
        'sales_invoice_id' => $sale->id,
        'return_number' => 'RET-UNITS-001',
        'return_date' => now()->toDateString(),
        'action' => 'post',
        'items' => [
            [
                'sales_invoice_item_id' => $saleItem->id,
                'quantity' => 0.5,
                'unit_refund_price' => 240.00,
            ],
        ],
    ]);

    $salesReturn = SalesReturn::latest('id')->first();
    $returnItem = $salesReturn->items->first();

    expect((float) $returnItem->base_quantity)->toEqual(6.0000);
    // Unit cost base must be 10.00 EGP per base piece!
    expect((float) $returnItem->unit_cost_base)->toEqual(10.0000);
    expect((float) $returnItem->total_cogs_reversed)->toEqual(60.00);

    $salesReturn->refresh();
    expect($salesReturn->status)->toBe(SalesReturn::STATUS_POSTED);
    expect((float) $salesReturn->total_cogs_returned)->toEqual(60.00);

    // Verify stock restored to 120 - 12 + 6 = 114 pieces
    $balance = StockBalance::where('product_id', $this->productDozen->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect((float) $balance->quantity)->toEqual(114.0000);
});

test('audit 6: sales return fallback without batch allocation uses base_cost_price', function () {
    // Create sales invoice item directly without batch allocations to trigger fallback
    $invoice = SalesInvoice::create([
        'invoice_number' => 'INV-TEST-FALLBACK',
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'status' => SalesInvoice::STATUS_POSTED,
        'subtotal' => 240.00,
        'total_amount' => 240.00,
    ]);

    $invItem = SalesInvoiceItem::create([
        'sales_invoice_id' => $invoice->id,
        'product_id' => $this->productDozen->id,
        'unit_id' => $this->unitDozen->id,
        'conversion_factor' => 12.0,
        'quantity' => 1,
        'base_quantity' => 12,
        'unit_price' => 240.00,
        'subtotal' => 240.00,
    ]);

    // Return 6 pieces (0.5 dozen)
    $response = $this->post(route('sales-returns.store'), [
        'sales_invoice_id' => $invoice->id,
        'return_number' => 'RET-UNITS-002',
        'return_date' => now()->toDateString(),
        'action' => 'draft',
        'items' => [
            [
                'sales_invoice_item_id' => $invItem->id,
                'quantity' => 0.5,
                'unit_refund_price' => 240.00,
            ],
        ],
    ]);

    $salesReturn = SalesReturn::where('return_number', 'RET-UNITS-002')->first();
    expect($salesReturn)->not->toBeNull();
    $returnItem = $salesReturn->items->first();

    // With our bug fix: unit_cost_base correctly uses base_cost_price (10 EGP), NOT cost_price (120 EGP)
    expect((float) $returnItem->unit_cost_base)->toEqual(10.0000);
    expect((float) $returnItem->total_cogs_reversed)->toEqual(60.00);
});

test('audit 7: inventory adjustment surplus uses base_cost_price for dozen items', function () {
    $inventoryService = app(InventoryService::class);

    // Initial stock: 12 pieces
    $inventoryService->addStock(
        productId: $this->productDozen->id,
        warehouseId: $this->warehouse->id,
        quantity: 12.0,
        costPrice: 10.00,
        retailPrice: 20.00,
        referenceType: 'opening_balance'
    );

    // Perform adjustment with surplus of 6 pieces (actual = 18 pieces)
    $result = $inventoryService->adjustStock(
        productId: $this->productDozen->id,
        warehouseId: $this->warehouse->id,
        actualQuantity: 18.0,
        reason: 'فائض جردي'
    );

    expect($result['status'])->toBe('increased');
    expect((float) $result['diff'])->toEqual(6.0);

    // The newly created surplus batch must have cost_price = base_cost_price (10.00), not dozen cost (120.00)
    $surplusBatch = $result['result']['batch'];
    expect((float) $surplusBatch->cost_price)->toEqual(10.0000);
    expect((float) $surplusBatch->total_initial_cost)->toEqual(60.00);
});

test('audit 8: stocktake service valuation uses base_cost_price preventing exaggerated valuations', function () {
    // 24 pieces of dozen product in warehouse
    StockBalance::create([
        'product_id' => $this->productDozen->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 24.00,
        'reserved_quantity' => 0.00,
    ]);

    $stocktakeService = app(StocktakeService::class);
    $sheet = $stocktakeService->getWarehouseStockBreakdown($this->warehouse);

    $row = collect($sheet['items'])->firstWhere('id', $this->productDozen->id);
    expect($row)->not->toBeNull();

    // 24 pieces @ 10 EGP base cost = 240.00 EGP valuation (NOT 24 * 120 = 2,880 EGP!)
    expect((float) $row['cost_price'])->toEqual(10.00);
    expect((float) $row['valuation'])->toEqual(240.00);
});

test('audit 9: inventory metrics calculates total valuation accurately across piece and dozen products', function () {
    $inventoryService = app(InventoryService::class);

    // Batch 1: Dozen product 24 pieces @ 10 EGP base cost = 240 EGP
    $inventoryService->addStock(
        productId: $this->productDozen->id,
        warehouseId: $this->warehouse->id,
        quantity: 24.0,
        costPrice: 10.00,
        retailPrice: 20.00,
        referenceType: 'opening_balance'
    );

    // Batch 2: Piece product 10 pieces @ 15 EGP base cost = 150 EGP
    $inventoryService->addStock(
        productId: $this->productPiece->id,
        warehouseId: $this->warehouse->id,
        quantity: 10.0,
        costPrice: 15.00,
        retailPrice: 25.00,
        referenceType: 'opening_balance'
    );

    $metrics = $inventoryService->getInventoryMetrics();
    // Total stock units = 24 + 10 = 34 pieces
    expect((float) $metrics['total_stock_units'])->toEqual(34.0);
    // Total inventory value = 240 + 150 = 390 EGP
    expect((float) $metrics['total_inventory_value'])->toEqual(390.00);
});
