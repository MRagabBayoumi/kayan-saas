<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'packing_list_id',
        'product_id',
        'sku',
        'barcode',
        'product_name',
        'category_name',
        'unit',
        'quantity',
        'packages_count',
        'unit_price',
        'total_amount',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'packages_count' => 'integer',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function packingList(): BelongsTo
    {
        return $this->belongsTo(PackingList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
