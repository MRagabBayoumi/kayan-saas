<?php

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\PrintTemplate;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PrintTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::disk('public')->put('company/logo.png', 'fake_logo_content');

    $this->user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($this->user);

    $this->warehouse = Warehouse::create([
        'name' => 'المستودع الرئيسي',
        'code' => 'WH-01',
        'is_active' => true,
    ]);

    $this->category = Category::create([
        'name' => 'إكسسوارات',
        'code' => 'ACC',
        'is_active' => true,
    ]);

    $this->company = CompanySetting::create([
        'company_name' => 'Kayan Company',
        'company_phone' => '01006543974',
        'logo_path' => 'company/logo.png',
        'program_status' => 'active',
    ]);
});

test('task 1: settings page print_templates tab renders correctly with templates list', function () {
    $response = $this->get(route('settings.index', ['tab' => 'print_templates']));

    $response->assertOk();
    $response->assertSee('قوالب الطباعة');
    $response->assertSee('نوع المستند');
    $response->assertSee('مقاس الورق ونوع الطابعة');
});

test('task 2: print templates correctly modify printed documents', function () {
    $service = app(PrintTemplateService::class);
    $service->seedDefaultTemplates();

    $template = PrintTemplate::forDocument(PrintTemplate::DOC_SALES_INVOICE)
        ->forFormat(PrintTemplate::FORMAT_A4)
        ->first();

    $template->update([
        'settings' => array_merge($template->settings, [
            'general' => array_merge($template->settings['general'] ?? [], [
                'custom_title' => 'فاتورة ضريبية رسمية مخصصة',
                'custom_subtitle' => 'نسخة الإدارة والعميل',
            ]),
        ]),
    ]);

    $customer = Customer::create([
        'name' => 'عميل تجريبي',
        'phone' => '01011111111',
    ]);

    $product = Product::create([
        'sku' => 'TEST-SKU-01',
        'name' => 'منتج تجريبي للتعديل',
        'category_id' => $this->category->id,
        'cost_price' => 50,
        'retail_price' => 100,
        'wholesale_price' => 80,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    $salesInvoice = SalesInvoice::create([
        'invoice_number' => 'INV-2026-9999',
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'user_id' => $this->user->id,
        'status' => 'completed',
        'subtotal' => 100,
        'total_amount' => 100,
        'paid_amount' => 100,
        'remaining_amount' => 0,
        'invoice_date' => now()->toDateString(),
    ]);

    SalesInvoiceItem::create([
        'sales_invoice_id' => $salesInvoice->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'base_quantity' => 1,
        'unit_price' => 100,
        'cost_price' => 50,
        'subtotal' => 100,
        'total' => 100,
    ]);

    $response = $this->get(route('sales.print', [$salesInvoice, 'format' => 'a4']));
    $response->assertOk();
    $response->assertSee('فاتورة ضريبية رسمية مخصصة');
    $response->assertSee('نسخة الإدارة والعميل');
});

test('task 3: product can be deleted when it has opening balance movements or unlinked', function () {
    $product = Product::create([
        'sku' => 'DEL-PROD-01',
        'name' => 'منتج قابل للحذف',
        'category_id' => $this->category->id,
        'cost_price' => 20,
        'retail_price' => 40,
        'wholesale_price' => 30,
        'unit' => 'قطعة',
        'min_stock_alert' => 2,
        'is_active' => true,
    ]);

    // Simulate opening balance stock movement
    StockMovement::create([
        'movement_number' => 'MV-OPENING-01',
        'type' => 'in',
        'product_id' => $product->id,
        'to_warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'running_balance' => 10,
        'unit_cost' => 20,
        'reference_type' => 'opening_balance',
        'reference_id' => $product->id,
    ]);

    $response = $this->delete(route('products.destroy', $product));

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('status', 'success');
    expect(Product::find($product->id))->toBeNull();
    expect(StockMovement::where('product_id', $product->id)->count())->toBe(0);
});

test('task 3: product deletion is prevented with friendly message when linked to sales invoices', function () {
    $customer = Customer::create([
        'name' => 'عميل حماية',
        'phone' => '01022222222',
    ]);

    $product = Product::create([
        'sku' => 'LOCK-PROD-01',
        'name' => 'منتج مرتبط بفاتورة',
        'category_id' => $this->category->id,
        'cost_price' => 20,
        'retail_price' => 40,
        'wholesale_price' => 30,
        'unit' => 'قطعة',
        'min_stock_alert' => 2,
        'is_active' => true,
    ]);

    $invoice = SalesInvoice::create([
        'invoice_number' => 'INV-LOCK-01',
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'user_id' => $this->user->id,
        'status' => 'completed',
        'subtotal' => 40,
        'total_amount' => 40,
        'paid_amount' => 40,
        'remaining_amount' => 0,
        'invoice_date' => now()->toDateString(),
    ]);

    SalesInvoiceItem::create([
        'sales_invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'base_quantity' => 1,
        'unit_price' => 40,
        'cost_price' => 20,
        'subtotal' => 40,
        'total' => 40,
    ]);

    $response = $this->delete(route('products.destroy', $product));

    $response->assertRedirect(route('products.index'));
    $response->assertSessionHas('status', 'error');
    expect(Product::find($product->id))->not->toBeNull();
});

test('task 4: company logo and branding appear in layout and topbar', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee($this->company->company_name);
    $response->assertSee('favicon.svg');
    $response->assertSee('company/logo.png');
});

test('task 5 & 6: low stock page has actions column linking to product show and print buttons', function () {
    $product = Product::create([
        'sku' => 'LOW-01',
        'name' => 'صنف ناقص الكمية',
        'category_id' => $this->category->id,
        'cost_price' => 15,
        'retail_price' => 30,
        'wholesale_price' => 25,
        'unit' => 'قطعة',
        'min_stock_alert' => 10,
        'is_active' => true,
    ]);

    $response = $this->get(route('inventory.low-stock'));

    $response->assertOk();
    // Task 5: Link to product page
    $response->assertSee(route('products.show', $product));
    $response->assertSee('عرض تفاصيل الصنف');

    // Task 6: Both print buttons (Python direct print + Browser print)
    $response->assertSee('data-action="python-direct-print"', false);
    $response->assertSee(route('inventory.low-stock.direct-print'));
    $response->assertSee(route('inventory.low-stock.print'));
    $response->assertSee('طباعة بايثون المباشرة');
    $response->assertSee('معاينة وطباعة');
});
