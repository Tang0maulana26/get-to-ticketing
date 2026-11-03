{{-- Admin overview with totals, actions and recent orders. --}}
<x-layout title="Ringkasan admin" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">ADMIN OVERVIEW</p>
                <h1>Ringkasan Pengelola.</h1>
                <p class="muted">Aktivitas penjualan, event aktif, dan ringkasan transaksi terbaru.</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a class="button secondary" href="{{ route('admin.discounts.create') }}">+ Kode diskon</a>
                <a class="button" href="{{ route('admin.events.create') }}">+ Buat event</a>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <p>Total event</p>
                <strong>{{ $eventCount }}</strong>
            </div>
            <div class="stat-card">
                <p>Total pesanan</p>
                <strong>{{ $orderCount }}</strong>
            </div>
            <div class="stat-card">
                <p>Nilai pesanan lunas</p>
                <strong>Rp{{ number_format($revenue, 0, ',', '.') }}</strong>
            </div>
            <div class="stat-card">
                <p>Pelanggan</p>
                <strong>{{ $customerCount }}</strong>
            </div>
        </div>

        <div class="flex items-center justify-between mb-5 mt-8">
            <h2>Pesanan terbaru</h2>
            <a class="text-link" href="{{ route('admin.reset.index') }}" style="color: #b91c1c; font-size: 0.9rem;">
                ⚠️ Kelola & Reset Data Pembeli →
            </a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>Event</th>
                        <th>Total Pembayaran</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <strong>{{ $order->user->name }}</strong>
                                <small>{{ $order->user->email }}</small>
                            </td>
                            <td>{{ $order->items->first()?->event_title }}</td>
                            <td>
                                <strong>Rp{{ number_format($order->total, 0, ',', '.') }}</strong>
                                @if($order->discount_amount > 0)
                                    <small style="color: #059669; display: block;">
                                        Diskon: -Rp{{ number_format($order->discount_amount, 0, ',', '.') }} ({{ $order->discount_code }})
                                    </small>
                                @endif
                            </td>
                            <td><x-status-badge :status="$order->status" /></td>
                            <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Belum ada pesanan. Pesanan pelanggan akan tampil di sini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $orders->links() }}</div>
    </section>
</x-layout>
