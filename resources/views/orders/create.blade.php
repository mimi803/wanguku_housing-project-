@extends('layouts.app')
@section('title', 'Request an order')
@section('content')
<section class="form-page"><div class="form-card wide">
    <span class="eyebrow">FARM MARKETPLACE · ORDER REQUEST</span><h1>Request this produce</h1>
    <p>{{ $product->title }} · KSh {{ number_format((float) $product->price, 2) }} each · {{ $product->stock }} available</p>
    @if($errors->any())<div class="error-list"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="post" action="{{ route('orders.store', $product) }}" class="form-stack">@csrf
        <label>Quantity<input type="number" name="quantity" min="1" max="{{ $product->stock }}" value="{{ old('quantity', 1) }}" required></label>
        <label>Phone number for coordination<input type="tel" name="phone" maxlength="20" value="{{ old('phone', auth()->user()->phone) }}" required></label>
        <label>Collection or delivery notes<textarea name="delivery_notes" rows="3" maxlength="1000" placeholder="Preferred collection point or delivery area">{{ old('delivery_notes') }}</textarea></label>
        <div class="order-summary"><span>Estimated total</span><strong>KSh <span id="order-total">{{ number_format((float) $product->price, 2) }}</span></strong><small>Payment and collection are agreed directly with the farmer. This request does not charge you.</small></div>
        <button class="button button-dark" type="submit">Send order request</button>
    </form>
</div></section>
<script>
const quantity = document.querySelector('[name="quantity"]');
const total = document.getElementById('order-total');
const unitPrice = {{ json_encode((float) $product->price) }};
quantity.addEventListener('input', () => { total.textContent = (unitPrice * Math.max(1, Number(quantity.value) || 1)).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); });
</script>
@endsection
