<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncubationBatch extends Model
{
    public $timestamps = false;
    protected $fillable = ['farmer_id', 'batch_name', 'egg_count', 'start_date', 'day7_candling', 'day14_candling', 'hatch_date', 'hatched_count', 'completed'];
    protected $casts = ['start_date' => 'date', 'day7_candling' => 'date', 'day14_candling' => 'date', 'hatch_date' => 'date', 'completed' => 'boolean'];

    public function farmer() { return $this->belongsTo(User::class, 'farmer_id'); }
}
