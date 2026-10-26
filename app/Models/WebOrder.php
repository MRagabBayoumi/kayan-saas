<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_city',
        'customer_address',
        'customer_notes',
        'subtotal',
        'shipping_cost',
        'discount_amount',
        'total',
        'status',
        'payment_method',
        'payment_status',
        'sales_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(WebOrderItem::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'WO-'.date('Ymd').'-';
        $lastOrder = static::where('order_number', 'LIKE', $prefix.'%')
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($lastOrder && preg_match('/-(\d+)$/', $lastOrder->order_number, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending' => ['bg' => 'bg-amber-100 text-amber-800 border-amber-300', 'label' => 'قيد الانتظار', 'icon' => 'clock'],
            'confirmed' => ['bg' => 'bg-blue-100 text-blue-800 border-blue-300', 'label' => 'تم التأكيد', 'icon' => 'check-circle'],
            'processing' => ['bg' => 'bg-indigo-100 text-indigo-800 border-indigo-300', 'label' => 'جاري التجهيز', 'icon' => 'package'],
            'shipped' => ['bg' => 'bg-purple-100 text-purple-800 border-purple-300', 'label' => 'تم الشحن', 'icon' => 'truck'],
            'delivered' => ['bg' => 'bg-emerald-100 text-emerald-800 border-emerald-300', 'label' => 'تم التوصيل', 'icon' => 'check-check'],
            'cancelled' => ['bg' => 'bg-rose-100 text-rose-800 border-rose-300', 'label' => 'ملغي', 'icon' => 'x-circle'],
            default => ['bg' => 'bg-slate-100 text-slate-700 border-slate-300', 'label' => $this->status, 'icon' => 'help-circle'],
        };
    }
}
