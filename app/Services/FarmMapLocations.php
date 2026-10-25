<?php

namespace App\Services;

use App\Models\User;

class FarmMapLocations
{
    public function publicFarms(): array
    {
        return User::query()
            ->where('role', 'farmer')
            ->whereNotNull('farmer_verified_at')
            ->where('show_on_map', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['products' => fn ($query) => $query->where('stock', '>', 0)->select('id', 'farmer_id', 'title', 'category', 'price')])
            ->get(['id', 'name', 'location_name', 'latitude', 'longitude'])
            ->map(fn (User $farmer) => [
                'name' => $farmer->name,
                'location' => $farmer->location_name,
                'lat' => (float) $farmer->latitude,
                'lng' => (float) $farmer->longitude,
                'market_url' => route('shop', ['farmer' => $farmer->id]),
                'products' => $farmer->products->map(fn ($product) => [
                    'title' => $product->title,
                    'category' => $product->category,
                    'price' => (float) $product->price,
                ])->all(),
            ])->all();
    }
}
