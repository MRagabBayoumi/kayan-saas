<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'from_treasury_id',
        'to_treasury_id',
        'amount',
        'transfer_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transfer_date' => 'date',
        ];
    }

    public function fromTreasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class, 'from_treasury_id');
    }

    public function toTreasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class, 'to_treasury_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateNumber(): string
    {
        $prefix = 'TRF-'.now()->format('Ymd').'-';
        $last = static::where('transfer_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('transfer_number');

        $nextSeq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = end($parts);
            if (is_numeric($seq)) {
                $nextSeq = ((int) $seq) + 1;
            }
        }

        return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
