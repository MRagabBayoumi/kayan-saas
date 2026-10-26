<?php

use App\Models\PrintTemplate;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\StockMovement;
use App\Services\PrintTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('print_templates table has expected columns and indexes', function () {
    expect(Schema::hasTable('print_templates'))->toBeTrue();

    $expectedColumns = [
        'id',
        'document_type',
        'paper_format',
        'name',
        'is_default',
        'settings',
        'version',
        'created_at',
        'updated_at',
    ];

    foreach ($expectedColumns as $col) {
        expect(Schema::hasColumn('print_templates', $col))->toBeTrue("Missing column: {$col}");
    }
});

test('print template model supports mass assignment, casts, and scopes', function () {
    $template = PrintTemplate::create([
        'document_type' => PrintTemplate::DOC_SALES_INVOICE,
        'paper_format' => PrintTemplate::FORMAT_A4,
        'name' => 'فاتورة مبيعات A4 مخصصة',
        'is_default' => true,
        'settings' => [
            'general' => ['show_logo' => true, 'primary_color' => '#1e293b'],
        ],
        'version' => 1,
    ]);

    expect($template->is_default)->toBeTrue()
        ->and($template->settings)->toBeArray()
        ->and($template->getSetting('general.primary_color'))->toBe('#1e293b')
        ->and($template->getSetting('general.non_existent', 'fallback'))->toBe('fallback');

    // Test scopes
    $foundDoc = PrintTemplate::forDocument(PrintTemplate::DOC_SALES_INVOICE)->first();
    expect($foundDoc->id)->toBe($template->id);

    $foundFormat = PrintTemplate::forFormat(PrintTemplate::FORMAT_A4)->first();
    expect($foundFormat->id)->toBe($template->id);

    $foundDefault = PrintTemplate::default()->first();
    expect($foundDefault->id)->toBe($template->id);
});

test('print template service returns tailored default settings for all 8 combinations', function () {
    $service = new PrintTemplateService;

    $docTypes = [
        PrintTemplate::DOC_SALES_INVOICE,
        PrintTemplate::DOC_PURCHASE_INVOICE,
        PrintTemplate::DOC_CUSTOMER_STATEMENT,
        PrintTemplate::DOC_SUPPLIER_STATEMENT,
    ];

    $formats = [
        PrintTemplate::FORMAT_A4,
        PrintTemplate::FORMAT_A5,
        PrintTemplate::FORMAT_THERMAL_80MM,
    ];

    foreach ($docTypes as $docType) {
        foreach ($formats as $format) {
            $defaults = $service->getDefaultSettings($docType, $format);

            expect($defaults)->toBeArray()
                ->toHaveKeys(['general', 'document_meta', 'party_meta', 'table_columns', 'financial_summary', 'statement_options', 'notes_and_terms', 'signatures']);

            if ($format === PrintTemplate::FORMAT_THERMAL_80MM) {
                expect($defaults['general']['primary_color'])->toBe('#000000')
                    ->and($defaults['table_columns']['density'])->toBe('compact')
                    ->and($defaults['table_columns']['show_sku'])->toBeFalse()
                    ->and($defaults['signatures']['show_signatures'])->toBeFalse();
            } elseif ($format === PrintTemplate::FORMAT_A5) {
                expect($defaults['general']['primary_color'])->toBe('#1e293b')
                    ->and($defaults['table_columns']['density'])->toBe('compact')
                    ->and($defaults['table_columns']['show_sku'])->toBeTrue()
                    ->and($defaults['table_columns']['show_notes'])->toBeFalse()
                    ->and($defaults['signatures']['show_signatures'])->toBeTrue()
                    ->and($defaults['signatures']['show_stamp_box'])->toBeFalse();
            } else {
                expect($defaults['general']['primary_color'])->toBe('#1e293b')
                    ->and($defaults['table_columns']['density'])->toBe('comfortable')
                    ->and($defaults['table_columns']['show_sku'])->toBeTrue()
                    ->and($defaults['signatures']['show_signatures'])->toBeTrue()
                    ->and($defaults['signatures']['show_stamp_box'])->toBeTrue();
            }

            if ($docType === PrintTemplate::DOC_PURCHASE_INVOICE) {
                expect($defaults['financial_summary']['show_extra_expenses'])->toBeTrue()
                    ->and($defaults['party_meta']['party_type_label'])->toBe('المورد');
            } elseif ($docType === PrintTemplate::DOC_SALES_INVOICE) {
                expect($defaults['party_meta']['party_type_label'])->toBe('العميل');
            }
        }
    }
});

