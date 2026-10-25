<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResourceMapClient
{
    public function sync(User $farmer): bool
    {
        if ($farmer->role !== 'farmer' || ! $farmer->farmer_verified_at || ! $farmer->show_on_map || $farmer->latitude === null || $farmer->longitude === null) {
            return $this->remove($farmer);
        }

        if (! $this->configured()) {
            return false;
        }

        $products = $farmer->products()
            ->where('stock', '>', 0)
            ->orderBy('id')
            ->limit(100)
            ->get();

        $payload = [
            'name' => $farmer->name,
            'description' => 'A verified Farmers Connect farm. Browse current produce and contact the farmer through the marketplace.',
            'location_label' => $farmer->location_name,
            'latitude' => (float) $farmer->latitude,
            'longitude' => (float) $farmer->longitude,
            'market_url' => route('shop', ['farmer' => $farmer->id]),
            'products' => $products->map(fn ($product) => [
                'id' => $product->id,
                'title' => $product->title,
                'category' => $product->category,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
                'description' => $product->description,
                'url' => route('shop', ['farmer' => $farmer->id, 'q' => $product->title]),
            ])->all(),
        ];

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->withToken($this->token())
                ->acceptJson()
                ->connectTimeout(2)
                ->timeout(6)
                ->put('/api/v1/integrations/wanguku/farms/'.$farmer->id, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Wanguku farm sync was rejected by Resource Center.', [
                'farmer_id' => $farmer->id,
                'status' => $response->status(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Wanguku farm sync could not reach Resource Center.', [
                'farmer_id' => $farmer->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    public function remove(User $farmer): bool
    {
        if (! $this->configured()) {
            return false;
        }

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->withToken($this->token())
                ->acceptJson()
                ->connectTimeout(2)
                ->timeout(6)
                ->delete('/api/v1/integrations/wanguku/farms/'.$farmer->id);

            return $response->successful();
        } catch (Throwable $exception) {
            Log::warning('Wanguku farm removal could not reach Resource Center.', [
                'farmer_id' => $farmer->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function configured(): bool
    {
        return (bool) config('services.resource_map.enabled')
            && $this->baseUrl() !== ''
            && $this->token() !== '';
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.resource_map.url', ''), '/');
    }

    private function token(): string
    {
        return (string) config('services.resource_map.token', '');
    }
}
