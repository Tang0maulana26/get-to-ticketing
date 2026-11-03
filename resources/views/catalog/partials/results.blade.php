{{-- Event grid and pagination; also returned as JSON HTML by the live catalog filter. --}}
<!-- Event Cards Grid -->
<div class="event-grid">
    @forelse($events as $event)
    <x-event-card :event="$event" />
    @empty
    <div class="empty-state">
        <span>⌕</span>
        <h3>{{ __('No events found.') }}</h3>
        <p>{{ __('Try searching for a different city or keyword. New events will appear here.') }}</p>
        <a href="{{ route('catalog') }}#events" class="button secondary">{{ __('View all events') }}</a>
    </div>
    @endforelse
</div>

<!-- Pagination -->
<div class="pagination">
    {{ $events->links() }}
</div>
