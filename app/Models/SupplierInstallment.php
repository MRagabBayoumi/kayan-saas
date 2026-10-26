<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInstallment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'supplier_id',
        'purchase_invoice_id',
        'installment_title',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_installment_id')->latest('payment_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0.00, round((float) $this->amount - (float) $this->paid_amount, 2));
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === self::STATUS_PAID) {
            return false;
        }

        return $this->due_date instanceof Carbon && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    public function getIsDueTodayAttribute(): bool
    {
        if ($this->status === self::STATUS_PAID) {
            return false;
        }

        return $this->due_date instanceof Carbon && $this->due_date->isToday();
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_PAID) {
            return 'مسدد بالكامل';
        }

        if ($this->is_overdue) {
            return 'متأخر السداد';
        }

        if ($this->is_due_today) {
            return 'مستحق اليوم';
        }

        return match ($this->status) {
            self::STATUS_PARTIAL => 'مسدد جزئياً',
            default => 'مجدول / قيد الانتظار',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->status === self::STATUS_PAID) {
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        }

        if ($this->is_overdue) {
            return 'bg-rose-50 text-rose-700 border-rose-200';
        }

        if ($this->is_due_today) {
            return 'bg-amber-50 text-amber-700 border-amber-200';
        }

        return match ($this->status) {
            self::STATUS_PARTIAL => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            default => 'bg-sky-50 text-sky-700 border-sky-200',
        };
    }

    public function recordPayment(float $paymentAmount): void
    {
        $this->paid_amount = round((float) $this->paid_amount + $paymentAmount, 2);

        if ($this->remaining_amount <= 0.0001) {
            $this->status = self::STATUS_PAID;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_PAID);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_PAID)
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->where('status', '!=', self::STATUS_PAID)
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }
}
