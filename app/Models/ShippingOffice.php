<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingOffice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'shipping_type',
        'contact_person',
        'phone',
        'phone_alt',
        'address',
        'rate_per_cbm_or_kg',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_cbm_or_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInternational(Builder $query): Builder
    {
        return $query->where('shipping_type', 'international_china');
    }

    public function scopeDomestic(Builder $query): Builder
    {
        return $query->where('shipping_type', 'domestic_egypt');
    }

    public function getTypeLabel(): string
    {
        return match ($this->shipping_type) {
            'international_china' => 'شحن دولي (الصين)',
            'domestic_egypt' => 'شحن محلي (المحافظات)',
            default => 'مكتب شحن',
        };
    }
}
