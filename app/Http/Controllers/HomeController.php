<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use App\Models\User;
use App\Services\FarmMapLocations;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $categories = [
            'kienyeji_eggs' => 'Farm eggs', 'layer_eggs' => 'Layer eggs',
            'hatching_eggs' => 'Hatching eggs', 'poultry_birds' => 'Chicken', 'chicks' => 'Day-old chicks',
            'fish' => 'Farmed fish', 'vegetables' => 'Fresh vegetables',
        ];
        $category = $request->query('cat');
        if (! array_key_exists($category, $categories)) $category = null;
        $products = Product::with('farmer')->where('stock', '>', 0)
            ->whereHas('farmer', fn ($query) => $query->where('role', 'farmer')->whereNotNull('farmer_verified_at'))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->latest('created_at')->limit(3)->get();
        return view('home', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $category,
            'farmers' => User::where('role', 'farmer')->whereNotNull('farmer_verified_at')->whereNotNull('phone')->latest('id')->limit(6)->get(),
            'mapFarmers' => app(FarmMapLocations::class)->publicFarms(),
            'customerLocation' => $this->customerLocation($request),
            'articles' => Article::latest('created_at')->limit(3)->get(),
        ]);
    }

    private function customerLocation(Request $request): ?array
    {
        $user = $request->user();
        if (! $user || $user->role !== 'buyer' || $user->latitude === null || $user->longitude === null) return null;

        return ['lat' => (float) $user->latitude, 'lng' => (float) $user->longitude];
    }
}
