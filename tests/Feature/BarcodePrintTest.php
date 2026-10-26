<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\BarcodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('barcode generator service produces crisp Code 128 SVG for numeric codes', function () {
    $service = new BarcodeGeneratorService;
    $svg = $service->generateSvg('202610110001');

    expect($svg)->toBeString()
        ->toContain('<svg')
        ->toContain('shape-rendering="crispEdges"')
        ->toContain('<rect')
        ->toContain('fill="#000000"');
});

test('barcode generator service produces complete 38x25mm default label SVG with 304x200 viewBox', function () {
    $service = new BarcodeGeneratorService;
    $labelSvg = $service->generateCompleteLabelSvg('سلسلة لؤلؤ مذهب', '202610110001', 'KY-NCK-001');

    expect($labelSvg)->toBeString()
        ->toContain('viewBox="0 0 304 200"')
        ->toContain('width="38mm"')
        ->toContain('height="25mm"')
        ->toContain('سلسلة لؤلؤ مذهب')
        ->toContain('ITEM-NO: KY-NCK-001');
});

test('barcode generator service produces complete 38x15mm fallback label SVG with 304x120 viewBox', function () {
    $service = new BarcodeGeneratorService;
    $labelSvg = $service->generateCompleteLabelSvg('سلسلة لؤلؤ مذهب', '202610110001', 'KY-NCK-001', 38, 15);

    expect($labelSvg)->toBeString()
        ->toContain('viewBox="0 0 304 120"')
        ->toContain('width="38mm"')
        ->toContain('height="15mm"')
        ->toContain('سلسلة لؤلؤ مذهب')
        ->toContain('ITEM-NO: KY-NCK-001');
});

test('barcode generator service produces valid Code 128 SVG for alphanumeric SKUs', function () {
    $service = new BarcodeGeneratorService;
    $svg = $service->generateSvg('KY-26-0001');

    expect($svg)->toBeString()
        ->toContain('<svg')
        ->toContain('shape-rendering="crispEdges"')
        ->toContain('<rect');
});

test('barcode generator service falls back to SKU when product barcode is null', function () {
    $category = Category::create(['name' => 'خواتم']);
    $product = Product::create([
        'sku' => 'KY-RNG-099',
        'name' => 'خاتم مطلي فضة عيار 925',
        'category_id' => $category->id,
        'barcode' => null,
        'cost_price' => 15.00,
        'wholesale_price' => 30.00,
        'retail_price' => 50.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    $service = new BarcodeGeneratorService;
    expect($service->getBarcodeValue($product))->toBe('KY-RNG-099');
});

test('single product print label view renders with 38x25mm specifications by default', function () {
    $category = Category::create(['name' => 'أساور']);
    $product = Product::create([
        'sku' => 'KY-BRC-101',
        'name' => 'إسورة لؤلؤ زراعي أبيض',
        'category_id' => $category->id,
        'barcode' => '202610111234',
        'cost_price' => 20.00,
        'wholesale_price' => 35.00,
        'retail_price' => 60.00,
        'unit' => 'دستة',
        'min_stock_alert' => 10,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.print-label', [
        'product' => $product,
        'quantity' => 3,
    ]));

    $response->assertOk();
    $response->assertViewIs('products.print-label');
    $response->assertSee('38×25 مم');
    $response->assertSee('Xprinter XP-233B');
    $response->assertSee('إسورة لؤلؤ زراعي أبيض');
    $response->assertSee('ITEM-NO: KY-BRC-101');
    $response->assertSee('الملصقات: 3 ملصق');
});

test('single product print label view renders with 38x15mm specifications when requested', function () {
    $category = Category::create(['name' => 'أساور']);
    $product = Product::create([
        'sku' => 'KY-BRC-102',
        'name' => 'إسورة كريستال أحمر',
        'category_id' => $category->id,
        'barcode' => '202610111235',
        'cost_price' => 22.00,
        'wholesale_price' => 38.00,
        'retail_price' => 65.00,
        'unit' => 'دستة',
        'min_stock_alert' => 10,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.print-label', [
        'product' => $product,
        'quantity' => 2,
        'size' => '38x15',
    ]));

    $response->assertOk();
    $response->assertViewIs('products.print-label');
    $response->assertSee('38×15 مم');
    $response->assertSee('Xprinter XP-233B');
});

test('single product print label view ignores unsupported orientation parameter and renders standard clean layout', function () {
    $category = Category::create(['name' => 'مناكير']);
    $product = Product::create([
        'sku' => 'COCO-1',
        'name' => 'اكلادور أحمر',
        'category_id' => $category->id,
        'barcode' => '202610119876',
        'cost_price' => 10.00,
        'wholesale_price' => 15.00,
        'retail_price' => 25.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.print-label', [
        'product' => $product,
        'quantity' => 1,
        'size' => '38x15',
        'orientation' => '90',
    ]));

    $response->assertOk();
    $response->assertViewIs('products.print-label');
    $response->assertDontSee('data-orientation', false);
    $response->assertDontSee('rotate(90)', false);
    $response->assertDontSee('rotate(270)', false);
    $response->assertSee('38×15 مم');
});

