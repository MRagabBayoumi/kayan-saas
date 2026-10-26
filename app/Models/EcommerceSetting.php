<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
     * Get or create active store settings singleton.
     */
    public static function getSettings(): self
    {
        $defaultName = 'متجرنا الإلكتروني';
        try {
            $company = CompanySetting::first();
            if ($company && ! empty($company->company_name)) {
                $defaultName = $company->company_name;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return static::firstOrCreate([], [
            'store_name' => $defaultName,
            'store_tagline' => 'أفضل المنتجات وأسرع توصيل حتى باب المنزل',
            'shipping_flat_rate' => 30.00,
            'free_shipping_threshold' => 500.00,
            'is_store_open' => true,
            'allow_cod' => true,
            'announcement_text' => 'أهلاً بكم في متجرنا! التوصيل لجميع المحافظات والدفع عند الاستلام.',
        ]);
    }
}
