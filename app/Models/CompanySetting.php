<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'company_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'company_phone',
        'logo_path',
        'program_status',
        'custom_backup_path',
        'thermal_printer_name',
        'document_printer_name',
        'receipt_print_mode',
        'printer_network_user',
        'printer_network_password',
        'auto_print_on_save',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'auto_print_on_save' => 'boolean',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearSettingsCache();
        });
    }

    /**
     * Get the global singleton company settings instance.
     */
    public static function getSettings(): self
    {
        if (app()->bound('company_setting.singleton')) {
            return app('company_setting.singleton');
        }

        $settings = static::firstOrCreate([], [
            'company_name' => 'Kayan Fashion Accessories',
            'company_phone' => null,
            'logo_path' => null,
            'program_status' => 'active',
            'thermal_printer_name' => 'XP-80C',
            'document_printer_name' => 'TOSHIBA Generic Printer XL',
            'receipt_print_mode' => 'browser_kiosk',
            'printer_network_user' => null,
            'printer_network_password' => null,
            'auto_print_on_save' => true,
        ]);

        app()->instance('company_setting.singleton', $settings);

        return $settings;
    }

    /**
     * Check if receipt printing should be handled directly by browser kiosk mode.
     */
    public function isBrowserKioskReceipt(): bool
    {
        return ($this->receipt_print_mode ?? 'browser_kiosk') === 'browser_kiosk';
    }

    /**
     * Clear cached singleton settings.
     */
    public static function clearSettingsCache(): void
    {
        app()->forgetInstance('company_setting.singleton');
    }

    /**
     * Get the full URL to the company logo if stored.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return '/storage/'.$this->logo_path;
        }

        return null;
    }

    /**
     * Check if program is set to maintenance status.
     */
    public function isMaintenance(): bool
    {
        return $this->program_status === 'maintenance';
    }
}
