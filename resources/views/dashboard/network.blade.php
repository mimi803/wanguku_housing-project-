@extends('layouts.app')
@section('title', 'Farmer network')
@section('content')
<section class="workspace"><div class="workspace-head"><div><span class="eyebrow">FARMER-TO-FARMER CONNECTION</span><h1>Share resources and know-how.</h1><p>Offer what your farm can share, or ask the network for support.</p></div><a class="button button-light bordered" href="{{ route('farm.dashboard') }}">← Dashboard</a></div>
    <div class="network-layout"><div class="panel"><span class="eyebrow">POST TO THE FARM NETWORK</span><h2>What would help your farm?</h2>
        @if($errors->any())<div class="error-list"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form class="form-stack" method="post" action="{{ route('farm.network.store') }}">@csrf
            <div class="form-columns"><label>Post type<select name="post_kind" required><option value="offer" @selected(old('post_kind')==='offer')>I can offer</option><option value="request" @selected(old('post_kind')==='request')>I’m looking for</option></select></label><label>Resource type<select name="resource_type" required>@foreach($resources as $key=>$label)<option value="{{ $key }}" @selected(old('resource_type')===$key)>{{ $label }}</option>@endforeach</select></label></div>
            <label>Title<input name="title" maxlength="150" value="{{ old('title') }}" placeholder="e.g. Sharing indigenous chicken health practices" required></label>
            <label>Details<textarea name="details" rows="4" maxlength="2000" placeholder="Describe what you can share or what support you need" required>{{ old('details') }}</textarea></label>
            <div class="form-columns"><label>Quantity (optional)<input name="quantity" type="number" min="0" step="0.01" value="{{ old('quantity') }}"></label><label>Unit<input name="unit" maxlength="32" value="{{ old('unit') }}" placeholder="kg, hours, sessions"></label></div>
            <label>Location<input name="location" maxlength="150" value="{{ old('location', auth()->user()->location_name) }}" placeholder="Town or area"></label>
            <button class="button button-dark" type="submit">Share with farmers</button>
        </form>
    </div>
    <div class="network-posts"><div class="panel-head"><div><span class="eyebrow">FARMER EXCHANGE BOARD</span><h2>Open connections</h2></div></div>
        @forelse($posts as $post)
        <article class="network-card"><div class="network-card-top"><span class="tag {{ $post->post_kind==='request' ? 'request-tag' : '' }}">{{ $post->post_kind==='offer' ? 'Offering' : 'Looking for' }} · {{ $resources[$post->resource_type] ?? $post->resource_type }}</span><span class="status-pill {{ $post->status==='fulfilled' ? 'resolved' : '' }}">{{ ucfirst($post->status) }}</span></div><h3>{{ $post->title }}</h3><p>{{ $post->details }}</p><div class="network-card-meta"><span>{{ $post->farmer?->name }} · {{ $post->location ?: $post->farmer?->location_name ?: 'Location not listed' }}</span>@if($post->quantity)<b>{{ rtrim(rtrim(number_format((float)$post->quantity,2),'0'),'.') }} {{ $post->unit }}</b>@endif</div>
            @php $phone=preg_replace('/\D+/','',$post->farmer?->phone ?? ''); if(str_starts_with($phone,'0')) $phone='254'.substr($phone,1); @endphp
            @if($post->farmer && $post->farmer->id !== auth()->id() && $phone)<a class="contact-button" target="_blank" rel="noopener" href="https://wa.me/{{ $phone }}?text={{ rawurlencode('Hello '.$post->farmer->name.', I saw your '.$post->post_kind.' post “'.$post->title.'” on the Farmers Connect network. Can we discuss?') }}">Connect on WhatsApp</a>@endif
            @if($post->farmer?->id === auth()->id() || auth()->user()->role==='admin')<form class="network-status-form" method="post" action="{{ route('farm.network.status',$post) }}">@csrf @method('PATCH')<label>Post status<select name="status"><option value="open" @selected($post->status==='open')>Open</option><option value="fulfilled" @selected($post->status==='fulfilled')>Fulfilled</option><option value="closed" @selected($post->status==='closed')>Closed</option></select></label><button class="button button-light bordered" type="submit">Update</button></form>@endif
        </article>
        @empty<div class="empty-state">The network board is quiet. Share the first resource, request or farming skill.</div>@endforelse
        <div class="pagination-wrap">{{ $posts->links() }}</div>
    </div></div>
</section>
@endsection
