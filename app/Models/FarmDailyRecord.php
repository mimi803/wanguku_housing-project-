<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmDailyRecord extends Model
{
    protected $fillable = [
        'farmer_id', 'recorded_on', 'flock_headcount', 'eggs_collected',
        'feed_kg', 'water_litres', 'deaths', 'notes',
    ];

    protected $casts = [
        'recorded_on' => 'date',
        'flock_headcount' => 'integer',
        'eggs_collected' => 'integer',
        'feed_kg' => 'decimal:3',
        'water_litres' => 'decimal:3',
        'deaths' => 'integer',
    ];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }
}
