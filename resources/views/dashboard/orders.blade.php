@extends('layouts.app')
@section('title', 'Farm orders')
@section('content')
<section class="workspace"><div class="workspace-head"><div><span class="eyebrow">FARMER WORKSPACE</span><h1>Customer order requests</h1><p>Confirm availability, coordinate collection and keep the order status up to date.</p></div><a class="button button-light bordered" href="{{ route('farm.dashboard') }}">← Dashboard</a></div>
    @forelse($orders as $order)
    <article class="order-card"><div><span class="eyebrow">ORDER #{{ $order->id }} · {{ $order->created_at ? \Illuminate\Support\Carbon::parse($order->created_at)->format('M j, Y') : '' }}</span><h2>{{ $order->product?->title }}</h2><p>{{ $order->quantity }} requested · {{ $order->buyer?->name }} · {{ $order->phone }}</p>@if($order->delivery_notes)<small>Collection / delivery: {{ $order->delivery_notes }}</small>@endif</div><div class="order-card-state"><span class="status-pill {{ $order->status === 'cancelled' ? 'attention' : ($order->status === 'completed' ? 'resolved' : '') }}">{{ ucfirst($order->status) }}</span><strong>KSh {{ number_format((float)$order->total_amount, 2) }}</strong><small>Payment: {{ ucfirst($order->payment_status ?? 'unpaid') }}</small></div>
        @php $phone=preg_replace('/\D+/','',$order->buyer?->phone ?? $order->phone); if(str_starts_with($phone,'0')) $phone='254'.substr($phone,1); @endphp
        @if($phone)<a class="contact-button" target="_blank" rel="noopener" href="https://wa.me/{{ $phone }}?text={{ rawurlencode('Hello '.$order->buyer?->name.', I’m following up on your Farmers Connect order #'.$order->id.' for '.$order->product?->title.'.') }}">Message customer</a>@endif
        @if(in_array($order->status,['pending','confirmed','ready']))
        <form class="order-update-form" method="post" action="{{ route('farm.orders.status', $order) }}">@csrf @method('PATCH')
            <label>Update status<select name="status" required>@if($order->status==='pending')<option value="confirmed">Confirm order</option><option value="cancelled">Cancel order</option>@elseif($order->status==='confirmed')<option value="ready">Ready for collection</option><option value="cancelled">Cancel order</option>@else<option value="completed">Complete order</option><option value="cancelled">Cancel order</option>@endif</select></label>
            <label>Payment<select name="payment_status"><option value="unpaid" @selected(($order->payment_status ?? 'unpaid')==='unpaid')>Unpaid / not confirmed</option><option value="paid" @selected(($order->payment_status ?? '')==='paid')>Paid</option></select></label>
            <button class="button button-dark" type="submit">Save update</button>
        </form>@endif
    </article>
    @empty<div class="empty-state">New customer requests will appear here.</div>@endforelse
    <div class="pagination-wrap">{{ $orders->links() }}</div>
</section>
@endsection
