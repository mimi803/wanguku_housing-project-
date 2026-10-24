<?php

namespace App\Http\Controllers;

use App\Models\FarmDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FarmDeviceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $devices = FarmDevice::with('farmer')
            ->when($user->role !== 'admin', fn ($query) => $query->where('farmer_id', $user->id))
            ->latest()->get();
        $readings = \App\Models\FarmSensorReading::with('device.farmer')
            ->whereHas('device', fn ($query) => $query->when($user->role !== 'admin', fn ($scope) => $scope->where('farmer_id', $user->id)))
            ->latest('captured_at')->limit(30)->get();

        return view('dashboard.devices', compact('devices', 'readings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sensor_type' => ['required', Rule::in(['water_level', 'feed_level'])],
            'unit' => ['required', 'string', 'max:20'],
            'warning_below' => ['nullable', 'numeric', 'between:-100000000,100000000'],
        ]);
        $token = Str::random(64);
        $device = FarmDevice::create([
            ...$data,
            'farmer_id' => $request->user()->id,
            'api_token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        return redirect()->route('farm.devices')->with('new_device_credentials', [
            'id' => $device->id,
            'name' => $device->name,
            'token' => $token,
        ])->with('status', 'Monitoring device registered. Copy its access token now.');
    }

    public function revoke(Request $request, FarmDevice $device)
    {
        abort_unless($request->user()->role === 'admin' || $device->farmer_id === $request->user()->id, 403);
        $device->update(['is_active' => false]);
        return back()->with('status', 'Device access revoked. Readings from this token will be rejected.');
    }
}
