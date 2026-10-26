<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPayment extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'payment_number',
        'amount',
        'payment_date',
        'payment_method',
        'billing_cycle',
        'months_covered',
        'period_starts_at',
        'period_ends_at',
        'transaction_reference',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'period_starts_at' => 'date',
        'period_ends_at' => 'date',
        'months_covered' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Human-readable payment method label.
     */
    public function getPaymentMethodNameAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'نقدي (كاش)',
            'bank_transfer' => 'تحويل بنكي',
            'vodafone_cash' => 'فودافون كاش / محافظ',
            'instapay' => 'إنستاباي (InstaPay)',
            'visa' => 'بطاقة بنكية / فيزا',
            default => 'أخرى',
        };
    }
}
