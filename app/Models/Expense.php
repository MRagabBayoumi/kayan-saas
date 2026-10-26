<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CHEQUE = 'cheque';

    public const METHOD_CARD = 'card';

    protected $fillable = [
        'expense_number',
        'expense_category_id',
        'treasury_id',
        'cashier_shift_id',
        'amount',
        'expense_date',
        'payment_method',
        'paid_to',
        'reference_number',
        'notes',
        'receipt_attachment',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            self::METHOD_CASH => 'نقداً',
            self::METHOD_BANK_TRANSFER => 'تحويل بنكي',
            self::METHOD_CHEQUE => 'شيك',
            self::METHOD_CARD => 'فيزا / بطاقة',
            default => $this->payment_method,
        };
    }

    public static function generateExpenseNumber(): string
    {
        $prefix = 'EXP-'.now()->format('Ymd').'-';
        $last = static::where('expense_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('expense_number');

        $nextSeq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = end($parts);
            if (is_numeric($seq)) {
                $nextSeq = ((int) $seq) + 1;
            }
        }

        return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
