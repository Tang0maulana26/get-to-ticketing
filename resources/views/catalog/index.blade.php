{{-- Landing page: hero, featured event, filters and the event grid. --}}
<x-layout>
    <!-- Hero Section: Clean Vintage Modern -->
    <section class="vintage-hero" style="--hero-bg: url('{{ asset('images/bg-poster-kelvin-moquete-unsplash.svg') }}');">
        <div class="shell vintage-hero-inner">
            <div class="vintage-hero-copy">
                <h1 class="vintage-title">
                    Get to Ticketing
                </h1>

                <p class="vintage-lead">
                    Discover and book tickets for upcoming events, concerts, and live experiences.
                </p>

                <div class="vintage-actions">
                    <a class="vintage-btn"
                        href="#events"
                        onclick="event.preventDefault(); document.getElementById('events').scrollIntoView({ behavior: 'smooth' });">
                        Explore Events <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Curated Benefits Strip -->
    <div class="benefits-strip">
        <div class="shell" style="justify-content: center;">
            <span>✦ &nbsp; Curated event selection</span>
            <!-- <span>↗ &nbsp; Direct and verified tickets</span> -->
            <!-- <span>◎ &nbsp; All passes secured in your account</span> -->
        </div>
    </div>

    <!-- Catalog Section with Smooth Scroll Anchor -->
    <section class="shell catalog-section" id="events">
        <div class="section-heading">
            <div>
                <h2>Available events</h2>
                <p class="muted">Discover the events of your choice and secure your spot</p>
            </div>
            <span class="count-label" data-catalog-count>{{ $events->total() }} events available</span>
        </div>

        <!-- Filter & Search Bar -->
        <form method="get" class="filter-bar" data-live-filter action="{{ route('catalog') }}#events">
            <label class="search-field">
                <span aria-hidden="true">⌕</span>
                <input type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search events, artists, venues..."
                    aria-label="Search events or venue"
                    maxlength="100">
            </label>

            <select name="city" aria-label="Select city">
                <option value="">All cities</option>
                @foreach($cities as $city)
                <option value="{{ $city }}" @selected(request('city')===$city)>{{ $city }}</option>
                @endforeach
            </select>

            @if($categories->count() > 1)
            <select name="category" aria-label="Event category">
                <option value="">All categories</option>
                @foreach($categories as $category)
                <option value="{{ $category->slug }}" @selected(request('category')===$category->slug)>
                    {{ $category->name }}
                </option>
                @endforeach
            </select>
            @endif

            <button class="button" type="submit">Filter events</button>

            @if(request()->hasAny(['q', 'city', 'category']))
            <a class="text-link" href="{{ route('catalog') }}#events">Reset</a>
            @endif
        </form>

        <div data-catalog-results>
            @include('catalog.partials.results')
        </div>

        <!-- Bottom Banner -->
        <div class="bottom-banner">
            <div>
                <p class="eyebrow">LESS SCROLLING. MORE LIVING.</p>
                <h2>The best memories are made at the event</h2>
            </div>
            <a href="#events" class="button secondary">Find your events ↗</a>
        </div>
    </section>
</x-layout>