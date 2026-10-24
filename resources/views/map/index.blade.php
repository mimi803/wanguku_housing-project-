@extends('layouts.app')
@section('title', 'Farm and customer map')
@section('content')
<section class="workspace map-page">
    <div class="workspace-head"><div><span class="eyebrow">WANGUKU FARM CONNECT</span><h1>Find farms around you.</h1><p>See partner farms that chose to share their map location. Customers can view their own saved location or locate themselves privately.</p></div><a class="button button-light bordered" href="{{ route('shop') }}">Browse produce</a></div>
    @include('partials.farm-map')
    <p class="health-note">Farm pins are shown only with each farmer’s consent. Your customer location is never published on the public map.</p>
</section>
@endsection
