@props(['title' => 'Temukan momen berikutnya', 'admin' => false])
<!DOCTYPE html>
<html lang="id" @if($admin) data-admin="true" @endif>
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="The Get To — temukan konser, pilih tiket, dan buat momen yang ingin kamu ulang.">
    <title>{{ $title }} · The Get To</title>
    <script src="{{ asset('theme.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a href="#main" class="skip-link">Langsung ke konten</a>
<header class="site-header">
    <nav class="shell nav-bar" aria-label="Navigasi utama">
        <a class="brand" href="{{ route('catalog') }}" aria-label="The Get To beranda"><span class="brand-mark">g<span>↗</span></span><span>the get to<span class="brand-dot">.</span></span></a>
        <div class="nav-links"><a class="{{ request()->routeIs('catalog','events.show') ? 'selected' : '' }}" href="{{ route('catalog') }}">Jelajahi event</a><a class="{{ request()->routeIs('orders.*') ? 'selected' : '' }}" href="{{ route('orders.index') }}">Tiket saya</a></div>
        <div class="nav-actions">
            @unless($admin)<button class="icon-button" type="button" data-theme-toggle aria-label="Ganti tema" title="Ganti tema"><span data-theme-icon>◐</span></button>@endunless
            @auth
                @if(auth()->user()->role === 'admin')<a class="button small secondary" href="{{ route('admin.dashboard') }}">Admin</a>@endif
                <form action="{{ route('logout') }}" method="post">@csrf<button class="nav-logout" type="submit">Keluar</button></form>
            @else
                <a class="nav-login" href="{{ route('login') }}">Masuk</a><a class="button small" href="{{ route('register') }}">Buat akun <span>↗</span></a>
            @endauth
        </div>
    </nav>
</header>
@if($admin)
<div class="admin-nav"><nav class="shell" aria-label="Menu admin"><span class="eyebrow">WORKSPACE</span><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">Ringkasan</a><a href="{{ route('admin.events.index') }}" class="{{ request()->routeIs('admin.events.*') ? 'selected' : '' }}">Event & tiket</a><a href="{{ route('admin.customers') }}" class="{{ request()->routeIs('admin.customers') ? 'selected' : '' }}">Pelanggan</a></nav></div>
@endif
<main id="main">
    @if(session('success'))<div class="shell"><div class="notice success" role="status">{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="shell"><div class="notice error" role="alert"><strong>Periksa kembali isianmu.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    {{ $slot }}
</main>
<footer class="footer shell"><a class="brand" href="{{ route('catalog') }}">the get to<span class="brand-dot">.</span></a><p>Good music. Real moments.</p><span>© {{ date('Y') }} The Get To · Portfolio demo</span></footer>
</body>
</html>
