<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmDevice extends Model
{
    protected $fillable = ['farmer_id', 'name', 'sensor_type', 'unit', 'warning_below', 'api_token_hash', 'is_active', 'last_seen_at'];
    protected $hidden = ['api_token_hash'];
    protected $casts = ['warning_below' => 'decimal:3', 'is_active' => 'boolean', 'last_seen_at' => 'datetime'];

    public function readings() { return $this->hasMany(FarmSensorReading::class, 'device_id'); }
    public function farmer() { return $this->belongsTo(User::class, 'farmer_id'); }
}
