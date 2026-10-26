<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryTransaction extends Model
{
    use HasFactory;

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    protected $fillable = [
        'transaction_number',
        'treasury_id',
        'type',
        'amount',
        'balance_after',
        'transaction_date',
        'source_type',
        'source_id',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT => 'إيداع / وارد (+)',
            self::TYPE_WITHDRAWAL => 'صرف / منصرف (-)',
            default => $this->type,
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::TYPE_WITHDRAWAL => 'bg-rose-100 text-rose-800 border-rose-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }

    public static function generateNumber(): string
    {
        $prefix = 'TRX-'.now()->format('Ymd').'-';
        $last = static::where('transaction_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('transaction_number');

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
