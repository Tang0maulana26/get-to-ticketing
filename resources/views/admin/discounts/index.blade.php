{{-- Admin discount management list. --}}
<x-layout :title="__('Manage discounts')" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <h1>{{ __('Discount Codes.') }}</h1>
                <p class="muted">{{ __('Manage promo codes, discount percentages, quotas, and validity.') }}</p>
            </div>
            <a class="button" href="{{ route('admin.discounts.create') }}">{{ __('+ Create Discount') }}</a>
        </div>

        <form class="filter-bar" method="get">
            <label class="search-field">
                <span>⌕</span>
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search promo code or name...') }}" aria-label="{{ __('Search discount') }}" maxlength="100">
            </label>
            <select name="status" aria-label="{{ __('Discount status') }}">
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
                                    <small class="tag-promo" style="display: inline-block; margin-top: 3px;">
                                        🎯 {{ __('Event Specific: :title', ['title' => $discount->event->title]) }}
                                    </small>
                                @else
                                    <small class="muted" style="display: block;">{{ __('All Events') }}</small>
                                @endif
                            </td>
                            <td>
                                @if($discount->type === 'percent')
                                    <strong>{{ $discount->value }}%</strong>
                                    @if($discount->max_discount_amount)
                                        <small>({{ __('Max. Rp:amount', ['amount' => number_format($discount->max_discount_amount, 0, ',', '.')]) }})</small>
                                    @endif
                                @else
                                    <strong>Rp{{ number_format($discount->value, 0, ',', '.') }}</strong>
                                @endif
                            </td>
                            <td>
                                {{ $discount->min_order_amount > 0 ? 'Rp' . number_format($discount->min_order_amount, 0, ',', '.') : __('No minimum') }}
                            </td>
                            <td>
                                {{ $discount->used_count }} / {{ $discount->usage_limit ?? '∞' }}
                            </td>
                            <td>
                                @if($discount->active)
                                    <span class="badge badge-published">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-draft">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="text-link" href="{{ route('admin.discounts.edit', $discount) }}">{{ __('Edit') }}</a>
                                    <form action="{{ route('admin.discounts.destroy', $discount) }}" method="post" data-confirm="{{ __('Delete or deactivate this discount code?') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">{{ __('No discount codes yet. Create your first promo code.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $discounts->links() }}</div>
    </section>
</x-layout>
