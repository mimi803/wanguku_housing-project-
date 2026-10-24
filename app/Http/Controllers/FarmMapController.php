<?php

namespace App\Http\Controllers;

use App\Services\FarmMapLocations;
use Illuminate\Http\Request;

class FarmMapController extends Controller
{
    public function __invoke(Request $request)
    {
        $mapFarmers = app(FarmMapLocations::class)->publicFarms();

        $user = $request->user();
        $customerLocation = $user?->role === 'buyer' && $user->latitude !== null && $user->longitude !== null
            ? ['lat' => (float) $user->latitude, 'lng' => (float) $user->longitude]
            : null;

        return view('map.index', compact('mapFarmers', 'customerLocation'));
    }
}
