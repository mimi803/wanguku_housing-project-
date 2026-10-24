<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmOperationLog extends Model
{
    protected $table = 'farm_operation_logs';
    public $timestamps = false;
    protected $fillable = ['farmer_id', 'operation_type', 'title', 'recorded_at', 'quantity', 'unit', 'status', 'flock_batch', 'notes'];
    protected $casts = ['recorded_at' => 'datetime', 'quantity' => 'decimal:3'];

    public function farmer() { return $this->belongsTo(User::class, 'farmer_id'); }
}
