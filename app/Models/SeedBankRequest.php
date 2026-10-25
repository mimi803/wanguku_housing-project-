<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeedBankRequest extends Model
{
    protected $fillable = ['seed_bank_listing_id', 'requester_id', 'quantity', 'unit', 'message', 'status'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(SeedBankListing::class, 'seed_bank_listing_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}
