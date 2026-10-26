<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'product_id',
        'warehouse_id',
        'purchase_invoice_id',
        'initial_quantity',
        'remaining_quantity',
        'cost_price',
        'retail_price',
        'wholesale_price',
        'distributor_price',
        'total_initial_cost',
        'accumulated_cogs_deducted',
        'unit_id',
        'conversion_factor',
        'source_type',
        'source_id',
        'received_at',
        'is_exhausted',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'initial_quantity' => 'decimal:4',
            'remaining_quantity' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'retail_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'distributor_price' => 'decimal:2',
            'total_initial_cost' => 'decimal:2',
            'accumulated_cogs_deducted' => 'decimal:2',
            'conversion_factor' => 'decimal:4',
            'received_at' => 'datetime',
            'is_exhausted' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public function costMovements(): HasMany
    {
        return $this->hasMany(BatchCostMovement::class, 'batch_id');
    }

    public function salesAllocations(): HasMany
    {
        return $this->hasMany(SalesInvoiceItemBatch::class, 'inventory_batch_id');
    }

    /**
     * Scope query to active (non-exhausted) batches ordered by FIFO (oldest received first).
     */
    public function scopeAvailableFIFO(Builder $query): Builder
    {
        return $query->where('is_exhausted', false)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('received_at', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Determine if this batch has confirmed selling prices assigned.
     */
    public function hasPricing(): bool
    {
        return ! is_null($this->retail_price) && ! is_null($this->wholesale_price);
    }

    /**
     * Get specific price tier per base unit.
     */
    public function getPriceForTier(string $tier = 'retail'): ?float
    {
        $prod = $this->product ?? Product::find($this->product_id);

        $price = match (strtolower(trim($tier))) {
            'wholesale' => $this->wholesale_price ?? $prod?->wholesale_price,
            'distributor' => $this->distributor_price ?? $this->wholesale_price ?? $prod?->distributor_price ?? $prod?->wholesale_price,
            default => $this->retail_price ?? $prod?->retail_price,
        };

        return ! is_null($price) ? (float) $price : null;
    }

    /**
     * Calculate exact COGS deduction with final unit closure reconciliation.
     * Guarantees total_cogs across all partial sales strictly matches total_initial_cost.
     */
    public function calculateExactCogs(float $quantityBase, bool $isFinalDepletion = false): float
    {
        $newRemaining = round((float) $this->remaining_quantity - $quantityBase, 4);

        if ($isFinalDepletion || $newRemaining <= 0.0001) {
            // Final depletion: resolve any penny fraction accumulated from division
            $initialCost = (float) $this->total_initial_cost;
            if ($initialCost <= 0.0) {
                $initialCost = round((float) $this->initial_quantity * (float) $this->cost_price, 2);
            }
            $allocatedSoFar = (float) $this->accumulated_cogs_deducted;
            $cogs = max(0.00, round($initialCost - $allocatedSoFar, 2));
        } else {
            $unitCost = (float) $this->cost_price;
            $cogs = round($quantityBase * $unitCost, 2);
        }

        return $cogs;
    }

    /**
     * Generate unique batch number.
     */
    public static function generateBatchNumber(): string
    {
        $prefix = 'BATCH-'.date('Ymd').'-';
        $random = strtoupper(bin2hex(random_bytes(2)));
        $count = static::whereDate('created_at', today())->count() + 1;

        return $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT).'-'.$random;
    }
}
