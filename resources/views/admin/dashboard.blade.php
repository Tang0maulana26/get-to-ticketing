<x-layout title="Ringkasan admin" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">ADMIN OVERVIEW</p>
                <h1>Selamat datang kembali.</h1>
                <p class="muted">Kelola event dan pantau pesanan dalam satu tempat.</p>
            </div><a class="button" href="{{ route('admin.events.create') }}">+ Buat event</a>
        </div>
        <div class="stat-grid">
            <div class="stat-card">
                <p>Total event</p><strong>{{ $eventCount }}</strong>
            </div>
            <div class="stat-card">
                <p>Total pesanan</p><strong>{{ $orderCount }}</strong>
            </div>
            <div class="stat-card">
                <p>Nilai pesanan lunas</p><strong>Rp{{ number_format($revenue,0,',','.') }}</strong>
            </div>
            <div class="stat-card">
                <p>Pelanggan</p><strong>{{ $customerCount }}</strong>
            </div>
        </div>
        <h2 class="mb-5">Pesanan terbaru</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>Event</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>@forelse($orders as $order)<tr>
                        <td><strong>{{ $order->user->name }}</strong><small>{{ $order->user->email }}</small></td>
                        <td>{{ $order->items->first()?->event_title }}</td>
                        <td>Rp{{ number_format($order->total,0,',','.') }}</td>
                        <td><x-status-badge :status="$order->status" /></td>
                        <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                    </tr>@empty<tr>
                        <td colspan="5">Belum ada pesanan. Pesanan pelanggan akan tampil di sini.</td>
                    </tr>@endforelse</tbody>
            </table>
        </div>
        <div class="pagination">{{ $orders->links() }}</div>
    </section>
</x-layout>