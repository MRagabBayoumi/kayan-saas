<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CHEQUE = 'cheque';

    public const METHOD_CARD = 'card';

    protected $fillable = [
        'payment_number',
        'supplier_id',
        'purchase_invoice_id',
        'supplier_installment_id',
        'treasury_id',
        'cashier_shift_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
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

    public function installment(): BelongsTo
    {
        return $this->belongsTo(SupplierInstallment::class, 'supplier_installment_id');
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
            self::METHOD_CHEQUE => 'شيك مصرفي',
            self::METHOD_CARD => 'بطاقة / فيزا',
            default => $this->payment_method,
        };
    }

    public function getPaymentMethodBadgeClassAttribute(): string
    {
        return match ($this->payment_method) {
            self::METHOD_CASH => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::METHOD_BANK_TRANSFER => 'bg-blue-50 text-blue-700 border-blue-200',
            self::METHOD_CHEQUE => 'bg-amber-50 text-amber-700 border-amber-200',
            self::METHOD_CARD => 'bg-purple-50 text-purple-700 border-purple-200',
            default => 'bg-gray-50 text-gray-700 border-gray-200',
        };
    }

    public static function generatePaymentNumber(): string
    {
        $prefix = 'SPAY-'.now()->format('Ymd').'-';
        $last = static::where('payment_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('payment_number');

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
