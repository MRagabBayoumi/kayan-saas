<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarcodeSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'barcode_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'barcode_prefix',
        'printer_name',
        'label_width_mm',
        'label_height_mm',
        'barcode_width_px',
        'barcode_height_px',
        'product_font_size',
        'details_font_size',
        'show_company_name',
        'company_name_override',
        'show_product_name',
        'show_barcode_bars',
        'show_item_sku',
        'print_price_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'label_width_mm' => 'integer',
        'label_height_mm' => 'integer',
        'barcode_width_px' => 'integer',
        'barcode_height_px' => 'integer',
        'product_font_size' => 'integer',
        'details_font_size' => 'integer',
        'show_company_name' => 'boolean',
        'show_product_name' => 'boolean',
        'show_barcode_bars' => 'boolean',
        'show_item_sku' => 'boolean',
    ];

    /**
     * Get the global singleton barcode settings instance.
     */
    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'barcode_prefix' => '02',
            'printer_name' => 'Xprinter XP-233B',
            'label_width_mm' => 38,
            'label_height_mm' => 25,
            'barcode_width_px' => 260,
            'barcode_height_px' => 80,
            'product_font_size' => 14,
            'details_font_size' => 8,
            'show_company_name' => true,
            'company_name_override' => 'شركة كيان للتجارة و التوزيع',
            'show_product_name' => true,
            'show_barcode_bars' => true,
            'show_item_sku' => true,
            'print_price_type' => 'none',
        ]);
    }

    /**
     * Get the company name to be printed on labels.
     */
    public function getEffectiveCompanyName(): string
    {
        if (! empty($this->company_name_override)) {
            return trim($this->company_name_override);
        }

        $companySetting = CompanySetting::first();
        if ($companySetting && ! empty($companySetting->company_name)) {
            return trim($companySetting->company_name);
        }

        return 'شركة كيان للتجارة و التوزيع';
    }
}
