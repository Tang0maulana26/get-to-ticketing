@props(['title' => 'Temukan momen berikutnya', 'admin' => false])
<!DOCTYPE html>
<html lang="id" @if($admin) data-admin="true" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="The Get To — temukan konser, pilih tiket, dan buat momen yang ingin kamu ulang.">
    <title>{{ $title }} · The Get To</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="{{ asset('theme.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#main" class="skip-link">Langsung ke konten</a>

    <header class="site-header @if(request()->routeIs('catalog')) header-home @endif">
        <nav class="shell nav-bar" aria-label="Navigasi utama">
            <a class="brand" href="{{ route('catalog') }}" aria-label="The Get To beranda">
                <span class="brand-mark">g<span>↗</span></span>
                <span>the get to<span class="brand-dot">.</span></span>
            </a>

            <div class="nav-links">
                <a class="{{ request()->routeIs('catalog', 'events.show') ? 'selected' : '' }}" href="{{ route('catalog') }}">
                    Jelajahi konser
                </a>
                <a class="{{ request()->routeIs('orders.*') ? 'selected' : '' }}" href="{{ route('orders.index') }}">
                    Tiket saya
                </a>
            </div>

            <div class="nav-actions">
                @unless($admin)
                    <button class="icon-button" type="button" data-theme-toggle aria-label="Ganti mode tema" title="Ganti mode tema">
                        <svg class="theme-icon-light" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                        <svg class="theme-icon-dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>
                @endunless

                @auth
                    @if(auth()->user()->isAdmin())
                        <a class="button small secondary" href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button class="nav-logout" type="submit">Keluar</button>
                    </form>
                @else
                    <a class="nav-login" href="{{ route('login') }}">Masuk</a>
                    <a class="button small" href="{{ route('register') }}">Buat akun ↗</a>
                @endauth
            </div>
        </nav>
    </header>

    @if($admin)
        <div class="admin-nav">
            <nav class="shell" aria-label="Menu admin">
                <span class="eyebrow">WORKSPACE</span>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
                    Ringkasan
                </a>
                <a href="{{ route('admin.events.index') }}" class="{{ request()->routeIs('admin.events.*') ? 'selected' : '' }}">
                    Event & tiket
                </a>
                <a href="{{ route('admin.customers') }}" class="{{ request()->routeIs('admin.customers') ? 'selected' : '' }}">
                    Pelanggan
                </a>
            </nav>
        </div>
    @endif

    <main id="main">
        @if(session('success'))
            <div class="shell">
                <div class="notice success" role="status">{{ session('success') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="shell">
                <div class="notice error" role="alert">
                    <strong>Periksa kembali isianmu.</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="footer shell">
        <a class="brand" href="{{ route('catalog') }}">
            the get to<span class="brand-dot">.</span>
        </a>
        <p>Good music. Real moments.</p>
        <span>© {{ date('Y') }} The Get To · Portfolio demo</span>
    </footer>
</body>
</html>
