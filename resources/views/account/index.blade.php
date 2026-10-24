@extends('layouts.app')
@section('title', 'Customer account')
@section('content')
<section class="workspace customer-workspace">
    <div class="workspace-head"><div><span class="eyebrow">CUSTOMER ACCOUNT</span><h1>Welcome, {{ auth()->user()->name }}.</h1><p>Discover farm listings, check where they come from and contact the seller directly.</p></div><div class="hero-actions"><a class="button button-light bordered" href="{{ route('orders.mine') }}">My order requests</a><a class="button button-dark" href="{{ route('shop') }}">Browse produce</a></div></div>
    <div class="customer-steps"><article><span>01</span><strong>Choose a product</strong><small>Browse chicken, eggs, fish and vegetables.</small></article><article><span>02</span><strong>Check the farm</strong><small>Review the seller and their listed location.</small></article><article><span>03</span><strong>Message the seller</strong><small>Confirm availability, price and collection directly.</small></article></div>
    <div class="section-heading account-heading"><div><span class="eyebrow">NEARBY FARMS</span><h2>Explore the farm network.</h2><p class="section-lede">Your saved location is visible only to you. Use your current location for a private one-time map pin.</p></div><a class="text-link" href="{{ route('farm-map') }}">Open full map ↗</a></div>
    @include('partials.farm-map')
    <div class="section-heading account-heading"><div><span class="eyebrow">AVAILABLE NOW</span><h2>Listings from the farm network.</h2></div><a class="text-link" href="{{ route('home') }}#farm-shop">See all categories ↗</a></div>
    <div class="product-grid">@forelse($products as $product)
        @php
            $fallbacks = ['poultry_birds' => 'https://images.unsplash.com/photo-1774361373612-ad86786bfa80?auto=format&fit=crop&w=900&q=80', 'chicks' => 'https://images.unsplash.com/photo-1774361373612-ad86786bfa80?auto=format&fit=crop&w=900&q=80', 'fish' => 'https://images.unsplash.com/photo-1649347173558-a305d7b8ff98?auto=format&fit=crop&w=900&q=80', 'vegetables' => 'https://images.unsplash.com/photo-1657288089316-c0350003ca49?auto=format&fit=crop&w=900&q=80'];
            $image = $product->image_display_url ?: ($fallbacks[$product->category] ?? asset('uploads/7e285535bc3382a453bd96a9.jpg'));
            $phone = preg_replace('/\D+/', '', $product->farmer?->phone ?? '');
            if (str_starts_with($phone, '0')) $phone = '254'.substr($phone, 1);
        @endphp
        <article class="product-card"><img src="{{ $image }}" alt="{{ $product->title }}" loading="lazy"><div class="product-content"><span class="tag">{{ $categories[$product->category] ?? $product->category }}</span><h3>{{ $product->title }}</h3><p>{{ \Illuminate\Support\Str::limit($product->description, 100) }}</p><div class="product-meta"><strong>KSh {{ number_format((float)$product->price, 0) }}</strong><span>{{ $product->farmer?->location_name ?? 'Farm location not listed' }}</span></div><div class="seller-row"><span>{{ $product->farmer?->name ?? 'Local farm' }}</span>@if($phone)<a class="contact-button" href="https://wa.me/{{ $phone }}?text={{ rawurlencode('Hi, I saw your '.$product->title.' listing on Wanguku Housing. Is it available?') }}" target="_blank" rel="noopener">Message farm</a>@endif</div></div></article>
    @empty<div class="empty-state">No listings are available yet. Browse the category photos for the growing network vision, or check back when farmers publish their first products.</div>@endforelse</div>
    <p class="health-note">Wanguku Housing connects customers and sellers. Confirm product availability, collection arrangements and any food-safety details directly with the farm.</p>
</section>
@endsection
