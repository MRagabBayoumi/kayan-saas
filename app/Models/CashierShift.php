<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierShift extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'shift_number',
        'user_id',
        'warehouse_id',
        'status',
        'opened_at',
        'closed_at',
        'opening_balance',
        'cash_sales_amount',
        'cash_returns_amount',
        'cash_additions',
        'cash_expenses',
        'expected_closing_balance',
        'actual_closing_balance',
        'difference',
        'opening_notes',
        'closing_notes',
        'opened_by',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_balance' => 'decimal:2',
            'cash_sales_amount' => 'decimal:2',
            'cash_returns_amount' => 'decimal:2',
            'cash_additions' => 'decimal:2',
            'cash_expenses' => 'decimal:2',
            'expected_closing_balance' => 'decimal:2',
            'actual_closing_balance' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'cashier_shift_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashierCashMovement::class, 'cashier_shift_id')->orderByDesc('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public static function getActiveShift(int $userId, ?int $warehouseId = null): ?self
    {
        $q = self::open()->where('user_id', $userId);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }

        return $q->latest('id')->first();
    }

    public static function getActiveShiftForUser(?int $userId = null): ?self
    {
        $uid = $userId ?: auth()->id();
        if (! $uid) {
            return null;
        }

        return static::getActiveShift($uid);
    }

    public static function generateShiftNumber(): string
    {
        $todayStr = now()->format('Ymd');
        $prefix = "SHF-{$todayStr}-";
        $count = self::where('shift_number', 'like', "{$prefix}%")->count() + 1;

        return sprintf('%s%04d', $prefix, $count);
    }

    public function calculateExpectedBalance(): float
    {
        return (float) round(
            (float) $this->opening_balance
            + (float) $this->cash_sales_amount
            - (float) $this->cash_returns_amount
            + (float) $this->cash_additions
            - (float) $this->cash_expenses,
            2
        );
    }

    public function recalculateAndSave(): void
    {
        $this->expected_closing_balance = $this->calculateExpectedBalance();
        if ($this->actual_closing_balance !== null) {
            $this->difference = round((float) $this->actual_closing_balance - (float) $this->expected_closing_balance, 2);
        }
        $this->save();
    }

    public function recordCashMovement(string $type, float $amount, ?string $notes = null, ?int $refId = null, ?string $refType = null): CashierCashMovement
    {
        $movement = $this->cashMovements()->create([
            'user_id' => $this->user_id,
            'movement_type' => $type,
            'amount' => $amount,
            'reference_id' => $refId,
            'reference_type' => $refType,
            'notes' => $notes,
            'created_at' => now(),
        ]);

        if ($type === CashierCashMovement::TYPE_CASH_IN) {
            $this->cash_additions = round((float) $this->cash_additions + $amount, 2);
        } elseif ($type === CashierCashMovement::TYPE_CASH_OUT) {
            $this->cash_expenses = round((float) $this->cash_expenses + $amount, 2);
        }

        $this->recalculateAndSave();

        return $movement;
    }

    public function recordCashSale(float $amount, ?int $invoiceId = null): void
    {
        $this->cash_sales_amount = round((float) $this->cash_sales_amount + $amount, 2);
        $this->recalculateAndSave();

        $this->cashMovements()->create([
            'user_id' => $this->user_id,
            'movement_type' => CashierCashMovement::TYPE_SALE,
            'amount' => $amount,
            'reference_id' => $invoiceId,
            'reference_type' => SalesInvoice::class,
            'notes' => "تحصيل نقدي لفاتورة بيع #{$invoiceId}",
            'created_at' => now(),
        ]);
    }

    public function recordCustomerPayment(CustomerPayment $payment): void
    {
        if ($payment->payment_method === CustomerPayment::METHOD_CASH) {
            $this->cash_sales_amount = round((float) $this->cash_sales_amount + (float) $payment->amount, 2);
            $this->recalculateAndSave();

            $this->cashMovements()->create([
                'user_id' => $payment->created_by ?: auth()->id() ?: $this->user_id,
                'movement_type' => CashierCashMovement::TYPE_SALE,
                'amount' => $payment->amount,
                'reference_id' => $payment->id,
                'reference_type' => CustomerPayment::class,
                'notes' => "تحصيل دفعة عميل رقم {$payment->payment_number}".($payment->salesInvoice ? " (فاتورة #{$payment->salesInvoice->invoice_number})" : ''),
                'created_at' => now(),
            ]);
        }
    }

    public function recordCashReturn(float $amount, ?int $returnId = null): void
    {
        $this->cash_returns_amount = round((float) $this->cash_returns_amount + $amount, 2);
        $this->recalculateAndSave();

        $this->cashMovements()->create([
            'user_id' => $this->user_id,
            'movement_type' => CashierCashMovement::TYPE_RETURN,
            'amount' => $amount,
            'reference_id' => $returnId,
            'reference_type' => SalesReturn::class,
            'notes' => "صرف مرتجع نقدي #{$returnId}",
            'created_at' => now(),
        ]);
    }

    public function recordExpense(Expense $expense): void
    {
        $this->recordCashMovement(
            CashierCashMovement::TYPE_CASH_OUT,
            (float) $expense->amount,
            "صرف مصروف: {$expense->category?->name} ({$expense->expense_number})",
            $expense->id,
            Expense::class
        );
    }

    public function recordSupplierPayment(SupplierPayment $payment): void
    {
        $this->recordCashMovement(
            CashierCashMovement::TYPE_CASH_OUT,
            (float) $payment->amount,
            "سند صرف مورد: {$payment->supplier?->name} ({$payment->payment_number})",
            $payment->id,
            SupplierPayment::class
        );
    }

    public function closeShift(float $actualBalance, ?string $notes = null, ?int $closedById = null): void
    {
        $this->expected_closing_balance = $this->calculateExpectedBalance();
        $this->actual_closing_balance = round($actualBalance, 2);
        $this->difference = round((float) $this->actual_closing_balance - (float) $this->expected_closing_balance, 2);
        $this->status = self::STATUS_CLOSED;
        $this->closed_at = now();
        $this->closing_notes = $notes;
        $this->closed_by = $closedById ?: auth()->id() ?: $this->user_id;
        $this->save();

        $this->cashMovements()->create([
            'user_id' => $this->closed_by,
            'movement_type' => CashierCashMovement::TYPE_CLOSING,
            'amount' => $this->actual_closing_balance,
            'reference_id' => $this->id,
            'reference_type' => self::class,
            'notes' => 'جرد فعلي وإغلاق وردية الكاشير'.($this->difference != 0 ? " (فارق: {$this->difference} ج.م)" : ''),
            'created_at' => now(),
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_OPEN ? 'وردية نشطة (مفتوحة)' : 'وردية مغلقة';
    }
}
