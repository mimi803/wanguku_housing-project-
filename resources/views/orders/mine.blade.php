@extends('layouts.app')
@section('title', 'My orders')
@section('content')
<section class="workspace"><div class="workspace-head"><div><span class="eyebrow">CUSTOMER ACCOUNT</span><h1>My order requests</h1><p>Follow order confirmations and coordinate collection with each farm.</p></div><a class="button button-dark" href="{{ route('shop') }}">Browse produce</a></div>
    @forelse($orders as $order)
    <article class="order-card"><div><span class="eyebrow">ORDER #{{ $order->id }} · {{ $order->created_at ? \Illuminate\Support\Carbon::parse($order->created_at)->format('M j, Y') : '' }}</span><h2>{{ $order->product?->title ?? 'Listing removed' }}</h2><p>{{ $order->quantity }} × KSh {{ number_format((float)($order->product?->price ?? 0), 2) }} · Farm: {{ $order->product?->farmer?->name ?? 'Unavailable' }}</p>@if($order->delivery_notes)<small>{{ $order->delivery_notes }}</small>@endif</div><div class="order-card-state"><span class="status-pill {{ $order->status === 'cancelled' ? 'attention' : ($order->status === 'completed' ? 'resolved' : '') }}">{{ ucfirst($order->status) }}</span><strong>KSh {{ number_format((float)$order->total_amount, 2) }}</strong><small>Payment: {{ ucfirst($order->payment_status ?? 'unpaid') }}</small></div>
        @php $phone=preg_replace('/\D+/','',$order->product?->farmer?->phone ?? ''); if(str_starts_with($phone,'0')) $phone='254'.substr($phone,1); @endphp
        @if($phone && !in_array($order->status,['cancelled','completed']))<a class="contact-button" target="_blank" rel="noopener" href="https://wa.me/{{ $phone }}?text={{ rawurlencode('Hello, I placed order #'.$order->id.' for '.$order->quantity.' × '.$order->product?->title.' on Farmers Connect. Can we confirm payment and collection?') }}">Message farm on WhatsApp</a>@endif
    </article>
    @empty<div class="empty-state">You haven’t requested an order yet. Browse the shop to find fresh produce.</div>@endforelse
    <div class="pagination-wrap">{{ $orders->links() }}</div>
</section>
@endsection
