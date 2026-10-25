<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/farmers-connect.svg') }}?v={{ filemtime(public_path('images/farmers-connect.svg')) }}">
    <meta name="theme-color" content="#153f32">
    <title>@yield('title', 'Farmers Connect') · Farmers Connect</title>
    <script>(()=>{let theme='light';try{const saved=localStorage.getItem('farmers-connect-theme');theme=saved==='light'||saved==='dark'?saved:(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light')}catch{}document.documentElement.dataset.theme=theme})();</script>
    <link rel="stylesheet" href="{{ asset('css/wanguku.css') }}?v={{ filemtime(public_path('css/wanguku.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}">
    @stack('head')
</head>
<body>
<header class="site-header">
    <a class="brand" href="{{ route('home') }}" aria-label="Farmers Connect home"><span class="brand-monogram" aria-hidden="true">FC</span><span class="brand-name">Farmers Connect<small>LOCAL FOOD · SHARED KNOWLEDGE</small></span></a>
    <button class="mobile-nav-toggle" type="button" data-nav-toggle aria-controls="primary-navigation" aria-expanded="false"><span aria-hidden="true">☰</span><span data-nav-label>Menu</span></button>
    <nav id="primary-navigation" class="site-nav" data-site-nav>
        <div class="nav-links"><a href="{{ route('shop') }}">Shop</a><a href="{{ route('farm-map') }}">Farm map</a><a href="{{ route('home') }}#farmer-connect">Farmer network</a><a href="{{ route('home') }}#vision">Vision 2035</a>@if(config('services.resource_map.public_url'))<a href="{{ config('services.resource_map.public_url') }}" target="_blank" rel="noopener">Resource map</a>@endif</div>
        <div class="nav-actions">
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark theme" title="Switch theme"><span data-theme-glyph aria-hidden="true">☾</span><span data-theme-label>Dark</span></button>
            @auth
                @if(in_array(auth()->user()->role, ['farmer','admin']))<a class="nav-role-link" href="{{ auth()->user()->role === 'farmer' && !auth()->user()->farmer_verified_at ? route('farmer.pending') : route('farm.dashboard') }}">Farmer workspace</a>@if(auth()->user()->role === 'admin')<a href="{{ route('admin.farmers') }}">Verify farmers</a>@else<a href="{{ route('farm.orders') }}">Orders</a><a href="{{ route('farm.network') }}">Connect</a>@endif
                @else<a class="nav-role-link" href="{{ route('account') }}">Customer account</a><a href="{{ route('orders.mine') }}">My orders</a>@endif
                <a href="{{ route('profile.edit') }}">Profile</a><form class="inline" method="post" action="{{ route('logout') }}">@csrf<button class="nav-button" type="submit">Sign out</button></form>
            @else
                <a class="nav-role-link" href="{{ route('register.role', ['role' => 'farmer']) }}">For farmers</a><a class="nav-cta" href="{{ route('register.role', ['role' => 'buyer']) }}">Shop as a customer</a><a href="{{ route('login') }}">Sign in</a>
            @endauth
        </div>
    </nav>
</header>
<main>@if(session('status'))<div class="flash">{{ session('status') }}</div>@endif @if(session('integration_warning'))<div class="flash" role="status">{{ session('integration_warning') }}</div>@endif @yield('content')</main>
<footer class="site-footer"><div><a class="brand brand-light" href="{{ route('home') }}"><span class="brand-monogram" aria-hidden="true">FC</span><span class="brand-name">Farmers Connect<small>LOCAL FOOD · SHARED KNOWLEDGE</small></span></a><p>Growing food. Growing forward.</p></div><span>Connecting farmers and customers</span></footer>
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
        try { localStorage.setItem('farmers-connect-theme', root.dataset.theme); } catch {}
        syncButton();
    });
})();
(() => {
    const button = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-site-nav]');
    const label = button?.querySelector('[data-nav-label]');
    if (!button || !nav) return;
    button.addEventListener('click', () => {
        const open = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(open));
        nav.dataset.open = String(open);
        if (label) label.textContent = open ? 'Close' : 'Menu';
    });
    nav.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            button.setAttribute('aria-expanded', 'false');
            nav.dataset.open = 'false';
            if (label) label.textContent = 'Menu';
        }
    });
})();
</script>
</body></html>
