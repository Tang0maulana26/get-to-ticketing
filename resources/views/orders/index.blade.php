{{-- Customer order history ("My tickets"). --}}
<x-layout :title="__('My tickets')">
    <section class="shell page-space">
        <p class="eyebrow">{{ __('YOUR NEXT EXPERIENCE') }}</p>
        <div class="section-heading">
            <div>
                <h1>{{ __('My tickets') }}.</h1>
                <p class="muted">{{ __('Hi, :name. All your exciting plans are right here.', ['name' => auth()->user()->name]) }}</p>
            </div>
            <a class="button secondary" href="{{ route('catalog') }}">{{ __('Find events ↗') }}</a>
        </div>
        <div class="order-list">
            @forelse($orders as $order)
                <div class="order-card">
                    <span class="order-icon">↗</span>
                    <div>
                        <small>{{ $order->created_at->format('d M Y · H:i') }}</small>
                        <h3>
                            <a href="{{ route('orders.show', $order) }}" style="text-decoration: none; color: inherit;">
                                {{ $order->items->first()?->event_title ?? __('Order') }}
                            </a>
                        </h3>
                        <p class="muted">
                            {{ __(':count tickets · Rp:total', ['count' => $order->items->sum('quantity'), 'total' => number_format($order->total,0,',','.')]) }}
                            @if($order->discount_code)
                                <span class="tag-promo">{{ __('Promo: :code', ['code' => $order->discount_code]) }}</span>
                            @endif
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <x-status-badge :status="$order->status" />
                        @if($order->status === 'paid')
                            <a href="{{ route('orders.ticket.pdf', $order) }}" class="button small" style="background: #0284c7; border-color: #0284c7; color: #fff; display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; font-size: 12px; text-decoration: none;" title="{{ __('Download E-Ticket (PDF)') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <span>{{ __('Download PDF') }}</span>
                            </a>
                        @endif
                        <a href="{{ route('orders.show', $order) }}" class="button small secondary" style="padding: 6px 12px; text-decoration: none;" title="{{ __('View Details') }}">→</a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <span>↗</span>
                    <h2>{{ __('No tickets yet. Never too late.') }}</h2>
                    <p>{{ __('The next concert could be your favorite story.') }}</p>
                    <a href="{{ route('catalog') }}" class="button">{{ __('Explore events') }}</a>
                </div>
            @endforelse
        </div>
        <div class="pagination">{{ $orders->links() }}</div>
    </section>
</x-layout>
