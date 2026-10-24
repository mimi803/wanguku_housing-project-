<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmSensorReading extends Model
{
    public $timestamps = false;
    protected $fillable = ['device_id', 'reading_value', 'captured_at', 'alert_triggered', 'created_at'];
    protected $casts = ['reading_value' => 'decimal:3', 'captured_at' => 'datetime', 'alert_triggered' => 'boolean'];

    public function device() { return $this->belongsTo(FarmDevice::class, 'device_id'); }
}
