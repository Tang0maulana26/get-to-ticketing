{{-- Admin discount create/edit form. --}}
<x-layout :title="$isEdit ? __('Edit Discount Code') : __('Create Discount Code')" :admin="true">
    <section class="shell page-space">
        <a class="back-link" href="{{ route('admin.discounts.index') }}">← {{ __('Manage discounts') }}</a>

        <div class="section-heading">
            <div>
                <h1>{{ $isEdit ? __('Update discount.') : __('Create new discount code.') }}</h1>
                <p class="muted">{{ __('Set discount rules, usage quotas, and promotional validity period.') }}</p>
            </div>
        </div>

        <form class="admin-form" action="{{ $isEdit ? route('admin.discounts.update', $discount) : route('admin.discounts.store') }}" method="post">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="panel">
                <h2>{{ __('Discount Rules') }}</h2>
                
                <div class="form-row">
                    <x-field :label="__('Discount Code (Capital letters & numbers)')" 
                             name="code" 
                             :value="$discount->code" 
                             placeholder="CONTOH: HEMAT20" 
                             required 
                             maxlength="50" 
                             style="text-transform: uppercase;" />
                             
                    <x-field :label="__('Promo Name / Description')" 
                             name="name" 
                             :value="$discount->name" 
                             placeholder="{{ __('Special Launch Promo') }}" 
                             required 
                             maxlength="120" />
                </div>

                <label class="field">
                    <span>{{ __('Event Scope (Optional)') }}</span>
                    <select name="event_id">
                        <option value="">{{ __('All Events (Global)') }}</option>
                        @foreach($events as $ev)
                            <option value="{{ $ev->id }}" @selected(old('event_id', $discount->event_id) == $ev->id)>
                                {{ $ev->title }} ({{ $ev->city }} - {{ $ev->start_time->format('d M Y') }})
                            </option>
                        @endforeach
                    </select>
                    <small class="muted">{{ __('Select \'All Events\' if the discount applies globally, or choose a specific event to restrict it.') }}</small>
                </label>

                <div class="form-row">
                    <label class="field">
                        <span>{{ __('Discount Type') }}</span>
                        <select name="type" id="discount-type">
                            <option value="percent" @selected(old('type', $discount->type) === 'percent')>{{ __('Percentage (%)') }}</option>
                            <option value="fixed" @selected(old('type', $discount->type) === 'fixed')>{{ __('Fixed Amount (Rp)') }}</option>
                        </select>
                    </label>

                    <x-field :label="__('Discount Value (Numbers only)')" 
                             name="value" 
                             type="number" 
                             :value="$discount->value" 
                             required 
                             min="1" />
                </div>

                <div class="form-row">
                    <x-field :label="__('Minimum Purchase (Rp, 0 for none)')" 
                             name="min_order_amount" 
                             type="number" 
                             :value="$discount->min_order_amount ?? 0" 
                             min="0" />

                    <x-field :label="__('Maximum Discount (Rp, optional for percentage)')" 
                             name="max_discount_amount" 
                             type="number" 
                             :value="$discount->max_discount_amount" 
                             placeholder="{{ __('Leave blank for unlimited') }}" 
                             min="0" />
                </div>
            </div>

            <div class="panel">
                <h2>{{ __('Quota & Schedule') }}</h2>

                <x-field :label="__('Usage Quota Limit (leave blank for unlimited)')" 
                         name="usage_limit" 
                         type="number" 
                         :value="$discount->usage_limit" 
                         placeholder="100" 
                         min="1" />

                <div class="form-row">
                    <x-field :label="__('Start Date (Optional)')" 
                             name="start_time" 
                             type="datetime-local" 
                             :value="$discount->start_time?->format('Y-m-d\TH:i')" />

                    <x-field :label="__('End Date / Expiry (Optional)')" 
                             name="end_time" 
                             type="datetime-local" 
                             :value="$discount->end_time?->format('Y-m-d\TH:i')" />
                </div>

                <label class="check-field mt-4">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $discount->active ?? true))>
                    {{ __('Activate this discount code now') }}
                </label>

                <div class="mt-6">
                    <button class="button full" type="submit">
                        {{ $isEdit ? __('Save Changes') : __('Create Discount Code') }} →
                    </button>
                </div>
            </div>
        </form>
    </section>
</x-layout>
