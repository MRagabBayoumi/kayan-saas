<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SalesRepVisit extends Model
{
    use HasFactory;

    public const PURPOSE_ORDER = 'order';

    public const PURPOSE_COLLECTION = 'collection';

    public const PURPOSE_FOLLOWUP = 'followup';

    public const PURPOSE_INTRODUCE = 'introduce';

    public const PURPOSE_COMPLAINT = 'complaint';

    public const PURPOSE_ROUTINE = 'routine';

    public const PURPOSES = [
        self::PURPOSE_ROUTINE => 'زيارة دورية اعتيادية',
        self::PURPOSE_ORDER => 'عرض منتجات وطلب بضاعة',
        self::PURPOSE_COLLECTION => 'تحصيل مديونية / دفعة نقدية',
        self::PURPOSE_FOLLOWUP => 'متابعة طلبيات ومبيعات سابقة',
        self::PURPOSE_INTRODUCE => 'تعريف بمنتجات جديدة وعروض',
        self::PURPOSE_COMPLAINT => 'معالجة شكوى أو مرتجع',
    ];

    public const OUTCOME_ORDER_PLACED = 'order_placed';

    public const OUTCOME_PROMISED_ORDER = 'promised_order';

    public const OUTCOME_PAYMENT_COLLECTED = 'payment_collected';

    public const OUTCOME_SHOP_CLOSED = 'shop_closed';

    public const OUTCOME_DECISION_MAKER_AWAY = 'decision_maker_away';

    public const OUTCOME_REFUSED = 'refused';

    public const OUTCOMES = [
        self::OUTCOME_ORDER_PLACED => 'تم تسجيل طلب بيع بنجاح',
        self::OUTCOME_PROMISED_ORDER => 'وعد بطلب قادم / تجهيز كشف',
        self::OUTCOME_PAYMENT_COLLECTED => 'تم تحصيل دفعة نقدية',
        self::OUTCOME_SHOP_CLOSED => 'المحل / المعرض مغلق',
        self::OUTCOME_DECISION_MAKER_AWAY => 'المسؤول / المشتري غير متواجد',
        self::OUTCOME_REFUSED => 'اعتذر عن الطلب حالياً',
    ];

    protected $fillable = [
        'sales_rep_id',
        'customer_id',
        'visit_date',
        'check_in_at',
        'check_out_at',
        'duration_minutes',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'purpose',
        'outcome',
        'notes',
        'visit_image_path',
        'sales_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'duration_minutes' => 'integer',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
        ];
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('visit_date', now()->format('Y-m-d'));
    }

    public function scopeForRep(Builder $query, int $repId): Builder
    {
        return $query->where('sales_rep_id', $repId);
    }

    public function getVisitImageUrlAttribute(): ?string
    {
        if ($this->visit_image_path && Storage::disk('public')->exists($this->visit_image_path)) {
            return '/storage/'.$this->visit_image_path;
        }

        return null;
    }

    public function getPurposeLabelAttribute(): string
    {
        return self::PURPOSES[$this->purpose] ?? 'زيارة ميدانية';
    }

    public function getOutcomeLabelAttribute(): string
    {
        return self::OUTCOMES[$this->outcome] ?? ($this->outcome ? 'أخرى' : 'قيد المتابعة');
    }
}
