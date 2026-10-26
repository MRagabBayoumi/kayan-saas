<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CHEQUE = 'cheque';

    public const METHOD_CARD = 'card';

    protected $fillable = [
        'payment_number',
        'customer_id',
        'sales_invoice_id',
        'customer_installment_id',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(CustomerInstallment::class, 'customer_installment_id');
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
            self::METHOD_CASH => 'نقدي',
            self::METHOD_BANK_TRANSFER => 'تحويل بنكي',
            self::METHOD_CHEQUE => 'شيك بنكي',
            self::METHOD_CARD => 'بطاقة دفع',
            default => 'نقدي',
        };
    }

    public function getPaymentMethodBadgeClassAttribute(): string
    {
        return match ($this->payment_method) {
            self::METHOD_CASH => 'erp-badge-success',
            self::METHOD_BANK_TRANSFER => 'erp-badge-info',
            self::METHOD_CHEQUE => 'erp-badge-warning',
            self::METHOD_CARD => 'erp-badge-neutral',
            default => 'erp-badge-neutral',
        };
    }

    public static function generatePaymentNumber(): string
    {
        $prefix = 'PAY-'.date('Ymd').'-';
        $count = static::whereDate('created_at', today())->count() + 1;

        return $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
