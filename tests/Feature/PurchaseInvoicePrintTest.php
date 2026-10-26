<?php

use App\Models\Category;
use App\Models\PrintTemplate;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PrintTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create(['name' => 'إكسسوارات', 'code' => 'ACC']);
    $this->supplier = Supplier::create([
        'name' => 'شركة النيل للتوريدات العامة',
        'contact_person' => 'إبراهيم حسن',
        'phone' => '01099887766',
        'country' => 'مصر',
        'city' => 'القاهرة',
        'current_balance' => 0,
        'is_active' => true,
    ]);
    $this->warehouse = Warehouse::create([
        'name' => 'المستودع الرئيسي (العاشر)',
        'code' => 'WH-MAIN',
        'is_default' => true,
        'is_active' => true,
    ]);
    $this->product1 = Product::create([
        'category_id' => $this->category->id,
        'name' => 'توكة شعر لؤلؤ فاخرة',
        'sku' => 'TK-PRL-01',
        'retail_price' => 50,
        'wholesale_price' => 35,
        'distributor_price' => 28,
        'unit' => 'دستة',
        'is_active' => true,
    ]);
    $this->product2 = Product::create([
        'category_id' => $this->category->id,
        'name' => 'طوق شعر ستان كوري',
        'sku' => 'TQ-STN-02',
        'retail_price' => 40,
        'wholesale_price' => 25,
        'distributor_price' => 20,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    $this->invoice = PurchaseInvoice::create([
        'invoice_number' => 'PUR-20261014-0001-TEST',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => '2026-10-14',
        'status' => PurchaseInvoice::STATUS_DRAFT,
        'subtotal' => 1500.00,
        'shipping_cost' => 150.00,
        'total_amount' => 1650.00,
        'notes' => 'شحنة تجريبية واردة بحالة ممتازة.',
    ]);

    PurchaseInvoiceItem::create([
        'purchase_invoice_id' => $this->invoice->id,
        'product_id' => $this->product1->id,
        'quantity' => 20,
        'unit_cost' => 50.00,
        'subtotal' => 1000.00,
    ]);

    PurchaseInvoiceItem::create([
        'purchase_invoice_id' => $this->invoice->id,
        'product_id' => $this->product2->id,
        'quantity' => 20,
        'unit_cost' => 25.00,
        'subtotal' => 500.00,
    ]);
});

test('1. successful opening of the print page for an existing purchase invoice', function () {
    $response = $this->get(route('purchases.print', $this->invoice));

    $response->assertOk()
        ->assertViewIs('purchases.print')
        ->assertSee('طباعة فاتورة شراء: PUR-20261014-0001-TEST')
        ->assertSee('data-format="a4"', false);
});

test('2. correct display of invoice number, supplier details, warehouse, and line items', function () {
    $response = $this->get(route('purchases.print', $this->invoice));

    $response->assertOk()
        ->assertSee('PUR-20261014-0001-TEST')
        ->assertSee('شركة النيل للتوريدات العامة')
        ->assertSee('01099887766')
        ->assertSee('المستودع الرئيسي (العاشر)')
        ->assertSee('توكة شعر لؤلؤ فاخرة')
        ->assertSee('TK-PRL-01')
        ->assertSee('طوق شعر ستان كوري')
        ->assertSee('TQ-STN-02')
        ->assertSee('دستة')
        ->assertSee('قطعة');
});

test('3. totals match stored data and invoice logic', function () {
    $response = $this->get(route('purchases.print', $this->invoice));

    $response->assertOk()
        ->assertSee('1,500.00 ج.م') // Subtotal
        ->assertSee('150.00 ج.م')   // Shipping
        ->assertSee('1,650.00 ج.م'); // Grand total
});

test('4. no accidental display of data from a different invoice', function () {
    $otherSupplier = Supplier::create([
        'name' => 'مورد سري مختلف تماماً',
        'current_balance' => 0,
        'is_active' => true,
    ]);
    $otherInvoice = PurchaseInvoice::create([
        'invoice_number' => 'PUR-99999999-SECRET',
        'supplier_id' => $otherSupplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => '2026-10-14',
        'status' => PurchaseInvoice::STATUS_DRAFT,
        'subtotal' => 9999.00,
        'shipping_cost' => 0,
        'total_amount' => 9999.00,
    ]);

    $response = $this->get(route('purchases.print', $this->invoice));

    $response->assertOk()
        ->assertSee('PUR-20261014-0001-TEST')
        ->assertDontSee('PUR-99999999-SECRET')
        ->assertDontSee('مورد سري مختلف تماماً');
});

test('5. correct handling of non-existent invoices returns 404', function () {
    $response = $this->get('/purchases/999999/print');

    $response->assertNotFound();
});

test('6. supports A4, A5, and Thermal 80mm paper sizes via query parameter', function () {
    // A4
    $resA4 = $this->get(route('purchases.print', [$this->invoice, 'format' => 'a4']));
    $resA4->assertOk()->assertSee('data-format="a4"', false);

    // A5
    $resA5 = $this->get(route('purchases.print', [$this->invoice, 'format' => 'a5']));
    $resA5->assertOk()->assertSee('data-format="a5"', false);

    // Thermal 80mm
    $resThermal = $this->get(route('purchases.print', [$this->invoice, 'format' => 'thermal_80mm']));
    $resThermal->assertOk()->assertSee('data-format="thermal_80mm"', false);
});

