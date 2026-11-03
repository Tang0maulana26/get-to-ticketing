{{-- Customer order history ("My tickets"). --}}
<x-layout :title="__('My tickets')">
    <section class="shell page-space">
        <p class="eyebrow">{{ __('YOUR NEXT EXPERIENCE') }}</p>
        <div class="section-heading">
            <div>
                <h1>{{ __('My tickets') }}.</h1>
                <p class="muted">{{ __('Hi, :name. All your exciting plans are right here.', ['name' => auth()->user()->name]) }}</p>
            </div><a class="button secondary" href="{{ route('catalog') }}">{{ __('Find events ↗') }}</a>
        </div>
        <div class="order-list">@forelse($orders as $order)<a class="order-card" href="{{ route('orders.show', $order) }}"><span class="order-icon">↗</span>
                <div><small>{{ $order->created_at->format('d M Y · H:i') }}</small>
                    <h3>{{ $order->items->first()?->event_title ?? __('Order') }}</h3>
                    <p class="muted">{{ __(':count tickets · Rp:total', ['count' => $order->items->sum('quantity'), 'total' => number_format($order->total,0,',','.')]) }} @if($order->discount_code)<span class="tag-promo">{{ __('Promo: :code', ['code' => $order->discount_code]) }}</span>@endif</p>
                </div><x-status-badge :status="$order->status" /><span>→</span>
            </a>@empty<div class="empty-state"><span>↗</span>
                <h2>{{ __('No tickets yet. Never too late.') }}</h2>
                <p>{{ __('The next concert could be your favorite story.') }}</p><a href="{{ route('catalog') }}" class="button">{{ __('Explore events') }}</a>
            </div>@endforelse</div>
        <div class="pagination">{{ $orders->links() }}</div>
    </section>
</x-layout>