test('single product print label view triggers autoprint when requested', function () {
    $category = Category::create(['name' => 'أقراط']);
    $product = Product::create([
        'sku' => 'KY-EAR-555',
        'name' => 'حلق كريستال سواروفسكي',
        'category_id' => $category->id,
        'barcode' => '202610115555',
        'cost_price' => 18.00,
        'wholesale_price' => 32.00,
        'retail_price' => 55.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 8,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.print-label', [
        'product' => $product,
        'quantity' => 1,
        'autoprint' => 1,
    ]));

    $response->assertOk();
    $response->assertSee('data-auto-print="true"', false);
});

test('batch print labels view handles multiple products with individual quantities', function () {
    $category = Category::create(['name' => 'توك وبكلات']);
    $product1 = Product::create([
        'sku' => 'KY-TCK-001',
        'name' => 'توكة شعر لؤلؤ كوري',
        'category_id' => $category->id,
        'cost_price' => 5.00,
        'wholesale_price' => 10.00,
        'retail_price' => 18.00,
        'unit' => 'كرتونة',
        'min_stock_alert' => 20,
        'is_active' => true,
    ]);

    $product2 = Product::create([
        'sku' => 'KY-TCK-002',
        'name' => 'بكلة شعر فيونكة ستان',
        'category_id' => $category->id,
        'cost_price' => 8.00,
        'wholesale_price' => 15.00,
        'retail_price' => 25.00,
        'unit' => 'دستة',
        'min_stock_alert' => 15,
        'is_active' => true,
    ]);

    $response = $this->post(route('products.print-batch'), [
        'products' => [
            $product1->id => 2,
            $product2->id => 3,
        ],
    ]);

    $response->assertOk();
    $response->assertViewIs('products.print-label');
    $response->assertSee('توكة شعر لؤلؤ كوري');
    $response->assertSee('بكلة شعر فيونكة ستان');
    $response->assertSee('ITEM-NO: KY-TCK-001');
    $response->assertSee('ITEM-NO: KY-TCK-002');
    $response->assertSee('الملصقات: 5 ملصق');
});

test('batch print labels redirects with error when empty list is provided', function () {
    $response = $this->post(route('products.print-batch'), [
        'products' => [],
    ]);

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('status', 'error');
});

test('product barcode SVG endpoint returns JSON payload with SVG barcode', function () {
    $category = Category::create(['name' => 'سلاسل']);
    $product = Product::create([
        'sku' => 'KY-CHN-888',
        'name' => 'سلسلة تيتانيوم مقاومة للصدأ',
        'category_id' => $category->id,
        'barcode' => '202610118888',
        'cost_price' => 40.00,
        'wholesale_price' => 70.00,
        'retail_price' => 110.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 12,
        'is_active' => true,
    ]);

    $response = $this->getJson(route('products.barcode-svg', $product));

    $response->assertOk();
    $response->assertJson([
        'product_id' => $product->id,
        'sku' => 'KY-CHN-888',
        'name' => 'سلسلة تيتانيوم مقاومة للصدأ',
        'barcode' => '202610118888',
    ]);
    expect($response->json('svg'))->toContain('<svg');
});

test('product show view displays direct print button and stepper', function () {
    $category = Category::create(['name' => 'أطقم إكسسوارات']);
    $product = Product::create([
        'sku' => 'KY-SET-999',
        'name' => 'طقم إكسسوار زركون 4 قطع',
        'category_id' => $category->id,
        'cost_price' => 120.00,
        'wholesale_price' => 190.00,
        'retail_price' => 280.00,
        'unit' => 'طقم',
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.show', $product));

    $response->assertOk();
    $response->assertSee('طباعة ملصق الباركود');
    $response->assertSee('data-action="direct-print-show"', false);
    $response->assertSee('printNotificationToast');
    $response->assertSee('38×25 مم');
});

test('products index view includes row double click navigation and direct print button', function () {
    $category = Category::create(['name' => 'سلاسل ذهبية']);
    $product = Product::create([
        'sku' => 'KY-GLD-123',
        'name' => 'سلسلة ذهب صيني راقية',
        'category_id' => $category->id,
        'barcode' => '202610119999',
        'cost_price' => 30.00,
        'wholesale_price' => 55.00,
        'retail_price' => 85.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 10,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('product-row-clickable');
    $response->assertSee(route('products.show', $product));
    $response->assertSee('data-action="direct-print-row"', false);
    $response->assertSee('printNotificationToast');
});
