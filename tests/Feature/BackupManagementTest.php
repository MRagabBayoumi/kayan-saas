<?php

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Events\BackupZipWasCreated;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'مدير النظام',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->cashier = User::factory()->create([
        'name' => 'كاشير مبيعات',
        'role' => User::ROLE_CASHIER,
        'is_active' => true,
    ]);

    Storage::fake('backups');
});

test('admin can access backups tab in settings', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('settings.index', ['tab' => 'backups']));

    $response->assertOk();
    $response->assertSee('النسخ الاحتياطي');
    $response->assertSee('نسخة احتياطية لقاعدة البيانات');
    $response->assertSee('نسخة احتياطية كاملة');
});

test('cashier cannot access or create backups', function () {
    $this->actingAs($this->cashier);

    $response = $this->get(route('settings.index', ['tab' => 'backups']));
    $response->assertForbidden();

    $postResponse = $this->post(route('settings.backups.create'), ['type' => 'db']);
    $postResponse->assertForbidden();
});

test('admin can download an existing backup file', function () {
    $this->actingAs($this->admin);

    // Create a mock backup file in storage
    $fakeFilename = '2026-10-17-test-backup.zip';
    Storage::disk('backups')->put('kayan-erp/'.$fakeFilename, 'fake zip content');

    $response = $this->get(route('settings.backups.download', $fakeFilename));

    $response->assertOk();
    $response->assertDownload($fakeFilename);
});

test('admin can delete an existing backup file', function () {
    $this->actingAs($this->admin);

    $fakeFilename = '2026-10-17-to-delete.zip';
    Storage::disk('backups')->put('kayan-erp/'.$fakeFilename, 'fake zip content to delete');

    expect(Storage::disk('backups')->exists('kayan-erp/'.$fakeFilename))->toBeTrue();

    $response = $this->delete(route('settings.backups.destroy', $fakeFilename));

    $response->assertRedirect(route('settings.index', ['tab' => 'backups']));
    $response->assertSessionHas('status', 'success');

    expect(Storage::disk('backups')->exists('kayan-erp/'.$fakeFilename))->toBeFalse();
});

test('admin can set and clear custom backup directory', function () {
    $this->actingAs($this->admin);

    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kayan_test_backup_'.uniqid();
    File::makeDirectory($tempDir, 0755, true, true);

    // 1. Set custom path
    $response = $this->put(route('settings.backups.custom-path'), [
        'custom_backup_path' => $tempDir,
    ]);

    $response->assertRedirect(route('settings.index', ['tab' => 'backups']));
    $response->assertSessionHas('status', 'success');

    $settings = CompanySetting::getSettings();
    expect($settings->custom_backup_path)->toBe($tempDir);

    // 2. Clear custom path
    $clearResponse = $this->put(route('settings.backups.custom-path'), [
        'custom_backup_path' => '',
    ]);

    $clearResponse->assertRedirect(route('settings.index', ['tab' => 'backups']));
    $settings->refresh();
    expect($settings->custom_backup_path)->toBeNull();

    File::deleteDirectory($tempDir);
});

test('backup zip was created event copies file to custom directory', function () {
    $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kayan_custom_target_'.uniqid();
    File::makeDirectory($tempDir, 0755, true, true);

    $settings = CompanySetting::getSettings();
    $settings->update(['custom_backup_path' => $tempDir]);

    // Create a mock zip file
    $mockZip = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mock_backup.zip';
    file_put_contents($mockZip, 'zip binary test content');

    // Fire the event
    event(new BackupZipWasCreated($mockZip));

    // Verify file copied to target
    $targetFile = $tempDir.DIRECTORY_SEPARATOR.'mock_backup.zip';
    expect(file_exists($targetFile))->toBeTrue();
    expect(file_get_contents($targetFile))->toBe('zip binary test content');

    // Clean up
    @unlink($mockZip);
    File::deleteDirectory($tempDir);
});
