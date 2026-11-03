{{-- Admin customer list with order counts, total spent, CSV export and deletion action. --}}
<x-layout :title="__('Customers')" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">CUSTOMER OVERVIEW</p>
                <h1>{{ __('Customers.') }}</h1>
                <p class="muted">{{ __('Customer summary and purchasing activity.') }}</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a class="button secondary" href="{{ route('admin.customers.export') }}">
                    {{ __('Export CSV ⤓') }}
                </a>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Order count') }}</th>
                        <th>{{ __('Paid spending') }}</th>
                        <th>{{ __('Joined') }}</th>
                        <th>{{ __('Action') }}</th>
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
                                    <a class="text-link" href="{{ route('admin.users.edit', $customer) }}">{{ __('Edit') }}</a>
                                    <form action="{{ route('admin.users.destroy', $customer) }}" method="post" data-confirm="{{ __('Delete customer account :name? Associated orders and tickets will be cleared.', ['name' => $customer->name]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color: #b91c1c;">{{ __('Delete') }}</button>
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
