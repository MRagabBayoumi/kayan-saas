<?php

namespace App\Models;

use App\Services\StatementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'company_name',
        'phone',
        'phone_alt',
        'city',
        'address',
        'latitude',
        'longitude',
        'customer_type',
        'pricing_tier',
        'opening_balance',
        'credit_limit',
        'current_balance',
        'notes',
        'is_active',
        'sales_rep_id',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'store_images',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'store_images' => 'array',
        ];
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(SalesRepVisit::class, 'customer_id');
    }

    public function scopeForSalesRep(Builder $query, int $repId): Builder
    {
        return $query->where('sales_rep_id', $repId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    public function getStoreImageUrlsAttribute(): array
    {
        if (empty($this->store_images) || ! is_array($this->store_images)) {
            return [];
        }

        $urls = [];
        foreach ($this->store_images as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                $urls[] = '/storage/'.$path;
            }
        }

        return $urls;
    }

    public function scopeWholesale(Builder $query): Builder
    {
        return $query->where('customer_type', 'wholesale');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('company_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%");
        });
    }

    public function getTypeLabel(): string
    {
        return match ($this->customer_type) {
            'wholesale' => 'تاجر جملة',
            'retail' => 'عميل تجزئة',
            'distributor' => 'موزع معتمد',
            default => 'عميل',
        };
    }

    public function getPricingTierLabel(): string
    {
        return match ($this->pricing_tier) {
            'wholesale' => 'سعر الجملة',
            'retail' => 'سعر التجزئة',
            'distributor' => 'سعر الموزع',
            default => 'سعر الجملة',
        };
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class)->latest('invoice_date');
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class)->latest('payment_date');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(CustomerInstallment::class)->orderBy('due_date');
    }

    /**
     * Recalculate customer's running balance from all posted ledger transactions and sync record.
     */
    public function recalculateCurrentBalance(): float
    {
        $statement = app(StatementService::class)->getCustomerStatement($this);
        $finalBalance = (float) ($statement['final_balance'] ?? 0.00);
        $this->updateQuietly(['current_balance' => $finalBalance]);

        return $finalBalance;
    }
}
