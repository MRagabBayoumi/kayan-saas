<?php

use App\Models\BarcodeSetting;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Services\BarcodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Generate a real 1x1 PNG byte stream so PHP's fileinfo detects a valid image without needing GD.
 */
function createFakePngFile(string $filename = 'logo.png', int $sizeKilobytes = 10): UploadedFile
{
    $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    $content = base64_decode($pngBase64);

    if ($sizeKilobytes > 1) {
        // Pad the file with null bytes while keeping PNG header intact
        $content .= str_repeat("\0", ($sizeKilobytes * 1024) - strlen($content));
    }

    return UploadedFile::fake()->createWithContent($filename, $content);
}

test('settings page loads successfully with Odoo layout and Arabic RTL', function () {
    $response = $this->get(route('settings.index'));

    $response->assertStatus(200);
    $response->assertSee('إعدادات الشركة والنظام');
    $response->assertSee('بيانات الشركة');
    $response->assertSee('حالة البرنامج');
    $response->assertSee('حفظ الإعدادات');
    $response->assertSee('name="company_name"', false);
    $response->assertSee('name="company_phone"', false);
    $response->assertSee('name="company_logo"', false);
    $response->assertSee('name="program_status"', false);
});

test('validation fails when company name is missing', function () {
    $response = $this->from(route('settings.index'))->put(route('settings.update'), [
        'company_name' => '',
        'company_phone' => '+20 100 123 4567',
        'program_status' => 'active',
    ]);

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHasErrors(['company_name']);
});

test('validation fails when company phone contains invalid characters', function () {
    $response = $this->from(route('settings.index'))->put(route('settings.update'), [
        'company_name' => 'كيان إكسسوارات',
        'company_phone' => 'invalid-phone-abc!@#$',
        'program_status' => 'active',
    ]);

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHasErrors(['company_phone']);
});

test('validation fails when logo is not a valid image format or exceeds 2MB', function () {
    Storage::fake('public');

    // 1. PDF document uploaded instead of image
    $pdfFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $response1 = $this->from(route('settings.index'))->put(route('settings.update'), [
        'company_name' => 'كيان إكسسوارات',
        'company_logo' => $pdfFile,
        'program_status' => 'active',
    ]);

    $response1->assertRedirect(route('settings.index'));
    $response1->assertSessionHasErrors(['company_logo']);

    // 2. Oversized image (> 2MB / 2048KB)
    $oversizedFile = createFakePngFile('oversized.png', 3000);

    $response2 = $this->from(route('settings.index'))->put(route('settings.update'), [
        'company_name' => 'كيان إكسسوارات',
        'company_logo' => $oversizedFile,
        'program_status' => 'active',
    ]);

    $response2->assertRedirect(route('settings.index'));
    $response2->assertSessionHasErrors(['company_logo']);
});

test('settings can be updated successfully and phone format is preserved', function () {
    $response = $this->from(route('settings.index'))->put(route('settings.update'), [
        'company_name' => 'كيان لتجارة الإكسسوارات الحريمي',
        'company_phone' => '+20 101 234 5678',
        'program_status' => 'maintenance',
    ]);

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHas('status', 'success');

    $this->assertDatabaseHas('company_settings', [
        'company_name' => 'كيان لتجارة الإكسسوارات الحريمي',
        'company_phone' => '+20 101 234 5678',
        'program_status' => 'maintenance',
    ]);

    $settings = CompanySetting::getSettings();
    expect($settings->company_name)->toBe('كيان لتجارة الإكسسوارات الحريمي')
        ->and($settings->company_phone)->toBe('+20 101 234 5678')
        ->and($settings->program_status)->toBe('maintenance')
        ->and($settings->isMaintenance())->toBeTrue();
});

test('company logo can be uploaded and replaces previous logo cleanly', function () {
    Storage::fake('public');

    // 1. Initial logo upload
    $firstLogo = createFakePngFile('logo1.png', 50);

    $response1 = $this->put(route('settings.update'), [
        'company_name' => 'كيان إكسسوارات',
        'company_phone' => '+20 122 000 1111',
        'program_status' => 'active',
        'company_logo' => $firstLogo,
    ]);

    $response1->assertRedirect(route('settings.index'));

    $settingsAfterFirst = CompanySetting::getSettings();
    $firstPath = $settingsAfterFirst->logo_path;

    expect($firstPath)->not->toBeNull();
    Storage::disk('public')->assertExists($firstPath);

    // 2. Replace with second logo
    $secondLogo = createFakePngFile('logo2.png', 80);

    $response2 = $this->put(route('settings.update'), [
        'company_name' => 'كيان إكسسوارات المحدث',
        'company_phone' => '+20 122 000 1111',
        'program_status' => 'active',
        'company_logo' => $secondLogo,
    ]);

    $response2->assertRedirect(route('settings.index'));

    $settingsAfterSecond = CompanySetting::getSettings();
    $secondPath = $settingsAfterSecond->logo_path;

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertExists($secondPath);
    // Old file should be deleted from storage
    Storage::disk('public')->assertMissing($firstPath);
});

