<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'current_stock',
        'price_per_kg',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'is_stock_low',
    ];

    public function feedingRecords(): HasMany
    {
        return $this->hasMany(FeedingRecord::class);
    }

    public function getIsStockLowAttribute(): bool
    {
        // Threshold 100 kg
        return $this->current_stock < 100;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
