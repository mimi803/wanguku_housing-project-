<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FarmerVerificationController extends Controller
{
    public function pending(Request $request)
    {
        abort_unless($request->user()?->role === 'farmer', 403);
        return view('farmers.pending-verification');
    }

    public function index()
    {
        $farmers = User::query()->where('role', 'farmer')->whereNull('farmer_verified_at')->orderBy('id')->paginate(30);
        return view('admin.farmers', compact('farmers'));
    }

    public function approve(User $user)
    {
        abort_unless($user->role === 'farmer', 404);
        abort_unless($user->phone_verified_at, 422, 'The farmer must verify their phone before approval.');
        $user->forceFill(['farmer_verified_at' => now()])->save();
        $synced = ! $user->show_on_map || app(\App\Services\ResourceMapClient::class)->sync($user);
        $response = back()->with('status', 'Farmer profile approved and made visible on Farmers Connect.');
        if ($user->show_on_map && ! $synced) $response->with('integration_warning', 'The farmer is approved, but the Resource Center map did not sync. Check the map connection settings.');
        return $response;
    }
}
