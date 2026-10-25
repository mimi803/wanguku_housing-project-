<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\FarmMapLocations;
use Illuminate\Http\Request;

class BuyerAccountController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('account.index', [
            'products' => Product::with('farmer')->where('stock', '>', 0)->whereHas('farmer', fn ($query) => $query->where('role', 'farmer')->whereNotNull('farmer_verified_at'))->latest('created_at')->limit(8)->get(),
            'categories' => Product::CATEGORIES,
            'mapFarmers' => app(FarmMapLocations::class)->publicFarms(),
            'customerLocation' => $request->user()->latitude !== null && $request->user()->longitude !== null
                ? ['lat' => (float) $request->user()->latitude, 'lng' => (float) $request->user()->longitude]
                : null,
        ]);
    }
}
