<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SaasModule extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $table = 'saas_modules';

    protected $fillable = [
        'code',
        'name',
        'description',
        'features',
        'price_monthly',
        'price_yearly',
        'icon',
        'color',
        'is_active',
        'is_core',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
        'is_core' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('saas_active_modules_catalog');
        });

        static::deleted(function () {
            Cache::forget('saas_active_modules_catalog');
        });
    }

    /**
     * Scope query to only active modules.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to ordered by custom sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Retrieve all active modules with high-speed memory/cache.
     *
     * @return Collection<int, self>
     */
    public static function getActiveCatalog(): Collection
    {
        return Cache::remember('saas_active_modules_catalog', 3600, function () {
            return static::active()->ordered()->get();
        });
    }

    /**
     * Format features into a clean array for display and forms.
     */
    public function getFeaturesListAttribute(): array
    {
        if (is_array($this->features)) {
            return $this->features;
        }

        if (is_string($this->features) && ! empty($this->features)) {
            $decoded = json_decode($this->features, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            return array_filter(array_map('trim', explode("\n", $this->features)));
        }

        return [];
    }
}
