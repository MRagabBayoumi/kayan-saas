<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'packing_number',
        'packing_date',
        'customer_name',
        'customer_id',
        'warehouse_id',
        'warehouse_name',
        'price_type',
        'show_quantity',
        'show_price',
        'show_packages',
        'total_quantity',
        'total_packages',
        'total_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'packing_date' => 'date',
            'show_quantity' => 'boolean',
            'show_price' => 'boolean',
            'show_packages' => 'boolean',
            'total_quantity' => 'decimal:2',
            'total_packages' => 'integer',
            'total_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackingListItem::class)->orderBy('sort_order');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope query to search by term in document number, customer, or notes.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('packing_number', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%");
        });
    }

    /**
     * Generate next sequential document number (e.g., PL-20261031-0001).
     */
    public static function generateNextNumber(): string
    {
        $datePrefix = 'PL-'.date('Ymd');
        $lastRecord = static::where('packing_number', 'like', "{$datePrefix}-%")
            ->latest('id')
            ->first();

        if (! $lastRecord) {
            return "{$datePrefix}-0001";
        }

        $parts = explode('-', $lastRecord->packing_number);
        $seq = (int) end($parts);

        return sprintf('%s-%04d', $datePrefix, $seq + 1);
    }
}
