<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MortalityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['farmer_id', 'log_date', 'flock_label', 'headcount', 'deaths', 'feed_cost', 'emergency_sales', 'notes'];
    protected $casts = ['log_date' => 'date'];

    public function farmer() { return $this->belongsTo(User::class, 'farmer_id'); }
}
