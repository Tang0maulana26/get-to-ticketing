{{-- Event detail page with gallery, description and the ticket selection/booking form. --}}
<x-layout :title="$event->title">
    <div class="shell page-space">
        <a class="back-link" href="{{ route('catalog') }}#events">{{ __('← All events') }}</a>

        <div class="detail-grid">
            <div>
                <div class="detail-banner">
                    <img src="{{ $event->banner_url }}" alt="Poster {{ $event->title }}" width="800" height="600" fetchpriority="high" decoding="async">
                </div>

                <div class="detail-heading">
                    <span class="badge badge-published">{{ $event->category->name }}</span>
                    <h1>{{ $event->title }}</h1>
                    <p class="lead">{{ $event->summary }}</p>
                </div>

                <div class="event-facts">
                    <div>
                        <span class="fact-icon">▦</span>
                        <div>
                            <strong>{{ $event->start_time->locale(app()->getLocale())->translatedFormat('l, d F Y') }}</strong>
                            <span>
                                {{ $event->start_time->format('H:i') }} WIB
                                @if($event->end_time) – {{ $event->end_time->format('H:i') }} WIB @endif
                            </span>
                        </div>
                    </div>
                    <div>
                        <span class="fact-icon">⌖</span>
                        <div>
                            <strong>{{ $event->venue }}</strong>
                            <span>{{ $event->address }}, {{ $event->city }}</span>
                        </div>
                    </div>
                </div>

                <section class="description-block">
                    <h2>{{ __('About this event') }}</h2>
                    <div class="prose-content">{{ $event->description }}</div>
                </section>

                @if($event->images->isNotEmpty())
                    <section class="description-block">
                        <h2>{{ __('Closer to the moment') }}</h2>
                        <div class="gallery">
                            @foreach($event->images as $image)
                                <img src="{{ Storage::disk('public')->url($image->path) }}" alt="Galeri {{ $event->title }} {{ $loop->iteration }}" loading="lazy" width="800" height="600">
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="description-block">
                    <h2>{{ __('Before you go') }}</h2>
                    <p class="muted">{{ __('Please double-check the date, venue, and ticket category. Tickets are only valid for the specified event and category.') }}</p>
                </section>
            </div>

            <aside class="booking-panel">
                <p class="eyebrow">YOUR WAY IN</p>
                <h2>{{ __('Select your tickets.') }}</h2>
                <p class="muted">{{ __('Pick a category, choose the amount, get ready to go.') }}</p>

                @if($event->start_time->isPast())
                    <div class="notice">{{ __('This event has already passed.') }}</div>
                @else
                    <form action="{{ route('checkout', $event) }}" method="post" data-booking-form>
                        @csrf
                        <input type="hidden" name="checkout_key" value="{{ old('checkout_key', (string) Str::uuid()) }}">
                        <input type="hidden" name="event_id" value="{{ $event->id }}">

                        @forelse($event->ticketCategories as $type)
                            <div class="ticket-option">
                                <div class="ticket-option-heading">
                                    <h3>{{ $type->name }}</h3>
                                    <span class="availability">{{ $type->stock > 0 ? __('Available') : __('Sold out') }}</span>
                                </div>
                                <p>{{ $type->description }}</p>
                                <div class="ticket-option-bottom">
                                    <strong>Rp{{ number_format($type->price, 0, ',', '.') }}</strong>
                                    <div class="stepper">
                                        <button type="button" data-step="-1" aria-label="Kurangi {{ $type->name }}">−</button>
                                        <input type="number" 
                                               name="tickets[{{ $type->id }}]" 
                                               min="0" 
                                               max="{{ min($type->stock, $type->max_per_order) }}" 
                                               value="{{ old('tickets.'.$type->id, 0) }}" 
                                               data-price="{{ $type->price }}" 
                                               aria-label="Jumlah {{ $type->name }}" 
                                               @disabled($type->stock === 0)>
                                        <button type="button" data-step="1" aria-label="Tambah {{ $type->name }}">+</button>
                                    </div>
                                </div>
                                <small>{{ __('Max. :count tickets / order', ['count' => $type->max_per_order]) }}</small>
                            </div>
                        @empty
                            <div class="notice">{{ __('Ticket options will be available soon. Please check back later.') }}</div>
                        @endforelse

                        @if($event->ticketCategories->isNotEmpty())
                            <div class="discount-box mt-4 mb-2">
                                <label class="field mb-1">
                                    <span>{{ __('Have a discount code?') }}</span>
                                    <div class="discount-input-group">
                                        <input type="text" 
                                               name="discount_code" 
                                               id="discount-code-input" 
                                               value="{{ old('discount_code') }}" 
                                               placeholder="{{ __('Enter promo code') }}" 
                                               maxlength="50" 
                                               style="text-transform: uppercase;">
                                        <button type="button" class="button secondary small" id="apply-discount-btn">{{ __('Apply') }}</button>
                                    </div>
                                    @error('discount_code')
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </label>
                                <div id="discount-feedback" class="small-copy" style="display: none;"></div>
                            </div>

                            <div class="booking-breakdown" id="booking-breakdown" style="display: none; border-top: 1px dashed var(--line); padding-top: 0.75rem; margin-top: 0.75rem;">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 0.35rem;">
                                    <span class="muted">{{ __('Ticket subtotal:') }}</span>
                                    <strong id="breakdown-subtotal">Rp0</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 13px; color: #059669; margin-bottom: 0.35rem;" id="breakdown-discount-row">
                                    <span id="breakdown-discount-label">{{ __('Discount deduction:') }}</span>
                                    <strong id="breakdown-discount-amount">-Rp0</strong>
                                </div>
                            </div>

                            <div class="booking-total">
                                <span>{{ __('Total Payment') }} <small data-ticket-count>{{ __(':count tickets', ['count' => 0]) }}</small></span>
                                <strong data-ticket-total>Rp0</strong>
                            </div>
                            <div class="booking-quota-alert" data-quota-alert role="alert"></div>
                            <p class="muted small-copy">{{ __('Final total will be recalculated during order creation.') }}</p>

                            @auth
                                <button class="button full" type="submit" data-booking-submit>{{ __('Proceed to checkout →') }}</button>
                            @else
                                <a class="button full" href="{{ route('login') }}">{{ __('Sign in to book →') }}</a>
                            @endauth
                            <p class="booking-note">{{ __('Tickets are held for 15 minutes after checkout.') }}</p>
                        @endif
                    </form>
                @endif
            </aside>
        </div>
    </div>
</x-layout>
