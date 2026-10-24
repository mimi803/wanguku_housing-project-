<?php

use App\Http\Controllers\FarmTelemetryController;
use App\Http\Middleware\AuthenticateFarmDevice;
use Illuminate\Support\Facades\Route;

Route::post('/farm/devices/{device}/readings', FarmTelemetryController::class)
    ->middleware([AuthenticateFarmDevice::class, 'throttle:30,1']);
