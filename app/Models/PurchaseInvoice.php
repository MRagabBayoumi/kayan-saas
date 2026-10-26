<?php

namespace App\Models;

use App\Services\DocumentSequenceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CREDIT = 'credit';

    public const PAYMENT_PARTIAL = 'partial';

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'warehouse_id',
        'invoice_date',
        'payment_type',
        'status',
        'subtotal',
        'shipping_cost',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'notes',
        'created_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'posted_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'purchase_invoice_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class)->latest('payment_date');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SupplierInstallment::class)->orderBy('due_date');
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return (float) $this->remaining_amount <= 0.0001;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        if ($this->is_fully_paid) {
            return 'مدفوعة بالكامل';
        }
        if ((float) $this->paid_amount > 0) {
            return 'مدفوعة جزئياً (متبقي '.number_format((float) $this->remaining_amount, 2).' ج.م)';
        }

        return 'آجل غير مسدد';
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        if ($this->is_fully_paid) {
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        }
        if ((float) $this->paid_amount > 0) {
            return 'bg-amber-100 text-amber-800 border-amber-200';
        }

        return 'bg-rose-100 text-rose-800 border-rose-200';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_POSTED => 'مُرحلة للمخازن',
            self::STATUS_CANCELLED => 'ملغاة',
            default => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'erp-badge-warning',
            self::STATUS_POSTED => 'erp-badge-success',
            self::STATUS_CANCELLED => 'erp-badge-danger',
            default => 'erp-badge-secondary',
        };
    }

    public static function generateInvoiceNumber(): string
    {
        return DocumentSequenceService::getNextNumber('purchase_invoice', 'PUR-', 6);
    }
}