test('print template service strictly sanitizes and whitelists settings', function () {
    $service = new PrintTemplateService;

    $maliciousInput = [
        'general' => [
            'show_logo' => '1',
            'logo_size' => 'invalid_size', // should fallback
            'custom_title' => '<script>alert("XSS")</script>فاتورة معتمدة',
            'custom_subtitle' => '<iframe src="evil.com"></iframe>فرع المنصورة',
            'primary_color' => 'javascript:void(0)', // invalid hex, should fallback
            'font_size' => 'huge', // should fallback
        ],
        'notes_and_terms' => [
            'notes_text' => '<b>نص عريض</b> وملاحظات <script>evil()</script>',
            'terms_text' => '<style>body{display:none;}</style>الشروط والأحكام الرسمية',
        ],
        'table_columns' => [
            'density' => 'loose', // invalid, should fallback
        ],
    ];

    $validated = $service->validateSettings($maliciousInput, PrintTemplate::DOC_SALES_INVOICE, PrintTemplate::FORMAT_A4);

    expect($validated['general']['show_logo'])->toBeTrue()
        ->and($validated['general']['logo_size'])->toBe('md') // fallback
        ->and($validated['general']['custom_title'])->toBe('فاتورة معتمدة')
        ->and($validated['general']['custom_subtitle'])->toBe('فرع المنصورة')
        ->and($validated['general']['primary_color'])->toBe('#1e293b') // safe fallback
        ->and($validated['general']['font_size'])->toBe('md')
        ->and($validated['notes_and_terms']['notes_text'])->toBe('نص عريض وملاحظات')
        ->and($validated['notes_and_terms']['terms_text'])->toBe('الشروط والأحكام الرسمية')
        ->and($validated['table_columns']['density'])->toBe('comfortable');
});

test('seedDefaultTemplates idempotently seeds exactly 8 default templates', function () {
    $service = new PrintTemplateService;

    expect(PrintTemplate::count())->toBe(0);

    $seededCount = $service->seedDefaultTemplates();
    expect($seededCount)->toBe(12)
        ->and(PrintTemplate::count())->toBe(12);

    // Verify all 12 are marked as default
    expect(PrintTemplate::default()->count())->toBe(12);

    // Calling it again should be idempotent (0 newly created)
    $secondPass = $service->seedDefaultTemplates();
    expect($secondPass)->toBe(0)
        ->and(PrintTemplate::count())->toBe(12);
});

test('getEffectiveTemplate returns persisted default, custom template by ID, or fallback instance', function () {
    $service = new PrintTemplateService;

    // 1. With completely empty table: returns in-memory safe fallback
    $fallback = $service->getEffectiveTemplate(PrintTemplate::DOC_SALES_INVOICE, PrintTemplate::FORMAT_A4);
    expect($fallback)->toBeInstanceOf(PrintTemplate::class)
        ->and($fallback->exists)->toBeFalse()
        ->and($fallback->is_default)->toBeTrue()
        ->and($fallback->getSetting('general.custom_title'))->toBe('إذن صرف');

    // 2. After seeding defaults: returns persisted default
    $service->seedDefaultTemplates();
    $persisted = $service->getEffectiveTemplate(PrintTemplate::DOC_SALES_INVOICE, PrintTemplate::FORMAT_A4);
    expect($persisted->exists)->toBeTrue()
        ->and($persisted->is_default)->toBeTrue();

    // 3. Custom template by ID
    $custom = PrintTemplate::create([
        'document_type' => PrintTemplate::DOC_SALES_INVOICE,
        'paper_format' => PrintTemplate::FORMAT_A4,
        'name' => 'قالب مبيعات خاص',
        'is_default' => false,
        'settings' => $service->getDefaultSettings(PrintTemplate::DOC_SALES_INVOICE, PrintTemplate::FORMAT_A4),
        'version' => 1,
    ]);

    $loaded = $service->getEffectiveTemplate(PrintTemplate::DOC_SALES_INVOICE, PrintTemplate::FORMAT_A4, $custom->id);
    expect($loaded->id)->toBe($custom->id);
});

test('preview sample data is purely in-memory, deterministic, and does not alter database', function () {
    $service = new PrintTemplateService;

    $initialProductsCount = Product::count();
    $initialMovementsCount = StockMovement::count();
    $initialInvoicesCount = PurchaseInvoice::count();

    $docTypes = [
        PrintTemplate::DOC_SALES_INVOICE,
        PrintTemplate::DOC_PURCHASE_INVOICE,
        PrintTemplate::DOC_CUSTOMER_STATEMENT,
        PrintTemplate::DOC_SUPPLIER_STATEMENT,
    ];

    foreach ($docTypes as $docType) {
        $sampleA4 = $service->getPreviewSampleData($docType, PrintTemplate::FORMAT_A4);
        $sampleThermal = $service->getPreviewSampleData($docType, PrintTemplate::FORMAT_THERMAL_80MM);

        expect($sampleA4['is_sample'])->toBeTrue()
            ->and($sampleA4['watermark'])->toBeString()
            ->and($sampleA4)->toHaveKeys(['company', 'document', 'party'])
            ->and($sampleThermal['is_sample'])->toBeTrue();

        if (in_array($docType, [PrintTemplate::DOC_SALES_INVOICE, PrintTemplate::DOC_PURCHASE_INVOICE], true)) {
            expect($sampleA4)->toHaveKeys(['items', 'financial'])
                ->and(count($sampleA4['items']))->toBeGreaterThan(0);
        } else {
            expect($sampleA4)->toHaveKeys(['transactions', 'summary'])
                ->and(count($sampleA4['transactions']))->toBeGreaterThan(0);
        }
    }

    // STRICT GUARANTEE: Zero database mutations
    expect(Product::count())->toBe($initialProductsCount)
        ->and(StockMovement::count())->toBe($initialMovementsCount)
        ->and(PurchaseInvoice::count())->toBe($initialInvoicesCount);
});
