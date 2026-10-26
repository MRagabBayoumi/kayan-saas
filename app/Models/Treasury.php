<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Treasury extends Model
{
    use HasFactory;

    public const TYPE_CASH = 'cash';

    public const TYPE_BANK = 'bank';

    public const TYPE_WALLET = 'wallet';

    protected $fillable = [
        'name',
        'type',
        'bank_name',
        'account_number',
        'opening_balance',
        'current_balance',
        'is_default',
        'is_active',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class)->orderByDesc('transaction_date')->orderByDesc('id');
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(TreasuryTransfer::class, 'from_treasury_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(TreasuryTransfer::class, 'to_treasury_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BANK => 'حساب بنكي',
            self::TYPE_WALLET => 'محفظة إلكترونية',
            default => 'خزينة نقدية',
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BANK => 'bg-blue-100 text-blue-800 border-blue-200',
            self::TYPE_WALLET => 'bg-purple-100 text-purple-800 border-purple-200',
            default => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        };
    }

    /**
     * Record a deposit into this treasury atomically.
     */
    public function recordDeposit(
        float $amount,
        string $sourceType,
        ?int $sourceId,
        string $description,
        ?int $userId = null,
        ?string $date = null
    ): TreasuryTransaction {
        return DB::transaction(function () use ($amount, $sourceType, $sourceId, $description, $userId, $date) {
            $this->lockForUpdate();
            $newBalance = (float) $this->current_balance + $amount;
            $this->current_balance = $newBalance;
            $this->save();

            return TreasuryTransaction::create([
                'transaction_number' => TreasuryTransaction::generateNumber(),
                'treasury_id' => $this->id,
                'type' => TreasuryTransaction::TYPE_DEPOSIT,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'transaction_date' => $date ?: now()->toDateString(),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'created_by' => $userId ?: auth()->id(),
            ]);
        });
    }

    /**
     * Record a withdrawal from this treasury atomically.
     */
    public function recordWithdrawal(
        float $amount,
        string $sourceType,
        ?int $sourceId,
        string $description,
        ?int $userId = null,
        ?string $date = null
    ): TreasuryTransaction {
        return DB::transaction(function () use ($amount, $sourceType, $sourceId, $description, $userId, $date) {
            $this->lockForUpdate();
            $newBalance = (float) $this->current_balance - $amount;
            $this->current_balance = $newBalance;
            $this->save();

            return TreasuryTransaction::create([
                'transaction_number' => TreasuryTransaction::generateNumber(),
                'treasury_id' => $this->id,
                'type' => TreasuryTransaction::TYPE_WITHDRAWAL,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'transaction_date' => $date ?: now()->toDateString(),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'created_by' => $userId ?: auth()->id(),
            ]);
        });
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::where('type', self::TYPE_CASH)->first()
            ?? static::first();
    }
}
