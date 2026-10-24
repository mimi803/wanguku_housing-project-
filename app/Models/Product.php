<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    public $timestamps = false;
    public const CATEGORIES = [
        'kienyeji_eggs' => 'Farm eggs',
        'layer_eggs' => 'Layer eggs',
        'hatching_eggs' => 'Hatching eggs',
        'poultry_birds' => 'Chicken',
        'chicks' => 'Day-old chicks',
        'fish' => 'Farmed fish',
        'vegetables' => 'Fresh vegetables',
    ];

    protected $fillable = ['farmer_id', 'title', 'category', 'price', 'stock', 'description', 'image_url'];

    public function farmer() { return $this->belongsTo(User::class, 'farmer_id'); }
    public function orders() { return $this->hasMany(Order::class); }

    public function getImageDisplayUrlAttribute(): ?string
    {
        $value = $this->image_url;
        if (! $value) return null;

        $path = parse_url($value, PHP_URL_PATH) ?: $value;
        if (Str::contains($path, '/storage/')) {
            $storedPath = Str::after($path, '/storage/');
            return Storage::disk('public')->exists($storedPath) ? asset('storage/'.$storedPath) : null;
        }
        if (Str::startsWith($value, ['http://', 'https://'])) return $value;

        $storedPath = ltrim($path, '/');
        if (Str::startsWith($storedPath, 'storage/')) $storedPath = Str::after($storedPath, 'storage/');
        if (Str::startsWith($storedPath, 'products/')) {
            return Storage::disk('public')->exists($storedPath) ? asset('storage/'.$storedPath) : null;
        }

        return asset($storedPath);
    }
}