test('sidebar and layout reflect saved company settings and maintenance badge', function () {
    CompanySetting::updateOrCreate(
        ['id' => 1],
        [
            'company_name' => 'مؤسسة كيان الراقية',
            'company_phone' => '+20 109 888 7777',
            'program_status' => 'maintenance',
        ]
    );

    $response = $this->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('مؤسسة كيان الراقية');
    $response->assertSee('صيانة');
});

test('barcode settings tab loads successfully with inputs matching legacy software', function () {
    $response = $this->get(route('settings.index', ['tab' => 'barcode']));

    $response->assertStatus(200);
    $response->assertSee('إعدادات الباركود');
    $response->assertSee('بادئة الباركود');
    $response->assertSee('اسم الطابعة');
    $response->assertSee('عرض الاستيكر (مم)');
    $response->assertSee('ارتفاع الاستيكر (مم)');
    $response->assertSee('عرض صورة الباركود (بكسل)');
    $response->assertSee('ارتفاع صورة الباركود (بكسل)');
    $response->assertSee('حجم خط اسم المنتج');
    $response->assertSee('حجم خط تفاصيل الباركود');
    $response->assertSee('name="show_company_name"', false);
    $response->assertSee('name="show_product_name"', false);
    $response->assertSee('name="show_barcode_bars"', false);
    $response->assertSee('name="show_item_sku"', false);
    $response->assertSee('name="print_price_type"', false);
    $response->assertSee('معاينة حية للملصق');
});

test('barcode settings can be updated successfully and reflected in database', function () {
    $response = $this->put(route('settings.barcode.update'), [
        'barcode_prefix' => '05',
        'printer_name' => 'Xprinter XP-233B Custom',
        'label_width_mm' => 38,
        'label_height_mm' => 25,
        'barcode_width_px' => 250,
        'barcode_height_px' => 85,
        'product_font_size' => 15,
        'details_font_size' => 9,
        'show_company_name' => '1',
        'company_name_override' => 'شركة كيان لتجارة الإكسسوارات',
        'show_product_name' => '1',
        'show_barcode_bars' => '1',
        'show_item_sku' => '1',
        'print_price_type' => 'wholesale',
    ]);

    $response->assertRedirect(route('settings.index', ['tab' => 'barcode']));
    $response->assertSessionHas('status', 'success');

    $this->assertDatabaseHas('barcode_settings', [
        'barcode_prefix' => '05',
        'printer_name' => 'Xprinter XP-233B Custom',
        'label_width_mm' => 38,
        'label_height_mm' => 25,
        'barcode_width_px' => 250,
        'barcode_height_px' => 85,
        'product_font_size' => 15,
        'details_font_size' => 9,
        'show_company_name' => 1,
        'company_name_override' => 'شركة كيان لتجارة الإكسسوارات',
        'show_product_name' => 1,
        'show_barcode_bars' => 1,
        'show_item_sku' => 1,
        'print_price_type' => 'wholesale',
    ]);

    $settings = BarcodeSetting::getSettings();
    expect($settings->barcode_prefix)->toBe('05')
        ->and($settings->print_price_type)->toBe('wholesale')
        ->and($settings->show_company_name)->toBeTrue();
});

test('barcode settings validation rejects invalid input', function () {
    $response = $this->from(route('settings.index', ['tab' => 'barcode']))->put(route('settings.barcode.update'), [
        'barcode_prefix' => '',
        'printer_name' => '',
        'label_width_mm' => -5,
        'label_height_mm' => 300,
        'barcode_width_px' => 10,
        'barcode_height_px' => 5,
        'product_font_size' => 0,
        'details_font_size' => 50,
        'print_price_type' => 'invalid_choice',
    ]);

    $response->assertRedirect(route('settings.index', ['tab' => 'barcode']));
    $response->assertSessionHasErrors([
        'barcode_prefix',
        'printer_name',
        'label_width_mm',
        'label_height_mm',
        'barcode_width_px',
        'barcode_height_px',
        'product_font_size',
        'details_font_size',
        'print_price_type',
    ]);
});

test('internal barcode generation uses prefix configured in barcode settings', function () {
    $barcodeSetting = BarcodeSetting::getSettings();
    $barcodeSetting->update(['barcode_prefix' => '77']);

    $generated = Product::generateInternalBarcode();
    expect($generated)->toStartWith('77');
});

test('barcode SVG generator incorporates company name and wholesale price when enabled', function () {
    $barcodeSetting = BarcodeSetting::getSettings();
    $barcodeSetting->update([
        'show_company_name' => true,
        'company_name_override' => 'شركة كيان للتجارة والتوزيع',
        'print_price_type' => 'wholesale',
    ]);

    $service = new BarcodeGeneratorService;
    $svg = $service->generateCompleteLabelSvg(
        'فرشاة شعر كوري',
        '021234567890',
        'HB-262',
        38,
        25,
        $barcodeSetting,
        50.00,
        35.00
    );

    expect($svg)->toContain('شركة كيان للتجارة والتوزيع')
        ->toContain('فرشاة شعر كوري')
        ->toContain('ITEM-NO: HB-262')
        ->toContain('جملة: 35.00 ج.م')
        ->toContain('<g id="barcode-bars">');
});
