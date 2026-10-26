<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'unit_id',
        'conversion_factor',
        'quantity',
        'base_quantity',
        'unit_price',
        'line_discount',
        'subtotal',
        'total_cogs',
        'total_profit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
            'quantity' => 'decimal:4',
            'base_quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'line_discount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total_cogs' => 'decimal:2',
            'total_profit' => 'decimal:2',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function batchAllocations(): HasMany
    {
        return $this->hasMany(SalesInvoiceItemBatch::class);
    }
}
