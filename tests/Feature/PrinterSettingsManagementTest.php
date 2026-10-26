<?php

use App\Models\BarcodeSetting;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
    ]);
});

test('admin can view the printers and pos setup tab in settings', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('settings.index', ['tab' => 'printers']));

    $response->assertOk();
    $response->assertSee('مركز التحكم في الطابعات وأجهزة الكاشير');
    $response->assertSee('طابعة إيصالات الكاشير');
    $response->assertSee('طابعة الفواتير والمستندات الرسمية');
    $response->assertSee('طابعة استيكرات وملصقات الباركود');
    $response->assertSee('فحص وتحديث الطابعات المتاحة');
});

test('admin can discover available system printers via api', function () {
    $response = $this->actingAs($this->admin)
        ->getJson(route('settings.printers.discover'));

    $response->assertOk();
    $response->assertJsonStructure([
        'success',
        'count',
        'printers',
        'message',
    ]);
    expect($response->json('success'))->toBeTrue();
    expect(is_array($response->json('printers')))->toBeTrue();
});

test('admin can update printer settings and synchronize barcode printer', function () {
    $barcodeSettings = BarcodeSetting::getSettings();
    $barcodeSettings->update(['printer_name' => 'Old Printer']);

    $payload = [
        'thermal_printer_name' => 'XP-80C-Custom',
        'document_printer_name' => 'Office Network Printer',
        'barcode_printer_name' => 'Xprinter XP-233B New',
        'receipt_print_mode' => 'server_python',
        'printer_network_user' => 'cashier_user',
        'printer_network_password' => 'secret123',
        'auto_print_on_save' => '1',
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('settings.printers.update'), $payload);

    $response->assertRedirect(route('settings.index', ['tab' => 'printers']));
    $response->assertSessionHas('status', 'success');

    $companySettings = CompanySetting::getSettings();
    expect($companySettings->thermal_printer_name)->toBe('XP-80C-Custom');
    expect($companySettings->document_printer_name)->toBe('Office Network Printer');
    expect($companySettings->receipt_print_mode)->toBe('server_python');
    expect($companySettings->printer_network_user)->toBe('cashier_user');
    expect($companySettings->printer_network_password)->toBe('secret123');
    expect($companySettings->auto_print_on_save)->toBeTrue();

    // Verify synchronization with BarcodeSetting
    expect(BarcodeSetting::getSettings()->fresh()->printer_name)->toBe('Xprinter XP-233B New');
});

test('admin can execute test print on receipt, document and barcode printers', function () {
    // Mock or verify test print returns structured response
    $responseReceipt = $this->actingAs($this->admin)
        ->postJson(route('settings.printers.test'), [
            'type' => 'thermal_80mm',
            'printer_name' => 'XP-80C',
        ]);

    $responseReceipt->assertOk();
    expect($responseReceipt->json('success'))->toBeTrue();

    $responseDoc = $this->actingAs($this->admin)
        ->postJson(route('settings.printers.test'), [
            'type' => 'document_a4',
            'printer_name' => 'TOSHIBA Generic Printer XL',
        ]);

    $responseDoc->assertOk();
    expect($responseDoc->json('success'))->toBeTrue();

    $responseBarcode = $this->actingAs($this->admin)
        ->postJson(route('settings.printers.test'), [
            'type' => 'barcode',
            'printer_name' => 'Xprinter XP-233B',
        ]);

    $responseBarcode->assertOk();
    expect($responseBarcode->json('success'))->toBeTrue();
});

test('printers blade template strictly enforces zero inline js and zero inline css separation', function () {
    $content = file_get_contents(resource_path('views/settings/tabs/printers.blade.php'));
    $eventHandlers = ['click', 'change', 'submit', 'input', 'keydown', 'keyup', 'keypress', 'load', 'error', 'mouseover', 'mouseout'];
    $eventPattern = '/\bon(?:'.implode('|', $eventHandlers).')\s*=/i';

    expect(preg_match('/<script\b[^>]*>(.*?)<\/script>/is', $content))->toBe(0);
    expect(preg_match($eventPattern, $content))->toBe(0);
    expect(preg_match('/<style\b[^>]*>(.*?)<\/style>/is', $content))->toBe(0);
    expect(preg_match('/<[^>]*\bstyle\s*=\s*["\'][^"\']*["\'][^>]*>/is', $content))->toBe(0);
});
