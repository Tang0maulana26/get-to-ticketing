<x-layout title="Pelanggan" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">CUSTOMER OVERVIEW</p>
                <h1>Pelanggan.</h1>
                <p class="muted">Ringkasan pelanggan dan aktivitas pembeliannya.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Jumlah pesanan</th>
                        <th>Total pembelian lunas</th>
                        <th>Bergabung</th>
                    </tr>
                </thead>
                <tbody>@forelse($customers as $customer)<tr>
                        <td><strong>{{ $customer->name }}</strong></td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->orders_count }}</td>
                        <td>Rp{{ number_format($customer->spent ?? 0,0,',','.') }}</td>
                        <td>{{ $customer->created_at->format('d M Y') }}</td>
                    </tr>@empty<tr>
                        <td colspan="5">Belum ada pelanggan.</td>
                    </tr>@endforelse</tbody>
            </table>
        </div>
        <div class="pagination">{{ $customers->links() }}</div>
    </section>
</x-layout>