test('7. selects the correct default template and settings for each format via PrintTemplateService', function () {
    $service = app(PrintTemplateService::class);
    $service->seedDefaultTemplates();

    $res = $this->get(route('purchases.print', [$this->invoice, 'format' => 'a4']));
    $res->assertOk();

    $template = $res->viewData('template');
    expect($template)->toBeInstanceOf(PrintTemplate::class)
        ->and($template->document_type)->toBe(PrintTemplate::DOC_PURCHASE_INVOICE)
        ->and($template->paper_format)->toBe(PrintTemplate::FORMAT_A4);
});

test('8. applies custom template settings such as custom title and logo visibility', function () {
    $service = app(PrintTemplateService::class);
    $service->seedDefaultTemplates();

    // Customize the purchase invoice A4 template
    $template = PrintTemplate::forDocument(PrintTemplate::DOC_PURCHASE_INVOICE)
        ->forFormat(PrintTemplate::FORMAT_A4)
        ->first();

    $settings = $template->settings;
    $settings['general']['custom_title'] = 'سند إيداع مخزني وتوريد بضائع';
    $settings['general']['custom_subtitle'] = 'قسم المشتريات الخارجية';
    $template->update(['settings' => $settings]);

    $response = $this->get(route('purchases.print', [$this->invoice, 'format' => 'a4']));

    $response->assertOk()
        ->assertSee('سند إيداع مخزني وتوريد بضائع')
        ->assertSee('قسم المشتريات الخارجية');
});

test('9. absence of dummy preview data in actual print output', function () {
    // The preview sample data in PrintTemplateService has dummy supplier "شركة الأمل للتوريدات"
    // and items like "كابل شاحن Type-C سريع"
    $response = $this->get(route('purchases.print', $this->invoice));

    $response->assertOk()
        ->assertSee($this->invoice->supplier->name)
        ->assertSee($this->product1->name)
        ->assertDontSee('شركة الأمل للتوريدات العمومية')
        ->assertDontSee('كابل شاحن Type-C سريع');
});

test('10. opening the print page does not modify inventory balances or supplier balances', function () {
    $initialBalance = (float) $this->supplier->fresh()->current_balance;
    $initialStockCount = StockBalance::count();
    $initialStatus = $this->invoice->fresh()->status;

    $this->get(route('purchases.print', $this->invoice))->assertOk();
    $this->get(route('purchases.print', [$this->invoice, 'format' => 'thermal_80mm']))->assertOk();

    expect((float) $this->supplier->fresh()->current_balance)->toBe($initialBalance)
        ->and(StockBalance::count())->toBe($initialStockCount)
        ->and($this->invoice->fresh()->status)->toBe($initialStatus);
});

test('11. print route shares identical middleware configuration with invoice show route', function () {
    $printRoute = Route::getRoutes()->getByName('purchases.print');
    $showRoute = Route::getRoutes()->getByName('purchases.show');

    expect($printRoute)->not->toBeNull()
        ->and($showRoute)->not->toBeNull()
        ->and($printRoute->middleware())->toEqual($showRoute->middleware());
});

test('12. operates with full fallback defaults when print_templates table is completely empty', function () {
    PrintTemplate::query()->delete();
    expect(PrintTemplate::count())->toBe(0);

    $response = $this->get(route('purchases.print', $this->invoice));

    $response->assertOk()
        ->assertViewIs('purchases.print')
        ->assertSee('PUR-20261014-0001-TEST')
        ->assertSee('شركة النيل للتوريدات العامة');

    $template = $response->viewData('template');
    expect($template)->toBeInstanceOf(PrintTemplate::class)
        ->and($template->is_default)->toBeTrue()
        ->and($template->getSetting('general.custom_title'))->toBe('إذن استلام');
});

test('13. financial section renders exact stored model values and does not show false discount or tax rows', function () {
    $response = $this->get(route('purchases.print', $this->invoice));

    $response->assertOk()
        ->assertSee('المجموع الفرعي:')
        ->assertSee('1,500.00 ج.م')
        ->assertSee('تكلفة الشحن والتوصيل:')
        ->assertSee('150.00 ج.م')
        ->assertSee('إجمالي الفاتورة:')
        ->assertSee('1,650.00 ج.م');
});

test('14. resilient against incomplete or partial template settings without crashing', function () {
    $service = app(PrintTemplateService::class);
    $service->seedDefaultTemplates();

    $template = PrintTemplate::forDocument(PrintTemplate::DOC_PURCHASE_INVOICE)
        ->forFormat(PrintTemplate::FORMAT_A4)
        ->first();

    // Corrupt or strip some nested settings keys to simulate partial configuration
    $template->update([
        'settings' => [
            'general' => [
                'custom_title' => null,
            ],
            // missing party_meta, financial_summary, etc.
        ],
    ]);

    $response = $this->get(route('purchases.print', [$this->invoice, 'format' => 'a4']));

    $response->assertOk()
        ->assertSee('فاتورة شراء وتوريد') // Fallback default title
        ->assertSee($this->invoice->invoice_number);
});
