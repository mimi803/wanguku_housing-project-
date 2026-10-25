<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeedBankListing extends Model
{
    protected $fillable = [
        'farmer_id', 'crop_name', 'variety', 'lot_code', 'provenance',
        'harvest_date', 'germination_tested_at', 'germination_rate',
        'quantity_available', 'unit', 'location', 'storage_notes', 'notes', 'status',
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'germination_tested_at' => 'date',
        'germination_rate' => 'decimal:2',
        'quantity_available' => 'decimal:3',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(SeedBankRequest::class);
    }
}
