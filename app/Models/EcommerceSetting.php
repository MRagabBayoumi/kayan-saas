<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EcommerceSetting extends Model
{
    protected $fillable = [
        'store_name',
        'store_tagline',
        'store_logo',
        'store_banner',
        'contact_phone',
        'contact_whatsapp',
        'shipping_flat_rate',
        'free_shipping_threshold',
        'is_store_open',
        'allow_cod',
        'announcement_text',
    ];

    protected $casts = [
        'shipping_flat_rate' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_store_open' => 'boolean',
        'allow_cod' => 'boolean',
    ];

    /**
     * Get the store logo URL with automatic fallback to company logo or app icon.
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->store_logo && Storage::disk('public')->exists($this->store_logo)) {
            return '/storage/'.$this->store_logo;
        }

        try {
            $company = CompanySetting::first();
            if ($company && ! empty($company->logo_url)) {
                return $company->logo_url;
            }
        } catch (\Throwable $e) {
        }

        if (file_exists(public_path('icon-192.png'))) {
            return asset('icon-192.png');
        }

        return asset('favicon.png');
    }

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
     * Clear cached singleton settings.
     */
    public static function clearSettingsCache(): void
    {
        app()->forgetInstance('ecommerce_setting.singleton');
    }

    /**
     * Get or create active store settings singleton.
     */
    public static function getSettings(): self
    {
        if (app()->bound('ecommerce_setting.singleton')) {
            return app('ecommerce_setting.singleton');
        }

        $defaultName = 'متجرنا الإلكتروني';
        try {
            $company = CompanySetting::first();
            if ($company && ! empty($company->company_name)) {
                $defaultName = $company->company_name;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        $settings = static::firstOrCreate([], [
            'store_name' => $defaultName,
            'store_tagline' => 'أفضل المنتجات وأسرع توصيل حتى باب المنزل',
            'shipping_flat_rate' => 30.00,
            'free_shipping_threshold' => 500.00,
            'is_store_open' => true,
            'allow_cod' => true,
            'announcement_text' => 'أهلاً بكم في متجرنا! التوصيل لجميع المحافظات والدفع عند الاستلام.',
        ]);

        app()->instance('ecommerce_setting.singleton', $settings);

        return $settings;
    }
}
