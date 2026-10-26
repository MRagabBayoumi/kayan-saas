<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierCashMovement extends Model
{
    public const TYPE_OPENING = 'opening';

    public const TYPE_SALE = 'sale';

    public const TYPE_RETURN = 'return';

    public const TYPE_CASH_IN = 'cash_in';

    public const TYPE_CASH_OUT = 'cash_out';

    public const TYPE_CLOSING = 'closing';

    public $timestamps = false;

    protected $fillable = [
        'cashier_shift_id',
        'user_id',
        'movement_type',
        'amount',
        'reference_id',
        'reference_type',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getMovementTypeLabelAttribute(): string
    {
        return match ($this->movement_type) {
            self::TYPE_OPENING => 'رصيد بداية الشيفت',
            self::TYPE_SALE => 'تحصيل مبيعات نقدية',
            self::TYPE_RETURN => 'صرف مرتجع مبيعات نقدي',
            self::TYPE_CASH_IN => 'إيداع / عهدة إضافية للدرج',
            self::TYPE_CASH_OUT => 'سحب نقدية / مصروفات',
            self::TYPE_CLOSING => 'جرد وإغلاق الدرج',
            default => $this->movement_type,
        };
    }
}
