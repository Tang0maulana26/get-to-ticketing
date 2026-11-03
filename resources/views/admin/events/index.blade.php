{{-- Admin event list with search, edit and delete actions. --}}
<x-layout :title="__('Manage events')" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">EVENT MANAGEMENT</p>
                <h1>{{ __('Events & tickets.') }}</h1>
                <p class="muted">{{ __('From stage idea to sold-out crowd.') }}</p>
            </div>
            <a class="button" href="{{ route('admin.events.create') }}">{{ __('+ Create event') }}</a>
        </div>
        <form class="filter-bar" method="get">
            <label class="search-field">
                <span>⌕</span>
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search event title...') }}" aria-label="Cari event" maxlength="100">
            </label>
            <button class="button secondary">{{ __('Search') }}</button>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Ticket options') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>
                                <strong>{{ $event->title }}</strong>
                                <small>{{ $event->venue }}, {{ $event->city }}</small>
                            </td>
                            <td>{{ $event->start_time->format('d M Y H:i') }}</td>
                            <td>{{ $event->ticket_categories_count }}</td>
                            <td><x-status-badge :status="$event->status"/></td>
                            <td>
                                <div class="table-actions">
                                    <a class="text-link" href="{{ route('admin.events.edit', $event) }}">{{ __('Edit') }}</a>
                                    @if($event->status === 'published')
                                        <a class="text-link" href="{{ route('events.show', $event->slug) }}">{{ __('View') }}</a>
                                    @endif
                                    <form action="{{ route('admin.events.destroy', $event) }}" method="post" data-confirm="{{ __('Delete event from catalog? Purchase history remains preserved.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">{{ __('No events yet. Create your first event.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $events->links() }}</div>
    </section>
</x-layout>
