<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const TYPE_TRANSFER = 'transfer';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'movement_number',
        'type',
        'product_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'batch_id',
        'quantity',
        'running_balance',
        'unit_cost',
        'reference_type',
        'reference_id',
        'user_id',
        'customer_id',
        'supplier_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'running_balance' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IN => 'إيداع / وارد',
            self::TYPE_OUT => 'صرف / منصرف',
            self::TYPE_TRANSFER => 'تحويل مخزني',
            self::TYPE_ADJUSTMENT => 'تسوية جردية',
            default => $this->type,
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IN => 'erp-badge-success',
            self::TYPE_OUT => 'erp-badge-danger',
            self::TYPE_TRANSFER => 'erp-badge-info',
            self::TYPE_ADJUSTMENT => 'erp-badge-warning',
            default => 'erp-badge-secondary',
        };
    }

    public static function generateMovementNumber(): string
    {
        $prefix = 'MV-'.date('Ymd').'-';
        $random = strtoupper(bin2hex(random_bytes(2)));
        $count = static::whereDate('created_at', today())->count() + 1;

        return $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT).'-'.$random;
    }
}
