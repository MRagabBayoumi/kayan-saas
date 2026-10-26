<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory;

    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    public const NATURE_DEBIT = 'debit';

    public const NATURE_CREDIT = 'credit';

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'parent_id',
        'type',
        'nature',
        'level',
        'is_leaf',
        'is_system',
        'system_role',
        'is_active',
        'description',
        'current_balance',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_leaf' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'current_balance' => 'decimal:2',
        ];
    }

    /**
     * Parent account relationship.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Immediate child accounts relationship.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    /**
     * Journal entry lines referencing this account.
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Scope for active accounts.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for leaf accounts (capable of receiving entries).
     */
    public function scopeLeaves(Builder $query): Builder
    {
        return $query->where('is_leaf', true);
    }

    /**
     * Scope accounts by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Find an account by its system role.
     */
    public static function byRole(string $role): ?self
    {
        return static::where('system_role', $role)->first();
    }

    /**
     * Get human-readable Arabic label for account type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_ASSET => 'أصول (Assets)',
            self::TYPE_LIABILITY => 'خصوم / التزامات (Liabilities)',
            self::TYPE_EQUITY => 'حقوق ملكية (Equity)',
            self::TYPE_REVENUE => 'إيرادات (Revenue)',
            self::TYPE_EXPENSE => 'مصروفات (Expenses)',
            default => $this->type,
        };
    }

    /**
     * Get human-readable Arabic label for normal balance nature.
     */
    public function getNatureLabelAttribute(): string
    {
        return match ($this->nature) {
            self::NATURE_DEBIT => 'مدين بطبيعته (Debit)',
            self::NATURE_CREDIT => 'دائن بطبيعته (Credit)',
            default => $this->nature,
        };
    }

    /**
     * Badge class for UI rendering.
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_ASSET => 'erp-badge-info',
            self::TYPE_LIABILITY => 'erp-badge-warning',
            self::TYPE_EQUITY => 'erp-badge-primary',
            self::TYPE_REVENUE => 'erp-badge-success',
            self::TYPE_EXPENSE => 'erp-badge-danger',
            default => 'erp-badge-neutral',
        };
    }

    /**
     * Calculate and refresh account balance from journal lines.
     */
    public function recalculateBalance(): float
    {
        if ($this->is_leaf) {
            $totals = DB::table('journal_entry_lines')
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entry_lines.account_id', $this->id)
                ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
                ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit, COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
                ->first();

            $totalDebit = (float) ($totals->total_debit ?? 0);
            $totalCredit = (float) ($totals->total_credit ?? 0);

            $balance = $this->nature === self::NATURE_CREDIT
                ? round($totalCredit - $totalDebit, 2)
                : round($totalDebit - $totalCredit, 2);

            $this->update(['current_balance' => $balance]);

            return $balance;
        }

        // Parent account balance = sum of leaf descendants
        $descendantIds = $this->getAllDescendantIds();
        $totalBalance = 0.0;

        if (! empty($descendantIds)) {
            $descendants = Account::whereIn('id', $descendantIds)->where('is_leaf', true)->get();
            foreach ($descendants as $desc) {
                $totalBalance += $desc->recalculateBalance();
            }
        }

        $this->update(['current_balance' => round($totalBalance, 2)]);

        return round($totalBalance, 2);
    }

    /**
     * Recursively retrieve all descendant account IDs.
     */
    public function getAllDescendantIds(): array
    {
        $ids = [];
        $children = $this->children()->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }

        return $ids;
    }
}
