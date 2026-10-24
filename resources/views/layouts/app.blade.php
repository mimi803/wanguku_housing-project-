<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/wanguku-favicon.png') }}?v={{ filemtime(public_path('images/wanguku-favicon.png')) }}">
    <title>@yield('title', 'Wanguku Housing') · Wanguku Housing</title>
    <link rel="stylesheet" href="{{ asset('css/wanguku.css') }}?v={{ filemtime(public_path('css/wanguku.css')) }}">
    @stack('head')
</head>
<body>
<header class="site-header"><a class="brand" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/wanguku-logo-original.jpeg') }}?v={{ filemtime(public_path('images/wanguku-logo-original.jpeg')) }}" alt="Wanguku Housing Organic Chicken Farm · Chicken Shop and Hatchery"></a>
    <nav><a href="{{ route('shop') }}">Shop</a><a href="{{ route('farm-map') }}">Farm map</a><a href="{{ route('home') }}#farm-network">Farm network</a><a href="{{ route('home') }}#vision">Vision 2035</a>
    @auth <a href="{{ route('shop') }}">Marketplace</a>@if(in_array(auth()->user()->role, ['farmer','admin']))<a href="{{ route('farm.dashboard') }}">Farm workspace</a><a href="{{ route('farm.orders') }}">Orders</a><a href="{{ route('farm.network') }}">Farmer network</a>@else<a href="{{ route('account') }}">My account</a><a href="{{ route('orders.mine') }}">My orders</a>@endif<a href="{{ route('profile.edit') }}">Profile</a>
        <form class="inline" method="post" action="{{ route('logout') }}">@csrf<button class="nav-button">Sign out</button></form>
    @else <a href="{{ route('login') }}">Sign in</a><a class="nav-cta" href="{{ route('register.role', ['role' => 'buyer']) }}">Customer account</a>@endauth
    </nav>
</header>
<main>@if(session('status'))<div class="flash">{{ session('status') }}</div>@endif @yield('content')</main>
<footer class="site-footer"><div><a class="brand brand-light" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/wanguku-logo-original.jpeg') }}?v={{ filemtime(public_path('images/wanguku-logo-original.jpeg')) }}" alt="Wanguku Housing Organic Chicken Farm · Chicken Shop and Hatchery"></a><p>Growing food. Growing forward.</p></div><span>K Road, Juja · Growing since 2021</span></footer>
@stack('scripts')
</body></html>
