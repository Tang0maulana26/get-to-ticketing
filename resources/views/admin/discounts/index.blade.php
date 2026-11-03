{{-- Admin discount management list. --}}
<x-layout :title="__('Manage discounts')" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">PROMO & DISCOUNTS</p>
                <h1>{{ __('Discount Codes.') }}</h1>
                <p class="muted">{{ __('Manage promo codes, discount percentages, quotas, and validity.') }}</p>
            </div>
            <a class="button" href="{{ route('admin.discounts.create') }}">{{ __('+ Create Discount') }}</a>
        </div>

        <form class="filter-bar" method="get">
            <label class="search-field">
                <span>⌕</span>
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search promo code or name...') }}" aria-label="Cari diskon" maxlength="100">
            </label>
            <select name="status" aria-label="Status diskon">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
            </select>
            <button class="button secondary" type="submit">{{ __('Filter') }}</button>
            @if(request()->hasAny(['q', 'status']))
                <a class="text-link" href="{{ route('admin.discounts.index') }}">{{ __('Reset') }}</a>
            @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Promo Name') }}</th>
                        <th>{{ __('Discount Amount') }}</th>
                        <th>{{ __('Min. Purchase') }}</th>
                        <th>{{ __('Quota / Used') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discounts as $discount)
                        <tr>
                            <td>
                                <strong><code>{{ $discount->code }}</code></strong>
                            </td>
                            <td>
                                <strong>{{ $discount->name }}</strong>
                                @if($discount->event)
                                    <small style="color: #14446b; display: block;">🎯 Khusus: {{ $discount->event->title }}</small>
                                @else
                                    <small class="muted" style="display: block;">Semua Event</small>
                                @endif
                            </td>
                            <td>
                                @if($discount->type === 'percent')
                                    <strong>{{ $discount->value }}%</strong>
                                    @if($discount->max_discount_amount)
                                        <small>(Maks. Rp{{ number_format($discount->max_discount_amount, 0, ',', '.') }})</small>
                                    @endif
                                @else
                                    <strong>Rp{{ number_format($discount->value, 0, ',', '.') }}</strong>
                                @endif
                            </td>
                            <td>
                                {{ $discount->min_order_amount > 0 ? 'Rp' . number_format($discount->min_order_amount, 0, ',', '.') : 'Tanpa min.' }}
                            </td>
                            <td>
                                {{ $discount->used_count }} / {{ $discount->usage_limit ?? '∞' }}
                            </td>
                            <td>
                                @if($discount->active)
                                    <span class="badge badge-published">Aktif</span>
                                @else
                                    <span class="badge badge-draft">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="text-link" href="{{ route('admin.discounts.edit', $discount) }}">Edit</a>
                                    <form action="{{ route('admin.discounts.destroy', $discount) }}" method="post" data-confirm="Hapus atau nonaktifkan kode diskon ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Belum ada kode diskon. Buat kode promo pertamamu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $discounts->links() }}</div>
    </section>
</x-layout>

