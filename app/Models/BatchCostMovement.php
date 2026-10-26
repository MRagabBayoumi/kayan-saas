<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchCostMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'product_id',
        'warehouse_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity_base',
        'unit_cost_base',
        'total_cost',
        'running_batch_quantity',
        'running_batch_cost_balance',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_base' => 'decimal:4',
            'unit_cost_base' => 'decimal:4',
            'total_cost' => 'decimal:2',
            'running_batch_quantity' => 'decimal:4',
            'running_batch_cost_balance' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
