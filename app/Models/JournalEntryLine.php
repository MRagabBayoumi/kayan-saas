<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
        'party_type',
        'party_id',
        'line_order',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'line_order' => 'integer',
        ];
    }

    /**
     * Parent journal entry.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Account affected by this entry line.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Customer party if party_type is customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    /**
     * Supplier party if party_type is supplier.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'party_id');
    }

    /**
     * Helper to retrieve related party (Customer, Supplier, etc.)
     */
    public function getPartyAttribute(): mixed
    {
        if (! $this->party_type || ! $this->party_id) {
            return null;
        }

        return match ($this->party_type) {
            'customer' => Customer::find($this->party_id),
            'supplier' => Supplier::find($this->party_id),
            'user' => User::find($this->party_id),
            default => null,
        };
    }
}
