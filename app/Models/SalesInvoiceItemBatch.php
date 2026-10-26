<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItemBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_item_id',
        'inventory_batch_id',
        'quantity_base',
        'unit_cost_base',
        'total_cogs',
        'batch_tier_price_base',
        'actual_selling_price_base',
        'subtotal_sold',
        'profit_margin',
        'is_reversed',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_base' => 'decimal:4',
            'unit_cost_base' => 'decimal:4',
            'total_cogs' => 'decimal:2',
            'batch_tier_price_base' => 'decimal:4',
            'actual_selling_price_base' => 'decimal:4',
            'subtotal_sold' => 'decimal:2',
            'profit_margin' => 'decimal:2',
            'is_reversed' => 'boolean',
            'reversed_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceItem::class, 'sales_invoice_item_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function getQuantityAttribute(): float
    {
        return (float) $this->quantity_base;
    }

    public function getUnitPriceAttribute(): float
    {
        return (float) $this->actual_selling_price_base;
    }

    public function getUnitCostAttribute(): float
    {
        return (float) $this->unit_cost_base;
    }

    public function getProfitAttribute(): float
    {
        return (float) $this->profit_margin;
    }
}
