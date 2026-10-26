<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    /**
     * Supported document types.
     */
    public const DOC_SALES_INVOICE = 'sales_invoice';

    public const DOC_PURCHASE_INVOICE = 'purchase_invoice';

    public const DOC_CUSTOMER_STATEMENT = 'customer_statement';

    public const DOC_SUPPLIER_STATEMENT = 'supplier_statement';

    /**
     * Supported paper formats.
     */
    public const FORMAT_A4 = 'a4';

    public const FORMAT_A5 = 'a5';

    public const FORMAT_THERMAL_80MM = 'thermal_80mm';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'print_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'document_type',
        'paper_format',
        'name',
        'is_default',
        'settings',
        'version',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'settings' => 'array',
        'version' => 'integer',
    ];

    /**
     * Scope query to a specific document type.
     */
    public function scopeForDocument(Builder $query, string $documentType): Builder
    {
        return $query->where('document_type', $documentType);
    }

    /**
     * Scope query to a specific paper format.
     */
    public function scopeForFormat(Builder $query, string $paperFormat): Builder
    {
        return $query->where('paper_format', $paperFormat);
    }

    /**
     * Scope query to default templates only.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Safe nested setting getter.
     */
    public function getSetting(string $path, mixed $default = null): mixed
    {
        return data_get($this->settings, $path, $default);
    }

    /**
     * Get list of supported document types with Arabic labels.
     *
     * @return array<string, string>
     */
    public static function getDocumentTypeLabels(): array
    {
        return [
            self::DOC_SALES_INVOICE => 'فاتورة مبيعات',
            self::DOC_PURCHASE_INVOICE => 'فاتورة مشتريات',
            self::DOC_CUSTOMER_STATEMENT => 'كشف حساب عميل',
            self::DOC_SUPPLIER_STATEMENT => 'كشف حساب مورد',
        ];
    }

    /**
     * Get list of supported paper formats with Arabic labels.
     *
     * @return array<string, string>
     */
    public static function getPaperFormatLabels(): array
    {
        return [
            self::FORMAT_A4 => 'ورق قياسي A4',
            self::FORMAT_A5 => 'ورق قياسي A5 (نصف A4)',
            self::FORMAT_THERMAL_80MM => 'شريط حراري 80 مم (Thermal)',
        ];
    }
}
