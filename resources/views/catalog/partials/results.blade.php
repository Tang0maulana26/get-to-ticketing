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
