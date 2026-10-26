<?php

namespace App\Listeners;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\Backup\Events\BackupZipWasCreated;

class CopyBackupToCustomDirectory
{
    /**
     * Handle the event when a backup zip archive is created.
     */
    public function handle(BackupZipWasCreated $event): void
    {
        try {
            $settings = CompanySetting::getSettings();
            $customPath = trim((string) $settings->custom_backup_path);

            if (empty($customPath)) {
                return;
            }

            // Ensure destination directory exists
            if (! File::isDirectory($customPath)) {
                File::makeDirectory($customPath, 0755, true, true);
            }

            $sourceZip = $event->pathToZip;
            $filename = basename($sourceZip);
            $destination = rtrim($customPath, '\\/').DIRECTORY_SEPARATOR.$filename;

            if (File::exists($sourceZip)) {
                File::copy($sourceZip, $destination);
                Log::info("Backup archive copied successfully to custom folder: {$destination}");
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to copy backup to custom folder ({$customPath}): ".$e->getMessage());
        }
    }
}
