<?php

use App\Models\PrintTemplate;
use App\Services\PrintTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed default 12 templates
    $service = app(PrintTemplateService::class);
    $service->seedDefaultTemplates();
});

test('settings print_templates tab renders correctly with all 3 paper formats and 4 doc types', function () {
    $response = $this->get(route('settings.index', [
        'tab' => 'print_templates',
        'doc_type' => PrintTemplate::DOC_SALES_INVOICE,
        'paper_format' => PrintTemplate::FORMAT_A4,
    ]));

    $response->assertOk()
        ->assertSee('قوالب الطباعة والمستندات')
        ->assertSee('فاتورة مبيعات')
        ->assertSee('فاتورة مشتريات')
        ->assertSee('كشف حساب عميل')
        ->assertSee('كشف حساب مورد')
        ->assertSee('A4 قياسي')
        ->assertSee('A5 قياسي')
        ->assertSee('حراري 80 مم')
        ->assertSee('data-page="settings-print-templates"', false)
        ->assertSee('id="paperMockupSheet"', false);
});

test('settings print_templates tab loads A5 format specifically', function () {
    $response = $this->get(route('settings.index', [
        'tab' => 'print_templates',
        'doc_type' => PrintTemplate::DOC_PURCHASE_INVOICE,
        'paper_format' => PrintTemplate::FORMAT_A5,
    ]));

    $response->assertOk()
        ->assertSee('data-format="a5"', false)
        ->assertSee('فاتورة مشتريات الافتراضي (A5)');
});

test('settings print_templates data endpoint returns structured json with preview sample data', function () {
    $response = $this->getJson(route('settings.print-templates.data', [
        'doc_type' => PrintTemplate::DOC_CUSTOMER_STATEMENT,
        'paper_format' => PrintTemplate::FORMAT_A5,
    ]));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('template.document_type', PrintTemplate::DOC_CUSTOMER_STATEMENT)
        ->assertJsonPath('template.paper_format', PrintTemplate::FORMAT_A5)
        ->assertJsonPath('sample_data.is_sample', true)
        ->assertJsonStructure([
            'success',
            'template' => ['id', 'document_type', 'paper_format', 'name', 'is_default', 'settings', 'version'],
            'sample_data' => ['company', 'document', 'party', 'transactions', 'summary'],
            'defaults',
            'templates_list',
        ]);
});

test('print template can be updated with sanitized settings', function () {
    $template = PrintTemplate::forDocument(PrintTemplate::DOC_SALES_INVOICE)
        ->forFormat(PrintTemplate::FORMAT_A4)
        ->firstOrFail();

    $initialVersion = $template->version;

    $updatePayload = [
        'name' => 'فاتورة مبيعات الموزعين المعدلة',
        'is_default' => true,
        'settings' => [
            'general' => [
                'custom_title' => '<script>alert(1)</script>فاتورة مبيعات معتمدة',
                'primary_color' => '#0f172a',
                'font_size' => 'lg',
            ],
            'table_columns' => [
                'density' => 'compact',
                'show_notes' => false,
            ],
        ],
    ];

    $response = $this->putJson(route('settings.print-templates.update', $template->id), $updatePayload);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('template.name', 'فاتورة مبيعات الموزعين المعدلة')
        ->assertJsonPath('template.settings.general.custom_title', 'فاتورة مبيعات معتمدة') // XSS stripped
        ->assertJsonPath('template.settings.general.primary_color', '#0f172a')
        ->assertJsonPath('template.settings.table_columns.density', 'compact');

    $template->refresh();
    expect($template->version)->toBe($initialVersion + 1)
        ->and($template->getSetting('general.custom_title'))->toBe('فاتورة مبيعات معتمدة');
});

test('print template reset endpoint restores system default settings', function () {
    $template = PrintTemplate::forDocument(PrintTemplate::DOC_SALES_INVOICE)
        ->forFormat(PrintTemplate::FORMAT_A4)
        ->firstOrFail();

    // First modify it
    $template->update([
        'settings' => [
            'general' => ['custom_title' => 'عنوان معدل مؤقتاً', 'primary_color' => '#ff0000'],
        ],
    ]);

    expect($template->getSetting('general.custom_title'))->toBe('عنوان معدل مؤقتاً');

    // Call reset
    $response = $this->postJson(route('settings.print-templates.reset', $template->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('template.settings.general.custom_title', 'إذن صرف')
        ->assertJsonPath('template.settings.general.primary_color', '#1e293b');

    $template->refresh();
    expect($template->getSetting('general.custom_title'))->toBe('إذن صرف');
});

test('print template duplicate endpoint clones template as custom copy', function () {
    $template = PrintTemplate::forDocument(PrintTemplate::DOC_PURCHASE_INVOICE)
        ->forFormat(PrintTemplate::FORMAT_A5)
        ->firstOrFail();

    $response = $this->postJson(route('settings.print-templates.duplicate', $template->id), [
        'name' => 'قالب مشتريات A5 مخصص فرع العتبة',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('template.name', 'قالب مشتريات A5 مخصص فرع العتبة')
        ->assertJsonPath('template.is_default', false)
        ->assertJsonPath('template.document_type', PrintTemplate::DOC_PURCHASE_INVOICE)
        ->assertJsonPath('template.paper_format', PrintTemplate::FORMAT_A5);

    expect(PrintTemplate::where('name', 'قالب مشتريات A5 مخصص فرع العتبة')->exists())->toBeTrue();
});

test('settings print_templates supports custom logo and live preview branding options', function () {
    $template = PrintTemplate::forDocument(PrintTemplate::DOC_SALES_INVOICE)
        ->forFormat(PrintTemplate::FORMAT_A4)
        ->firstOrFail();

    $settings = $template->settings;
    $settings['general']['show_logo'] = true;
    $settings['general']['custom_logo'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    $settings['general']['logo_size'] = 'lg';
    $settings['general']['primary_color'] = '#714B67';

    $response = $this->putJson(route('settings.print-templates.update', $template->id), [
        'name' => $template->name,
        'settings' => $settings,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('template.settings.general.custom_logo', $settings['general']['custom_logo'])
        ->assertJsonPath('template.settings.general.logo_size', 'lg')
        ->assertJsonPath('template.settings.general.primary_color', '#714b67');

    $template->refresh();
    expect($template->getSetting('general.custom_logo'))->toBe($settings['general']['custom_logo'])
        ->and($template->getSetting('general.logo_size'))->toBe('lg')
        ->and($template->getSetting('general.primary_color'))->toBe('#714b67');
});
