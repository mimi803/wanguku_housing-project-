<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CircularResourceLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['farmer_id', 'recorded_on', 'resource_type', 'source', 'destination', 'quantity', 'unit', 'review_status', 'notes', 'created_at'];
    protected $casts = ['recorded_on' => 'date', 'quantity' => 'decimal:3'];

    public function farmer() { return $this->belongsTo(User::class, 'farmer_id'); }
}
