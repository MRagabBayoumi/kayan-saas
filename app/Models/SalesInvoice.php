<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesInvoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_VOIDED = 'voided';

    public const TIER_RETAIL = 'retail';

    public const TIER_WHOLESALE = 'wholesale';

    public const TIER_DISTRIBUTOR = 'distributor';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CREDIT = 'credit';

    public const PAYMENT_PARTIAL = 'partial';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'sales_rep_id',
        'warehouse_id',
        'cashier_shift_id',
        'invoice_date',
        'price_tier',
        'payment_type',
        'status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'total_cogs',
        'total_profit',
        'commission_rate',
        'commission_amount',
        'notes',
        'created_by',
        'posted_at',
        'posted_by',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'total_cogs' => 'decimal:2',
            'total_profit' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return match ($this->payment_type) {
            self::PAYMENT_CASH => 'نقدي',
            self::PAYMENT_CREDIT => 'آجل',
            self::PAYMENT_PARTIAL => 'دفع جزئي',
            default => 'نقدي',
        };
    }

    public function getPaymentTypeBadgeClassAttribute(): string
    {
        return match ($this->payment_type) {
            self::PAYMENT_CASH => 'erp-badge-success',
            self::PAYMENT_CREDIT => 'erp-badge-danger',
            self::PAYMENT_PARTIAL => 'erp-badge-warning',
            default => 'erp-badge-neutral',
        };
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return (float) $this->remaining_amount <= 0.001;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        if ($this->is_fully_paid) {
            return 'مدفوعة بالكامل';
        }
        if ((float) $this->paid_amount > 0) {
            return 'مدفوعة جزئياً';
        }

        return 'آجل غير مسدد';
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        if ($this->is_fully_paid) {
            return 'erp-badge-success';
        }
        if ((float) $this->paid_amount > 0) {
            return 'erp-badge-warning';
        }

        return 'erp-badge-danger';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(SalesCommission::class, 'sales_invoice_id');
    }

    public function scopeForSalesRep(Builder $query, int $repId): Builder
    {
        return $query->where('sales_rep_id', $repId);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class)->latest('payment_date');
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.date('Ymd').'-';
        $count = static::whereDate('created_at', today())->count() + 1;

        return $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
