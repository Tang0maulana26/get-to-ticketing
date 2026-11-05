{{-- Customer order history ("My tickets"). --}}
<x-layout :title="__('My tickets')">
    @php
        $currentTab = $tab ?? 'all';
        $tabCounts = $counts ?? ['all' => $orders->total(), 'active' => 0, 'past' => 0];
    @endphp
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <h1>{{ __('My tickets') }}</h1>
                <p class="muted">{{ __('Hi, :name. All your exciting plans are right here', ['name' => auth()->user()->name]) }}</p>
            </div>
            <a class="button secondary" href="{{ route('catalog') }}">{{ __('Find events ↗') }}</a>
        </div>

        {{-- Filter Tabs: All / Active Tickets / Past Events --}}
        <div class="ticket-tabs" role="tablist" aria-label="{{ __('My tickets') }}">
            <a href="{{ route('orders.index', ['tab' => 'all']) }}"
               class="ticket-tab {{ $currentTab === 'all' ? 'active' : '' }}"
               role="tab"
               aria-selected="{{ $currentTab === 'all' ? 'true' : 'false' }}">
                <span>{{ __('All Orders') }}</span>
                <span class="ticket-tab-count">{{ $tabCounts['all'] }}</span>
            </a>
            <a href="{{ route('orders.index', ['tab' => 'active']) }}"
               class="ticket-tab {{ $currentTab === 'active' ? 'active' : '' }}"
               role="tab"
               aria-selected="{{ $currentTab === 'active' ? 'true' : 'false' }}">
                <span>{{ __('Active Tickets') }}</span>
                <span class="ticket-tab-count">{{ $tabCounts['active'] }}</span>
            </a>
            <a href="{{ route('orders.index', ['tab' => 'past']) }}"
               class="ticket-tab {{ $currentTab === 'past' ? 'active' : '' }}"
               role="tab"
               aria-selected="{{ $currentTab === 'past' ? 'true' : 'false' }}">
                <span>{{ __('Past Events') }}</span>
                <span class="ticket-tab-count">{{ $tabCounts['past'] }}</span>
            </a>
        </div>

        <div class="order-list">
            @forelse($orders as $order)
                @php
                    $firstItem = $order->items->first();
                    $eventDate = $firstItem?->event_start_time ?? $order->created_at;
                    $isPastEvent = $firstItem?->event_start_time && $firstItem->event_start_time->lt(now()->startOfDay());
                    $ticketCount = $order->items->sum('quantity');
                    $tierNames = $order->items->pluck('ticket_name')->filter()->unique()->implode(', ');
                @endphp
                <article class="order-card {{ $isPastEvent || in_array($order->status, ['cancelled', 'expired']) ? 'is-past' : '' }}">
                    {{-- Left Calendar Date Box --}}
                    <div class="order-date-box" aria-hidden="true">
                        <span class="order-date-month">{{ $eventDate->locale(app()->getLocale())->translatedFormat('M') }}</span>
                        <strong class="order-date-day">{{ $eventDate->format('d') }}</strong>
                        <span class="order-date-year">{{ $eventDate->format('Y') }}</span>
                    </div>

                    {{-- Center Event & Order Info --}}
                    <div class="order-card-body">
                        <div class="order-meta-top">
                            <!-- <span class="order-number-pill">#{{ $order->number }}</span> -->
                            <!-- <span class="order-meta-dot">·</span> -->
                            <small class="muted">{{ __('Ordered :date', ['date' => $order->created_at->format('d M Y · H:i')]) }}</small>
                            @if($isPastEvent && $order->status === 'paid')
                                <span class="order-past-pill">{{ __('Event Ended') }}</span>
                            @endif
                        </div>

                        <h3 class="order-card-title">
                            <a href="{{ route('orders.show', $order) }}">
                                {{ $firstItem?->event_title ?? __('Order') }}
                            </a>
                        </h3>

                        @if($firstItem?->event_start_time || $firstItem?->venue)
                            <div class="order-event-meta">
                                @if($firstItem?->event_start_time)
                                    <span>{{ $firstItem->event_start_time->locale(app()->getLocale())->translatedFormat('d M Y · H:i') }} WIB</span>
                                @endif
                                @if($firstItem?->event_start_time && $firstItem?->venue)
                                    <span class="order-meta-dot">·</span>
                                @endif
                                @if($firstItem?->venue)
                                    <span>{{ $firstItem->venue }}</span>
                                @endif
                            </div>
                        @endif

                        <p class="order-summary-line">
                            @if($tierNames)
                                <span class="order-tier-pill">{{ $tierNames }}</span>
                            @endif
                            <span>{{ __(':count tickets · Rp:total', ['count' => $ticketCount, 'total' => number_format($order->total, 0, ',', '.')]) }}</span>
                            @if($order->discount_code)
                                <span class="tag-promo">{{ __('Promo: :code', ['code' => $order->discount_code]) }}</span>
                            @endif
                        </p>
                    </div>

                    {{-- Right Status & Detail Button --}}
                    <div class="order-card-actions">
                        <x-status-badge :status="$order->status" />
                        <a href="{{ route('orders.show', $order) }}"
                           class="button small {{ $order->status === 'paid' ? '' : 'secondary' }} order-detail-btn"
                           title="{{ __('View Details') }}">
                            <span>{{ $order->status === 'paid' ? __('View Tickets') : __('Order Details') }}</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <span>↗</span>
                    @if($currentTab === 'active')
                        <h2>{{ __('No active tickets right now.') }}</h2>
                        <p>{{ __('Book your next concert ticket and it will show up here.') }}</p>
                    @elseif($currentTab === 'past')
                        <h2>{{ __('No past ticket history yet.') }}</h2>
                        <p>{{ __('Completed events and expired orders will be archived here.') }}</p>
                    @else
                        <h2>{{ __('No tickets yet. Never too late.') }}</h2>
                        <p>{{ __('The next concert could be your favorite story.') }}</p>
                    @endif
                    <a href="{{ route('catalog') }}" class="button">{{ __('Explore events') }}</a>
                </div>
            @endforelse
        </div>
        <div class="pagination">{{ $orders->links() }}</div>
    </section>
</x-layout>
