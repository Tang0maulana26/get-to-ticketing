{{-- Admin overview with totals, actions and recent orders. --}}
<x-layout :title="__('Admin overview')" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">ADMIN OVERVIEW</p>
                <h1>{{ __('Admin Overview.') }}</h1>
                <p class="muted">{{ __('Sales activity, active events, and recent transactions summary.') }}</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a class="button secondary" href="{{ route('gate.scan') }}" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                    <span>📱</span> {{ __('Gate Scanner') }}
                </a>
                <a class="button secondary" href="{{ route('admin.customers') }}">
                    {{ __('Customers Activity') }}
                </a>
                <a class="button secondary" href="{{ route('admin.discounts.create') }}">
                    {{ __('Discount code') }}
                </a>
                <a class="button" href="{{ route('admin.events.create') }}">
                    {{ __('+ Create event') }}
                </a>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <p>{{ __('Total events') }}</p>
                <strong>{{ $eventCount }}</strong>
            </div>
            <div class="stat-card">
                <p>{{ __('Total orders') }}</p>
                <strong>{{ $orderCount }}</strong>
            </div>
            <div class="stat-card">
                <p>{{ __('Paid order revenue') }}</p>
                <strong>Rp{{ number_format($revenue, 0, ',', '.') }}</strong>
            </div>
            <a class="stat-card" href="{{ route('admin.customers') }}" style="text-decoration: none; color: inherit; transition: border-color 0.15s;">
                <p>{{ __('Customers') }} ↗</p>
                <strong>{{ $customerCount }}</strong>
            </a>
            <a class="stat-card" href="{{ route('gate.scan') }}" style="text-decoration: none; color: inherit; transition: border-color 0.15s;">
                <p>{{ __('Gate attendance') }} ↗</p>
                <strong>{{ $checkedInCount }} <span style="font-size: 0.9rem; font-weight: 500; color: var(--muted);">/ {{ $totalValidTickets }}</span></strong>
                <small style="color: #059669; font-weight: 600; display: block; margin-top: 0.25rem;">
                    {{ $totalValidTickets > 0 ? round(($checkedInCount / $totalValidTickets) * 100, 1) : 0 }}% {{ __('admitted') }}
                </small>
            </a>
        </div>

        <div class="flex items-center justify-between mb-5 mt-8">
            <h2>{{ __('Recent orders') }}</h2>
            <a class="text-link" href="{{ route('admin.reset.index') }}" style="color: #b91c1c; font-size: 0.9rem;">
                {{ __('⚠️ Manage & Reset Buyer Data →') }}
            </a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Total Payment') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Created') }}</th>
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
                                {{ __('Discount:') }} -Rp{{ number_format($order->discount_amount, 0, ',', '.') }} ({{ $order->discount_code }})
                            </small>
                            @endif
                        </td>
                        <td><x-status-badge :status="$order->status" /></td>
                        <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">{{ __('No orders yet.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $orders->links() }}</div>
    </section>
</x-layout>