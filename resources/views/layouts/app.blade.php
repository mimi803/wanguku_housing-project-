<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/wanguku-favicon.png') }}?v={{ filemtime(public_path('images/wanguku-favicon.png')) }}">
    <title>@yield('title', 'Wanguku Housing') · Wanguku Housing</title>
    <script>(()=>{let theme='light';try{const saved=localStorage.getItem('wanguku-theme');theme=saved==='light'||saved==='dark'?saved:(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light')}catch{}document.documentElement.dataset.theme=theme})();</script>
    <link rel="stylesheet" href="{{ asset('css/wanguku.css') }}?v={{ filemtime(public_path('css/wanguku.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}">
    @stack('head')
</head>
<body>
<header class="site-header"><a class="brand" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/wanguku-logo-original.jpeg') }}?v={{ filemtime(public_path('images/wanguku-logo-original.jpeg')) }}" alt="Wanguku Housing Organic Chicken Farm · Chicken Shop and Hatchery"></a>
    <nav><a href="{{ route('shop') }}">Shop</a><a href="{{ route('farm-map') }}">Farm map</a>@if(config('services.resource_map.public_url'))<a href="{{ config('services.resource_map.public_url') }}" target="_blank" rel="noopener">Resource map</a>@endif<a href="{{ route('home') }}#farmer-connect">Farm network</a><a href="{{ route('home') }}#vision">Vision 2035</a>
    <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark theme" title="Switch theme"><span data-theme-glyph aria-hidden="true">☾</span><span data-theme-label>Dark</span></button>
    @auth <a href="{{ route('shop') }}">Marketplace</a>@if(in_array(auth()->user()->role, ['farmer','admin']))<a href="{{ route('farm.dashboard') }}">Farm workspace</a><a href="{{ route('farm.orders') }}">Orders</a><a href="{{ route('farm.network') }}">Farmer network</a>@else<a href="{{ route('account') }}">My account</a><a href="{{ route('orders.mine') }}">My orders</a>@endif<a href="{{ route('profile.edit') }}">Profile</a>
        <form class="inline" method="post" action="{{ route('logout') }}">@csrf<button class="nav-button">Sign out</button></form>
    @else <a href="{{ route('login') }}">Sign in</a><a class="nav-cta" href="{{ route('register.role', ['role' => 'buyer']) }}">Customer account</a>@endauth
    </nav>
</header>
<main>@if(session('status'))<div class="flash">{{ session('status') }}</div>@endif @if(session('integration_warning'))<div class="flash" role="status">{{ session('integration_warning') }}</div>@endif @yield('content')</main>
<footer class="site-footer"><div><a class="brand brand-light" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/wanguku-logo-original.jpeg') }}?v={{ filemtime(public_path('images/wanguku-logo-original.jpeg')) }}" alt="Wanguku Housing Organic Chicken Farm · Chicken Shop and Hatchery"></a><p>Growing food. Growing forward.</p></div><span>K Road, Juja · Growing since 2021</span></footer>
@stack('scripts')
<script>
(() => {
    const root = document.documentElement;
    const button = document.querySelector('[data-theme-toggle]');
    if (!button) return;
    const syncButton = () => {
        const dark = root.dataset.theme === 'dark';
        button.setAttribute('aria-label', dark ? 'Switch to light theme' : 'Switch to dark theme');
        button.setAttribute('aria-pressed', String(dark));
        button.querySelector('[data-theme-glyph]').textContent = dark ? '☀' : '☾';
        button.querySelector('[data-theme-label]').textContent = dark ? 'Light' : 'Dark';
    };
    syncButton();
    button.addEventListener('click', () => {
        root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem('wanguku-theme', root.dataset.theme); } catch {}
        syncButton();
    });
})();
</script>
</body></html>
