<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'capacity',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    protected $appends = [
        'current_occupancy',
    ];

    public function livestocks(): HasMany
    {
        return $this->hasMany(Livestock::class)->where('status', true);
    }

    public function getCurrentOccupancyAttribute(): int
    {
        return $this->livestocks()->count();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
