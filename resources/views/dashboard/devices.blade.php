@extends('layouts.app')
@section('title', 'Farm monitoring devices')
@section('content')
<section class="workspace"><div class="workspace-head"><div><span class="eyebrow">SENSOR MONITORING</span><h1>Water and feed monitoring</h1><p>Register a sensor and send readings to your farm workspace.</p></div><a class="button button-light bordered" href="{{ route('farm.dashboard') }}">← Dashboard</a></div>
    @if(session('new_device_credentials'))<div class="device-token-panel"><strong>Copy this device token now</strong><p>It is shown once and stored as a one-way hash. Add it to the sensor’s secure configuration.</p><code>{{ session('new_device_credentials.token') }}</code><small>Device ID: {{ session('new_device_credentials.id') }} · {{ session('new_device_credentials.name') }}</small></div>@endif
    <div class="operations-grid"><div class="panel"><span class="eyebrow">REGISTER A SENSOR</span><h2>Add a monitoring device</h2>@if($errors->any())<div class="error-list"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="post" action="{{ route('farm.devices.store') }}" class="form-stack">@csrf
            <label>Device name<input name="name" maxlength="100" value="{{ old('name') }}" placeholder="Main tank level sensor" required></label>
            <label>Measurement type<select name="sensor_type"><option value="water_level">Water level</option><option value="feed_level">Feed level</option></select></label>
            <div class="form-columns"><label>Unit<input name="unit" maxlength="20" value="{{ old('unit') }}" placeholder="litres, kg or %" required></label><label>Warning below<input type="number" name="warning_below" step="0.001" value="{{ old('warning_below') }}" placeholder="Optional threshold"></label></div>
            <button class="button button-dark" type="submit">Register device</button>
        </form>
        <div class="device-api-help"><strong>Connect a sensor</strong><p>Send a JSON reading to the device URL with the access token in an <code>Authorization: Bearer …</code> header.</p><pre>POST {{ url('/api/farm/devices/{device-id}/readings') }}
Content-Type: application/json

{"value": 42.5, "captured_at": "{{ now()->toIso8601String() }}"}</pre><small>Use HTTPS when the site is deployed publicly. This endpoint records readings and returns a low-threshold alert; it does not switch pumps, valves or feeders.</small></div>
    </div>
    <div class="panel"><div class="panel-head"><div><span class="eyebrow">DEVICE STATUS</span><h2>Registered devices</h2></div></div>
        @forelse($devices as $device)@php $reading=$device->readings()->latest('captured_at')->first(); @endphp
            <article class="device-row"><div class="device-row-head"><div><span class="tag">{{ $device->sensor_type==='water_level' ? 'Water' : 'Feed' }}</span><h3>{{ $device->name }}</h3></div><span class="status-pill {{ $device->is_active ? 'resolved' : 'attention' }}">{{ $device->is_active ? 'Active' : 'Revoked' }}</span></div><div class="device-reading">@if($reading)<strong class="{{ $reading->alert_triggered ? 'danger' : '' }}">{{ rtrim(rtrim(number_format((float)$reading->reading_value,3),'0'),'.') }} {{ $device->unit }}</strong><small>{{ $reading->captured_at->format('M j · g:i A') }}@if($reading->alert_triggered) · Below warning level@endif</small>@else<small>No readings received yet.</small>@endif</div><small>Warning below: {{ $device->warning_below ?? 'Not set' }} {{ $device->warning_below ? $device->unit : '' }} · Last seen: {{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</small>
                @if($device->is_active && ($device->farmer_id===auth()->id() || auth()->user()->role==='admin'))<form method="post" action="{{ route('farm.devices.revoke',$device) }}" onsubmit="return confirm('Revoke this device token? Its future readings will be rejected.')">@csrf @method('PATCH')<button class="text-link danger-link" type="submit">Revoke token</button></form>@endif
            </article>
        @empty<div class="empty-state">No sensors registered. Register a water or feed monitor when compatible hardware is ready.</div>@endforelse
        <div class="panel-head readings-heading"><div><span class="eyebrow">LATEST SENSOR DATA</span><h2>Recent readings</h2></div></div>
        @forelse($readings as $reading)<div class="activity-row"><span class="activity-icon">{{ $reading->alert_triggered ? '⚠️' : '📡' }}</span><div><strong>{{ $reading->device?->name }} · {{ rtrim(rtrim(number_format((float)$reading->reading_value,3),'0'),'.') }} {{ $reading->device?->unit }}</strong><small>{{ $reading->captured_at->format('D, M j · g:i A') }} · {{ $reading->device?->farmer?->name }}</small></div>@if($reading->alert_triggered)<span class="status-pill attention">Low</span>@endif</div>@empty<div class="empty-state compact">Sensor readings will appear here after a device connects.</div>@endforelse
    </div></div>
</section>
@endsection
