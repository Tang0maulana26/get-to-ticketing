{{-- Admin customer list with order counts, total spent, CSV export and deletion action. --}}
<x-layout title="Pelanggan" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">CUSTOMER OVERVIEW</p>
                <h1>Pelanggan.</h1>
                <p class="muted">Ringkasan pelanggan dan aktivitas pembeliannya.</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a class="button secondary" href="{{ route('admin.customers.export') }}">
                    Export CSV ⤓
                </a>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>Email</th>
                        <th>Jumlah pesanan</th>
                        <th>Total pembelian lunas</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                <strong>{{ $customer->name }}</strong>
                                <small class="muted" style="display: block;">&#64;{{ $customer->username ?? 'belum-set' }}</small>
                            </td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->orders_count }}</td>
                            <td>Rp{{ number_format($customer->spent ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $customer->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="text-link" href="{{ route('admin.users.edit', $customer) }}">Edit</a>
                                    <form action="{{ route('admin.users.destroy', $customer) }}" method="post" data-confirm="Hapus akun pelanggan {{ $customer->name }}? Riwayat pesanan dan tiket terkait akan dibersihkan.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color: #b91c1c;">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Belum ada pelanggan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $customers->links() }}</div>
    </section>
</x-layout>
