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
        <div class="shell">
            <span>✦ &nbsp; Curated event selection</span>
            <span>↗ &nbsp; Direct and verified tickets</span>
            <span>◎ &nbsp; All passes secured in your account</span>
        </div>
    </div>

    <!-- Catalog Section with Smooth Scroll Anchor -->
    <section class="shell catalog-section" id="events">
        <div class="section-heading">
            <div>
                <p class="eyebrow">UPCOMING DATES</p>
                <h2>Next on stage.</h2>
                <p class="muted">Find your sound and secure your spot in the crowd.</p>
            </div>
            <span class="count-label">{{ $events->total() }} events available</span>
        </div>

        <!-- Filter & Search Bar -->
        <form method="get" class="filter-bar" action="{{ route('catalog') }}#events">
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

        <!-- Event Cards Grid -->
        <div class="event-grid">
            @forelse($events as $event)
            <x-event-card :event="$event" />
            @empty
            <div class="empty-state">
                <span>⌕</span>
                <h3>No events found.</h3>
                <p>Try searching for a different city or keyword. New events will appear here.</p>
                <a href="{{ route('catalog') }}#events" class="button secondary">View all events</a>
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
                <h2>The best memories are in front of the stage.</h2>
            </div>
            <a href="#events" class="button secondary">Find your event ↗</a>
        </div>
    </section>
</x-layout>