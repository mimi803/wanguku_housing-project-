<?php

namespace App\Http\Middleware;

use App\Models\FarmDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFarmDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $device = $request->route('device');
        $token = $request->bearerToken();
        abort_unless($device instanceof FarmDevice && $device->is_active && $token && hash_equals($device->api_token_hash, hash('sha256', $token)), 401, 'Invalid or inactive farm device token.');
        $request->attributes->set('farm_device', $device);

        return $next($request);
    }
}
