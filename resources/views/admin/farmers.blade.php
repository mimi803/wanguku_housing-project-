@extends('layouts.app')
@section('title', 'Farmer verification')
@section('content')<section class="workspace"><div class="workspace-head"><div><span class="eyebrow">ADMIN ACCESS</span><h1>Farmer profile review</h1><p>Review each farm profile before listing it to customers or showing it on the public map. Phone verification confirms control of the number; review the farm details before approval.</p></div></div>
    @forelse($farmers as $farmer)<article class="panel farmer-review-card"><div><h2>{{ $farmer->name }}</h2><p>{{ $farmer->location_name ?: 'Farm location not provided' }} · {{ $farmer->phone }}</p><small>Phone verified {{ $farmer->phone_verified_at?->format('M j, Y') ?? 'not verified' }}</small></div>@if($farmer->phone_verified_at)<form method="post" action="{{ route('admin.farmers.approve', $farmer) }}">@csrf @method('PATCH')<button class="button button-dark" type="submit">Approve farm profile</button></form>@else<span class="tag">Waiting for phone verification</span>@endif</article>@empty<div class="panel empty-state">There are no farmer profiles waiting for review.</div>@endforelse
    <div class="pagination-wrap">{{ $farmers->links() }}</div>
</section>@endsection
