<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Livestock extends Model
{
    use HasFactory;

    protected $fillable = [
        'ear_tag',
        'breed_type',
        'gender',
        'birth_date',
        'initial_weight',
        'health_status',
        'notes',
        'status',
        'pen_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'initial_weight' => 'decimal:2',
        'status' => 'boolean',
    ];

    protected $appends = [
        'current_weight',
        'average_daily_gain',
        'age_days',
    ];

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    public function weightRecords(): HasMany
    {
        return $this->hasMany(WeightRecord::class)->orderBy('record_date', 'desc');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    public function feedingRecords(): HasMany
    {
        return $this->hasMany(FeedingRecord::class);
    }

    public function getCurrentWeightAttribute(): float
    {
        $latest = $this->weightRecords()->latest('record_date')->first();
        return $latest ? (float) $latest->weight_kg : (float) $this->initial_weight;
    }

    public function getAverageDailyGainAttribute(): float
    {
        $first = $this->weightRecords()->oldest('record_date')->first();
        $last = $this->weightRecords()->latest('record_date')->first();

        if (!$first || !$last || $first->id === $last->id) {
            return 0.0;
        }

        $days = $first->record_date->diffInDays($last->record_date);
        if ($days === 0) {
            return 0.0;
        }

        $gain = $last->weight_kg - $first->weight_kg;
        return round($gain / $days, 3);
    }

    public function getAgeDaysAttribute(): int
    {
        return $this->birth_date->diffInDays(now());
    }
}
