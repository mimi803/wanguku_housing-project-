<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FarmMapController extends Controller
{
    public function __invoke(Request $request)
    {
        $mapFarmers = User::query()->where('role', 'farmer')->where('show_on_map', true)
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->get(['id', 'name', 'location_name', 'latitude', 'longitude'])
            ->map(fn (User $farmer) => [
                'name' => $farmer->name,
                'location' => $farmer->location_name,
                'lat' => (float) $farmer->latitude,
                'lng' => (float) $farmer->longitude,
            ])->all();

        $user = $request->user();
        $customerLocation = $user?->role === 'buyer' && $user->latitude !== null && $user->longitude !== null
            ? ['lat' => (float) $user->latitude, 'lng' => (float) $user->longitude]
            : null;

        return view('map.index', compact('mapFarmers', 'customerLocation'));
    }
}
