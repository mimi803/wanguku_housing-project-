<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class BuyerAccountController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('account.index', [
            'products' => Product::with('farmer')->where('stock', '>', 0)->latest('created_at')->limit(8)->get(),
            'categories' => Product::CATEGORIES,
            'mapFarmers' => User::query()->where('role', 'farmer')->where('show_on_map', true)
                ->whereNotNull('latitude')->whereNotNull('longitude')
                ->get(['id', 'name', 'location_name', 'latitude', 'longitude'])
                ->map(fn (User $farmer) => ['name' => $farmer->name, 'location' => $farmer->location_name, 'lat' => (float) $farmer->latitude, 'lng' => (float) $farmer->longitude])->all(),
            'customerLocation' => $request->user()->latitude !== null && $request->user()->longitude !== null
                ? ['lat' => (float) $request->user()->latitude, 'lng' => (float) $request->user()->longitude]
                : null,
        ]);
    }
}
