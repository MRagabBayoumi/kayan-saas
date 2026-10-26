<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'supplier_type',
        'contact_person',
        'phone',
        'wechat_id',
        'email',
        'country',
        'city',
        'address',
        'currency',
        'opening_balance',
        'current_balance',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeChinaFactories(Builder $query): Builder
    {
        return $query->where('supplier_type', 'china_factory');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('contact_person', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('wechat_id', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%");
        });
    }

    public function getTypeLabel(): string
    {
        return match ($this->supplier_type) {
            'china_factory' => 'مصنع (الصين)',
            'local_supplier' => 'مورد محلي',
            'shipping_agent' => 'وكيل شحن / تخليص',
            default => 'مورد',
        };
    }

    public function getCurrencySymbol(): string
    {
        return match ($this->currency) {
            'CNY' => '¥ (إيوان)',
            'USD' => '$ (دولار)',
            'EGP' => 'ج.م',
            default => $this->currency,
        };
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class)->latest('invoice_date');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class)->latest('return_date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class)->latest('payment_date');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SupplierInstallment::class)->orderBy('due_date');
    }
}
