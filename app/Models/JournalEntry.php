<?php

namespace App\Models;

use App\Services\DocumentSequenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    public const REF_SALES_INVOICE = 'sales_invoice';

    public const REF_PURCHASE_INVOICE = 'purchase_invoice';

    public const REF_SALES_RETURN = 'sales_return';

    public const REF_PURCHASE_RETURN = 'purchase_return';

    public const REF_CUSTOMER_PAYMENT = 'customer_payment';

    public const REF_SUPPLIER_PAYMENT = 'supplier_payment';

    public const REF_EXPENSE = 'expense';

    public const REF_OPENING_BALANCE = 'opening_balance';

    public const REF_MANUAL = 'manual';

    protected $fillable = [
        'entry_number',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'total_debit',
        'total_credit',
        'created_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
        ];
    }

    /**
     * Lines belonging to this journal entry.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_order');
    }

    /**
     * Creator of the entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for posted entries.
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    /**
     * Check if total debits equal total credits within 2 decimal places.
     */
    public function isBalanced(): bool
    {
        return abs((float) $this->total_debit - (float) $this->total_credit) < 0.001;
    }

    /**
     * Generate next sequential entry number.
     */
    public static function generateEntryNumber(): string
    {
        return DocumentSequenceService::getNextNumber('journal_entry', 'JV-', 6);
    }

    /**
     * Human-readable label for reference type.
     */
    public function getReferenceTypeLabelAttribute(): string
    {
        return match ($this->reference_type) {
            self::REF_SALES_INVOICE => 'فاتورة مبيعات',
            self::REF_PURCHASE_INVOICE => 'فاتورة مشتريات',
            self::REF_SALES_RETURN => 'مردود مبيعات',
            self::REF_PURCHASE_RETURN => 'مردود مشتريات',
            self::REF_CUSTOMER_PAYMENT => 'سند قبض عميل',
            self::REF_SUPPLIER_PAYMENT => 'سند صرف مورد',
            self::REF_EXPENSE => 'سند مصروفات',
            self::REF_OPENING_BALANCE => 'قيد رصيد افتتاحي',
            default => 'قيد يومية تسوية / يدوي',
        };
    }

    /**
     * Badge CSS class for entry status.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_POSTED => 'erp-badge-success',
            self::STATUS_DRAFT => 'erp-badge-warning',
            self::STATUS_REVERSED => 'erp-badge-danger',
            default => 'erp-badge-neutral',
        };
    }

    /**
     * Status human-readable Arabic label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_POSTED => 'مُرحل ومعتمد',
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_REVERSED => 'ملغى / معكوس',
            default => $this->status,
        };
    }

    /**
     * Resolve URL for the source business document if applicable.
     */
    public function getSourceDocumentUrlAttribute(): ?string
    {
        if (! $this->reference_id) {
            return null;
        }

        return match ($this->reference_type) {
            self::REF_SALES_INVOICE => route('sales.show', $this->reference_id),
            self::REF_PURCHASE_INVOICE => route('purchases.show', $this->reference_id),
            self::REF_SALES_RETURN => route('sales-returns.show', $this->reference_id),
            self::REF_PURCHASE_RETURN => route('purchase-returns.show', $this->reference_id),
            self::REF_EXPENSE => route('expenses.voucher', $this->reference_id),
            self::REF_SUPPLIER_PAYMENT => route('supplier-payments.receipt', $this->reference_id),
            default => null,
        };
    }
}
