<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmSystemPlan extends Model
{
    protected $fillable = ['farmer_id', 'floor_number', 'system_type', 'name', 'stage', 'target_year', 'notes'];
    protected $casts = ['floor_number' => 'integer', 'target_year' => 'integer'];

    public function farmer() { return $this->belongsTo(User::class, 'farmer_id'); }
}
