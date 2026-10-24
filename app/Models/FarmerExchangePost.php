<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerExchangePost extends Model
{
    protected $fillable = ['farmer_id', 'post_kind', 'resource_type', 'title', 'details', 'quantity', 'unit', 'location', 'status'];
    protected $casts = ['quantity' => 'decimal:2'];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }
}
