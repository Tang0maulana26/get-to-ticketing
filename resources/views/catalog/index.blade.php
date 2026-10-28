<x-layout>
    @php
        $heroImage = file_exists(public_path('images/hero-concert.jpg'))
            ? asset('images/hero-concert.jpg')
            : asset('images/concert-stage.svg');
    @endphp

    <!-- Hero Section: Fullscreen Concert Stage -->
    <section class="hero-fullscreen" style="background-image: url('{{ $heroImage }}');">
        <div class="shell hero-fullscreen-inner">
            <div class="hero-copy">
                <div class="hero-badge-live">
                    <span class="live-dot"></span>
                    <span>YOUR NEXT GOOD MEMORY</span>
                </div>
                
                <h1 class="hero-title">
                    Jangan cuma dengerin.<br>
                    <span>Rasain langsung.</span>
                </h1>
                
                <p class="hero-desc">
                    Suara yang kamu suka. Orang-orang sefrekuensi.<br>
                    Temukan panggung konser impianmu dan buat momen yang tak terlupakan.
                </p>
                
                <div class="hero-actions">
                    <a class="button hero-cta-btn" href="#events">
                        Jelajahi Panggung Konser <span>↓</span>
                    </a>
                    <a class="hero-scroll-btn" href="#events">
                        Lihat Jadwal <span>↓</span>
                    </a>
                </div>

                <div class="hero-footnote">
                    <span class="mini-ticket">↗</span>
                    <span>Satu tiket resmi. Cerita yang tak habis-habis.</span>
                </div>
            </div>

            <!-- Spotlight Card -->
            <div class="hero-spotlight">
                @if($featured)
                    <a href="{{ route('events.show', $featured->slug) }}" class="hero-ticket" aria-label="Lihat konser unggulan {{ $featured->title }}">
                        <img src="{{ $featured->banner_url }}" alt="{{ $featured->title }}" width="800" height="600" fetchpriority="high">
                        <div class="hero-ticket-stub">
                            <div>
                                <span>ON OUR RADAR</span>
                                <strong>{{ $featured->title }}</strong>
                                <small>{{ $featured->start_time->locale('id')->translatedFormat('d M Y') }} · {{ $featured->city }}</small>
                            </div>
                            <span class="ticket-code">||||||||||||</span>
                        </div>
                    </a>
                @else
                    <div class="hero-ticket">
                        <img src="{{ asset('images/poster-blue.svg') }}" alt="Ilustrasi musik The Get To" width="800" height="600">
                        <div class="hero-ticket-stub">
                            <div>
                                <span>LIVE MUSIC RADAR</span>
                                <strong>Good moments start here.</strong>
                                <small>Temukan panggung terdekat</small>
                            </div>
                            <span class="ticket-code">||||||||||||</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Scroll down indicator -->
        <a href="#events" class="hero-bottom-indicator" aria-label="Gulir ke daftar event">
            <span>GULIR KE KONSER</span>
            <span class="bounce-arrow">↓</span>
        </a>
    </section>

    <!-- Benefits Strip -->
    <div class="benefits-strip">
        <div class="shell">
            <span>✦ &nbsp; Pilihan kurasi konser terbaik</span>
            <span>↗ &nbsp; Checkout cepat & aman</span>
            <span>◎ &nbsp; Semua tiket tersimpan dalam satu akun</span>
        </div>
    </div>

    <!-- Catalog Section with Smooth Scroll Anchor -->
    <section class="shell catalog-section" id="events">
        <div class="section-heading">
            <div>
                <p class="eyebrow">FIND YOUR FREQUENCY</p>
                <h2>Panggung berikutnya.</h2>
                <p class="muted">Pilih konser dan nikmati suasananya langsung di venue.</p>
            </div>
            <span class="count-label">{{ $events->total() }} konser tersedia</span>
        </div>

        <!-- Filter & Search Bar -->
        <form method="get" class="filter-bar" action="{{ route('catalog') }}#events">
            <label class="search-field">
                <span aria-hidden="true">⌕</span>
                <input type="search" 
                       name="q" 
                       value="{{ request('q') }}" 
                       placeholder="Cari konser, musisi, atau venue..." 
                       aria-label="Cari konser atau venue" 
                       maxlength="100">
            </label>

            <select name="city" aria-label="Pilih kota">
                <option value="">Semua kota</option>
                @foreach($cities as $city)
                    <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                @endforeach
            </select>

            @if($categories->count() > 1)
                <select name="category" aria-label="Kategori event">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <button class="button" type="submit">Cari konser</button>
            
            @if(request()->hasAny(['q', 'city', 'category']))
                <a class="text-link" href="{{ route('catalog') }}#events">Reset</a>
            @endif
        </form>

        <!-- Event Cards Grid -->
        <div class="event-grid">
            @forelse($events as $event)
                <x-event-card :event="$event"/>
            @empty
                <div class="empty-state">
                    <span>⌕</span>
                    <h3>Belum ketemu yang pas.</h3>
                    <p>Coba gunakan kata kunci atau kota lain. Konser baru akan segera hadir.</p>
                    <a href="{{ route('catalog') }}#events" class="button secondary">Lihat semua event</a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="pagination">
            {{ $events->links() }}
        </div>

        <!-- Bottom Banner -->
        <div class="bottom-banner">
            <div>
                <p class="eyebrow">LESS SCROLLING. MORE LIVING.</p>
                <h2>Momen terbaik ada di depan panggung.</h2>
            </div>
            <a href="#events" class="button secondary">Cari momenmu ↗</a>
        </div>
    </section>
</x-layout>
