<?php

namespace App\Http\Controllers;

use App\Models\FarmDevice;
use App\Models\FarmSensorReading;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FarmTelemetryController extends Controller
{
    public function __invoke(Request $request, FarmDevice $device)
    {
        abort_unless($request->attributes->get('farm_device')?->is($device), 401);
        $data = $request->validate([
            'value' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'captured_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);
        $capturedAt = isset($data['captured_at']) ? Carbon::parse($data['captured_at']) : now();
        $alert = $device->warning_below !== null && (float) $data['value'] < (float) $device->warning_below;
        $reading = FarmSensorReading::create([
            'device_id' => $device->id,
            'reading_value' => $data['value'],
            'captured_at' => $capturedAt,
            'alert_triggered' => $alert,
            'created_at' => now(),
        ]);
        $device->forceFill(['last_seen_at' => now()])->save();

        return response()->json([
            'accepted' => true,
            'reading_id' => $reading->id,
            'alert' => $alert,
            'message' => $alert ? 'Reading is below the configured warning threshold.' : 'Reading recorded.',
        ], 201);
    }
}
