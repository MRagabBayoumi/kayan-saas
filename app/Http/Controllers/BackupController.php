<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Update or clear the optional custom backup directory path.
     */
    public function updateCustomPath(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'custom_backup_path' => ['nullable', 'string', 'max:500'],
        ]);

        $path = trim((string) ($validated['custom_backup_path'] ?? ''));

        if (! empty($path)) {
            try {
                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0755, true, true);
                }

                if (! is_writable($path)) {
                    return redirect()->route('settings.index', ['tab' => 'backups'])
                        ->with('error', 'المسار المحدد غير قابل للكتابة. يرجى التأكد من صلاحيات المجلد أو اختيار مجلد آخر.');
                }
            } catch (\Throwable $e) {
                return redirect()->route('settings.index', ['tab' => 'backups'])
                    ->with('error', 'تعذر الوصول إلى المجلد المحدد أو إنشاؤه: '.$e->getMessage());
            }
        }

        $settings = CompanySetting::getSettings();
        $settings->update([
            'custom_backup_path' => ! empty($path) ? $path : null,
        ]);

        $msg = ! empty($path)
            ? 'تم حفظ وتفعيل مسار الحفظ الإضافي بنجاح. سيتم نسخ كافة النسخ الاحتياطية إليه تلقائياً.'
            : 'تم إلغاء مسار الحفظ الإضافي، وسيتم الاكتفاء بالمسار الافتراضي للنظام.';

        return redirect()->route('settings.index', ['tab' => 'backups'])
            ->with('status', 'success')
            ->with('message', $msg);
    }

    /**
     * Create a new backup on demand (Database only or Full).
     */
    public function create(Request $request): RedirectResponse
    {
        $type = $request->input('type', 'db');

        try {
            $params = ['--disable-notifications' => true];
            if ($type !== 'full') {
                $params['--only-db'] = true;
            }

            $exitCode = Artisan::call('backup:run', $params);
            $output = Artisan::output();

            if ($exitCode !== 0) {
                Log::error('Backup failed with exit code '.$exitCode.': '.$output);

                return redirect()->route('settings.index', ['tab' => 'backups'])
                    ->with('error', 'حدث خطأ أثناء إنشاء النسخة الاحتياطية: '.$output);
            }

            $typeLabel = $type === 'full' ? 'الكاملة (قاعدة البيانات والملفات)' : 'لقاعدة البيانات';

            return redirect()->route('settings.index', ['tab' => 'backups'])
                ->with('status', 'success')
                ->with('message', 'تم إنشاء النسخة الاحتياطية '.$typeLabel.' بنجاح.');
        } catch (\Throwable $e) {
            Log::error('Backup exception: '.$e->getMessage());

            return redirect()->route('settings.index', ['tab' => 'backups'])
                ->with('error', 'تعذر إنشاء النسخة الاحتياطية: '.$e->getMessage());
        }
    }

    /**
     * Download a specific backup archive.
     */
    public function download(string $filename): StreamedResponse|RedirectResponse
    {
        $cleanFilename = basename($filename);

        if (! preg_match('/^[a-zA-Z0-9_\-\.]+\.zip$/', $cleanFilename)) {
            return redirect()->route('settings.index', ['tab' => 'backups'])
                ->with('error', 'اسم ملف النسخة الاحتياطية غير صالح.');
        }

        $path = 'kayan-erp/'.$cleanFilename;

        if (! Storage::disk('backups')->exists($path)) {
            return redirect()->route('settings.index', ['tab' => 'backups'])
                ->with('error', 'ملف النسخة الاحتياطية المطلوب غير موجود أو تم حذفه.');
        }

        return Storage::disk('backups')->download($path, $cleanFilename);
    }

    /**
     * Delete an existing backup file.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $cleanFilename = basename($filename);

        if (! preg_match('/^[a-zA-Z0-9_\-\.]+\.zip$/', $cleanFilename)) {
            return redirect()->route('settings.index', ['tab' => 'backups'])
                ->with('error', 'اسم ملف النسخة الاحتياطية غير صالح.');
        }

        $path = 'kayan-erp/'.$cleanFilename;

        if (Storage::disk('backups')->exists($path)) {
            Storage::disk('backups')->delete($path);

            $settings = CompanySetting::getSettings();
            if ($settings->custom_backup_path) {
                $customFile = rtrim($settings->custom_backup_path, '\\/').DIRECTORY_SEPARATOR.$cleanFilename;
                if (File::exists($customFile)) {
                    File::delete($customFile);
                }
            }

            return redirect()->route('settings.index', ['tab' => 'backups'])
                ->with('status', 'success')
                ->with('message', 'تم حذف ملف النسخة الاحتياطية بنجاح.');
        }

        return redirect()->route('settings.index', ['tab' => 'backups'])
            ->with('error', 'ملف النسخة الاحتياطية غير موجود.');
    }

    /**
     * Get the list of all available backups formatted for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBackupsList(): array
    {
        $disk = Storage::disk('backups');
        $files = $disk->exists('kayan-erp') ? $disk->files('kayan-erp') : [];

        $backups = [];

        foreach ($files as $file) {
            if (! str_ends_with($file, '.zip')) {
                continue;
            }

            $bytes = (int) $disk->size($file);
            $lastModified = (int) $disk->lastModified($file);

            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size_formatted' => $this->formatBytes($bytes),
                'size_bytes' => $bytes,
                'created_at' => date('Y-m-d H:i:s', $lastModified),
                'timestamp' => $lastModified,
            ];
        }

        // Sort newest first
        usort($backups, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Helper to format bytes into KB/MB.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' ميجابايت';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' كيلوبايت';
        }

        return $bytes.' بايت';
    }
}
