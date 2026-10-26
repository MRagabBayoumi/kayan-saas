<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesRepAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_rep_id',
        'date',
        'check_in_time',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_address',
        'check_out_time',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_address',
        'work_duration_minutes',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'work_duration_minutes' => 'integer',
        ];
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('date', now()->format('Y-m-d'));
    }

    public function scopeForRep(Builder $query, int $repId): Builder
    {
        return $query->where('sales_rep_id', $repId);
    }

    public function isCheckedIn(): bool
    {
        return $this->status === 'present' && $this->check_out_time === null;
    }

    public function getFormattedWorkDurationAttribute(): string
    {
        $mins = $this->work_duration_minutes;
        if (! $mins && $this->check_in_time) {
            $mins = (int) $this->check_in_time->diffInMinutes($this->check_out_time ?? now());
        }

        $hours = floor($mins / 60);
        $remainingMins = $mins % 60;

        if ($hours > 0) {
            return "{$hours} س و {$remainingMins} د";
        }

        return "{$remainingMins} دقيقة";
    }
}
