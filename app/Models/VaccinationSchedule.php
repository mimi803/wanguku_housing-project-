<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VaccinationSchedule extends Model
{
    public $timestamps = false;
    protected $fillable = ['farmer_id', 'batch_name', 'vaccine_name', 'due_date', 'completed'];
    protected $casts = ['due_date' => 'date', 'completed' => 'boolean'];

    public function farmer() { return $this->belongsTo(User::class, 'farmer_id'); }
}
