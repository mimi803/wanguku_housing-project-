@extends('layouts.app')
@section('title', 'Manage Seed Bank')
@section('content')
<section class="workspace seed-bank-page">
    <div class="workspace-head"><div><span class="eyebrow">FARMER RESOURCE LIBRARY</span><h1>Manage your Seed Bank.</h1><p>Record seed identity, source, storage, quantity and any germination test. A test result is not a certification.</p></div><a class="button button-light bordered" href="{{ route('seed-bank.index') }}">View public Seed Bank</a></div>
    @if(session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error-list"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if(auth()->user()->role === 'farmer')
    <div class="panel seed-create-panel"><span class="eyebrow">ADD A SEED LOT</span><h2>Share a variety with the network.</h2>
        <form class="form-stack" method="post" action="{{ route('farm.seed-bank.store') }}">@csrf
            <div class="form-columns"><label>Crop name<input name="crop_name" maxlength="100" value="{{ old('crop_name') }}" placeholder="e.g. Maize" required></label><label>Variety<input name="variety" maxlength="120" value="{{ old('variety') }}" placeholder="e.g. local white maize" required></label></div>
            <div class="form-columns"><label>Lot code (optional)<input name="lot_code" maxlength="60" value="{{ old('lot_code') }}"></label><label>Seed source / provenance<input name="provenance" maxlength="180" value="{{ old('provenance') }}" placeholder="Where and how this seed was obtained" required></label></div>
            <div class="form-columns"><label>Quantity<input name="quantity_available" type="number" min="0" max="100000000" step="0.001" value="{{ old('quantity_available') }}" required></label><label>Unit<select name="unit" required>@foreach($units as $unit)<option value="{{ $unit }}" @selected(old('unit')===$unit)>{{ ucfirst($unit) }}</option>@endforeach</select></label></div>
            <div class="form-columns"><label>Harvest date<input name="harvest_date" type="date" max="{{ today()->toDateString() }}" value="{{ old('harvest_date') }}"></label><label>Storage notes<input name="storage_notes" maxlength="300" value="{{ old('storage_notes') }}" placeholder="Dry, cool, sealed container"></label></div>
            <div class="form-columns"><label>Germination test date<input name="germination_tested_at" type="date" max="{{ today()->toDateString() }}" value="{{ old('germination_tested_at') }}"></label><label>Germination result (%)<input name="germination_rate" type="number" min="0" max="100" step="0.01" value="{{ old('germination_rate') }}" placeholder="Only when tested"></label></div>
            <label>Farm location<input name="location" maxlength="150" value="{{ old('location', auth()->user()->location_name) }}" placeholder="Town or area"></label>
            <label>Notes (optional)<textarea name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea></label>
            <input type="hidden" name="status" value="available"><button class="button button-dark" type="submit">Add to my Seed Bank</button>
        </form>
    </div>
    @else
        <div class="panel"><p>Admin access is for review and support. Seed lots are added by verified farmers.</p></div>
    @endif

    <div class="seed-manage-section"><div class="panel-head"><div><span class="eyebrow">YOUR SEED LOTS</span><h2>Inventory</h2></div></div>
        @forelse($listings as $listing)
            <article class="seed-manage-card"><div class="seed-card-head"><div><span class="tag">{{ $listing->crop_name }}</span><h3>{{ $listing->variety }}</h3><p>{{ number_format((float)$listing->quantity_available, 3) }} {{ $listing->unit }} · {{ ucfirst(str_replace('_', ' ', $listing->status)) }}</p></div><details><summary>Edit lot</summary><form class="form-stack" method="post" action="{{ route('farm.seed-bank.update', $listing) }}">@csrf @method('PATCH')
                <div class="form-columns"><label>Crop<input name="crop_name" value="{{ $listing->crop_name }}" required maxlength="100"></label><label>Variety<input name="variety" value="{{ $listing->variety }}" required maxlength="120"></label></div>
                <div class="form-columns"><label>Lot code<input name="lot_code" value="{{ $listing->lot_code }}" maxlength="60"></label><label>Source / provenance<input name="provenance" value="{{ $listing->provenance }}" required maxlength="180"></label></div>
                <div class="form-columns"><label>Quantity<input name="quantity_available" type="number" value="{{ $listing->quantity_available }}" min="0" max="100000000" step="0.001" required></label><label>Unit<select name="unit">@foreach($units as $unit)<option value="{{ $unit }}" @selected($listing->unit===$unit)>{{ ucfirst($unit) }}</option>@endforeach</select></label></div>
                <div class="form-columns"><label>Harvest date<input name="harvest_date" type="date" max="{{ today()->toDateString() }}" value="{{ $listing->harvest_date?->toDateString() }}"></label><label>Storage notes<input name="storage_notes" maxlength="300" value="{{ $listing->storage_notes }}"></label></div>
                <div class="form-columns"><label>Germination test date<input name="germination_tested_at" type="date" max="{{ today()->toDateString() }}" value="{{ $listing->germination_tested_at?->toDateString() }}"></label><label>Germination result (%)<input name="germination_rate" type="number" min="0" max="100" step="0.01" value="{{ $listing->germination_rate }}"></label></div>
                <label>Location<input name="location" maxlength="150" value="{{ $listing->location }}"></label><label>Notes<textarea name="notes" rows="2" maxlength="2000">{{ $listing->notes }}</textarea></label>
                <label>Status<select name="status"><option value="available" @selected($listing->status==='available')>Available</option><option value="out_of_stock" @selected($listing->status==='out_of_stock')>Out of stock</option><option value="archived" @selected($listing->status==='archived')>Archived</option></select></label>
                <button class="button button-dark" type="submit">Save changes</button>
            </form></details></div></article>
        @empty<div class="empty-state compact">You have not added any seed lots yet.</div>@endforelse
        <div class="pagination-wrap">{{ $listings->links() }}</div>
    </div>

    <div class="seed-manage-section"><div class="panel-head"><div><span class="eyebrow">REQUESTS FOR YOUR SEEDS</span><h2>Incoming farmer requests</h2></div></div>
        @forelse($receivedRequests as $seedRequest)
            <article class="seed-request-card"><div><span class="tag">{{ $seedRequest->listing?->crop_name }} · {{ $seedRequest->listing?->variety }}</span><h3>{{ $seedRequest->requester?->name }}</h3><p>{{ number_format((float)$seedRequest->quantity, 3) }} {{ $seedRequest->unit }} · {{ ucfirst($seedRequest->status) }}</p>@if($seedRequest->message)<p>{{ $seedRequest->message }}</p>@endif</div>
                @if($seedRequest->status==='pending')<div class="seed-request-actions">@foreach(['accepted'=>'Accept', 'rejected'=>'Decline'] as $status=>$label)<form method="post" action="{{ route('farm.seed-bank.requests.respond', $seedRequest) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $status }}"><button class="button {{ $status==='accepted'?'button-dark':'button-light bordered' }}" type="submit">{{ $label }}</button></form>@endforeach</div>@elseif($seedRequest->status==='accepted')<form method="post" action="{{ route('farm.seed-bank.requests.respond', $seedRequest) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="fulfilled"><button class="button button-light bordered" type="submit">Mark collected</button></form>@endif
            </article>
        @empty<div class="empty-state compact">No farmers have requested your listed seed.</div>@endforelse
        <div class="pagination-wrap">{{ $receivedRequests->links() }}</div>
    </div>

    @if(auth()->user()->role==='farmer')<div class="seed-manage-section"><div class="panel-head"><div><span class="eyebrow">YOUR REQUESTS</span><h2>Seed you have requested</h2></div></div>
        @forelse($sentRequests as $seedRequest)<article class="seed-request-card"><div><span class="tag">{{ $seedRequest->listing?->crop_name }} · {{ $seedRequest->listing?->variety }}</span><p>{{ $seedRequest->listing?->farmer?->name }} · {{ number_format((float)$seedRequest->quantity, 3) }} {{ $seedRequest->unit }} · {{ ucfirst($seedRequest->status) }}</p></div></article>@empty<div class="empty-state compact">You have not requested seed lots yet.</div>@endforelse
        <div class="pagination-wrap">{{ $sentRequests->links() }}</div>
    </div>@endif
</section>
@endsection
