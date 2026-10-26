<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_CANCELLED = 'cancelled';

    public const SETTLEMENT_DEDUCT_BALANCE = 'deduct_balance';

    public const SETTLEMENT_CASH_REFUND = 'cash_refund';

    protected $fillable = [
        'return_number',
        'supplier_id',
        'warehouse_id',
        'purchase_invoice_id',
        'return_date',
        'status',
        'settlement_type',
        'treasury_id',
        'total_amount',
        'notes',
        'created_by',
        'posted_at',
        'posted_by',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'posted_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public static function generateReturnNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $random = strtoupper(bin2hex(random_bytes(2)));

        do {
            $seq = str_pad((string) (static::whereDate('created_at', Carbon::today())->count() + 1), 4, '0', STR_PAD_LEFT);
            $number = "PR-{$date}-{$seq}-{$random}";
        } while (static::where('return_number', $number)->exists());

        return $number;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_POSTED => 'مُرحل للمخازن',
            self::STATUS_CANCELLED => 'ملغي',
            default => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'erp-badge-neutral',
            self::STATUS_POSTED => 'erp-badge-success',
            self::STATUS_CANCELLED => 'erp-badge-danger',
            default => 'erp-badge-neutral',
        };
    }

    public function getSettlementTypeLabelAttribute(): string
    {
        return match ($this->settlement_type) {
            self::SETTLEMENT_DEDUCT_BALANCE => 'خصم من رصيد المورد (آجل)',
            self::SETTLEMENT_CASH_REFUND => 'استرداد نقدي (خزينة)',
            default => $this->settlement_type,
        };
    }
}
