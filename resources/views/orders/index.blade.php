<x-layout title="Tiket saya">
    <section class="shell page-space">
        <p class="eyebrow">YOUR NEXT EXPERIENCE</p>
        <div class="section-heading">
            <div>
                <h1>Tiket saya.</h1>
                <p class="muted">Hai, {{ auth()->user()->name }}. Semua rencana serumu ada di sini.</p>
            </div><a class="button secondary" href="{{ route('catalog') }}">Cari event ↗</a>
        </div>
        <div class="order-list">@forelse($orders as $order)<a class="order-card" href="{{ route('orders.show', $order) }}"><span class="order-icon">↗</span>
                <div><small>{{ $order->created_at->format('d M Y · H:i') }}</small>
                    <h3>{{ $order->items->first()?->event_title ?? 'Pesanan' }}</h3>
                    <p class="muted">{{ $order->items->sum('quantity') }} tiket · Rp{{ number_format($order->total,0,',','.') }}</p>
                </div><x-status-badge :status="$order->status" /><span>→</span>
            </a>@empty<div class="empty-state"><span>↗</span>
                <h2>Belum ada tiket. Belum terlambat.</h2>
                <p>Konser berikutnya bisa jadi cerita favoritmu.</p><a href="{{ route('catalog') }}" class="button">Jelajahi event</a>
            </div>@endforelse</div>
        <div class="pagination">{{ $orders->links() }}</div>
    </section>
</x-layout>