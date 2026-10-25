@extends('layouts.app')
@section('title', 'Community Seed Bank')
@section('content')
<section class="workspace seed-bank-page">
    <div class="workspace-head"><div><span class="eyebrow">RMC · COMMUNITY SEED BANK</span><h1>Seeds shared by local farmers.</h1><p>Explore locally adapted varieties, reported seed-lot details and farmer-to-farmer requests. Germination results are farmer-reported, not certification.</p></div><a class="button button-light bordered" href="{{ route('farm-map') }}">Find farms on the map</a></div>

    <form class="seed-search" method="get" action="{{ route('seed-bank.index') }}"><label>Search seeds<input type="search" name="q" value="{{ request('q') }}" placeholder="Crop, variety or source"></label><label>Crop<select name="crop"><option value="">All crops</option>@foreach($crops as $crop)<option value="{{ $crop }}" @selected(request('crop')===$crop)>{{ $crop }}</option>@endforeach</select></label><button class="button button-dark" type="submit">Find seeds</button></form>

    @if(session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    <div class="seed-listings">
        @forelse($listings as $listing)
            @php $phone=preg_replace('/\D+/','',$listing->farmer?->phone ?? ''); if(str_starts_with($phone,'0')) $phone='254'.substr($phone,1); @endphp
            <article class="seed-card">
                <div class="seed-card-head"><span class="tag">{{ $listing->crop_name }}</span><span class="status-pill">{{ number_format((float)$listing->quantity_available, 3).' '.$listing->unit }} available</span></div>
                <h2>{{ $listing->variety }}</h2>
                <p class="seed-source">{{ $listing->provenance }}</p>
                <div class="seed-facts"><span><small>Lot</small><b>{{ $listing->lot_code ?: 'Not recorded' }}</b></span><span><small>Harvested</small><b>{{ $listing->harvest_date?->format('M Y') ?? 'Not recorded' }}</b></span><span><small>Germination</small><b>@if($listing->germination_rate !== null){{ number_format((float)$listing->germination_rate, 1) }}% @else Not tested @endif</b></span></div>
                @if($listing->germination_tested_at)<p class="seed-note">Farmer-reported test: {{ $listing->germination_tested_at->format('M j, Y') }}</p>@endif
                @if($listing->storage_notes)<p class="seed-note">Storage: {{ $listing->storage_notes }}</p>@endif
                @if($listing->notes)<p class="seed-note">{{ $listing->notes }}</p>@endif
                <div class="seed-seller"><span>{{ $listing->farmer?->name }} · {{ $listing->location ?: $listing->farmer?->location_name ?: 'Farm location not listed' }}</span>@if($phone)<a class="contact-button" href="https://wa.me/{{ $phone }}?text={{ rawurlencode('Hello, I found your '.$listing->variety.' seed lot through the RMC Farmers Connect Seed Bank. Is it available?') }}" target="_blank" rel="noopener">Contact farmer</a>@endif</div>
                @auth
                    @if(auth()->user()->role === 'farmer' && auth()->user()->farmer_verified_at && auth()->id() !== $listing->farmer_id)
                        <form class="seed-request-form" method="post" action="{{ route('seed-bank.requests.store', $listing) }}">@csrf<label>Request quantity<input name="quantity" type="number" min="0.001" max="{{ $listing->quantity_available }}" step="0.001" required placeholder="{{ $listing->unit }}"></label><label>Message (optional)<input name="message" maxlength="1000" placeholder="How you plan to use or exchange the seed"></label><button class="button button-dark" type="submit">Request this seed</button></form>
                    @elseif(auth()->user()->role === 'farmer' && !auth()->user()->farmer_verified_at)
                        <p class="seed-note">Your farm must be verified before requesting seed.</p>
                    @endif
                @else
                    <a class="text-link" href="{{ route('register.role', ['role' => 'farmer']) }}">Join as a farmer to request seed →</a>
                @endauth
            </article>
        @empty
            <div class="empty-state"><span>🌱</span><h2>No seed lots are listed yet.</h2><p>Verified farmers can add locally adapted varieties to the shared Seed Bank.</p><a class="button button-dark" href="{{ route('register.role', ['role' => 'farmer']) }}">Join the farmer network</a></div>
        @endforelse
    </div>
    <div class="pagination-wrap">{{ $listings->links() }}</div>
    <p class="health-note">Seed source, storage and germination details are supplied by the listing farmer. Confirm quality and exchange arrangements directly before collecting seed.</p>
</section>
@endsection
