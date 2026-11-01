<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'name',
        'subdomain',
        'database_name',
        'plan_name',
        'plan_id',
        'max_users',
        'billing_cycle',
        'subscription_price',
        'starts_at',
        'modules',
        'is_active',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'modules' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'starts_at' => 'datetime',
        'max_users' => 'integer',
        'subscription_price' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class)->orderByDesc('payment_date');
    }

    /**
     * Check if a specific module is enabled for this tenant.
     */
    public function hasModule(string $module): bool
    {
        if (empty($this->modules)) {
            return true;
        }

        return (bool) ($this->modules[$module] ?? false);
    }

    /**
     * Check if subscription has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Get remaining days until subscription expires.
     */
    public function daysRemaining(): ?int
    {
        if (! $this->expires_at) {
            return null; // Unlimited / No expiry set
        }

        if ($this->expires_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }

    /**
     * Determine if tenant can add more users based on plan user limit.
     */
    public function canAddUser(int $currentCount): bool
    {
        $limit = $this->max_users ?? 3;

        return $currentCount < $limit;
    }
}
