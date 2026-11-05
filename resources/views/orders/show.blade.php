{{-- Order detail: status, countdown, payment/cancel actions and issued tickets. --}}
<x-layout :title="__('Order details')">
    <section class="shell page-space order-detail"><a class="back-link" href="{{ route('orders.index') }}">{{ __('← My tickets') }}</a>
        <div class="section-heading">
            <div>
                <h1>{{ $order->status === 'paid' ? __('You\'re all set') : __('Order details') }}</h1>
            </div><x-status-badge :status="$order->status" />
        </div>
        <div class="panel">
            <p class="muted small-copy">{{ __('Order No.: :number', ['number' => $order->number]) }}</p>@foreach($order->items as $item)<div class="order-line">
                <div>
                    <h2>{{ $item->event_title }}</h2>
                    <p class="muted">{{ $item->event_start_time->locale(app()->getLocale())->translatedFormat('d M Y · H:i') }} WIB · {{ $item->venue }}</p>
                    <p>{{ $item->ticket_name }} × {{ $item->quantity }}</p>
                </div><strong>Rp{{ number_format($item->subtotal,0,',','.') }}</strong>
            </div>@endforeach
            @if($order->discount_amount > 0)
            <div class="booking-breakdown mt-4 pt-3" style="border-top: 1px dashed var(--line);">
                <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 0.35rem;">
                    <span class="muted">{{ __('Ticket subtotal:') }}</span>
                    <strong>Rp{{ number_format($order->subtotal, 0, ',', '.') }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 14px; color: #059669; margin-bottom: 0.35rem;">
                    <span>{{ __('Discount deduction:') }} ({{ $order->discount_code }}):</span>
                    <strong>-Rp{{ number_format($order->discount_amount, 0, ',', '.') }}</strong>
                </div>
            </div>
            @endif
            <div class="booking-total">
                <span>{{ __('Total payment') }}</span>
                <strong>Rp{{ number_format($order->total, 0, ',', '.') }}</strong>
            </div>

            @if($order->status === 'pending')
                <div class="notice">
                    {{ __('Complete payment before :time. After that, reservation expires and ticket stock is released.', ['time' => $order->expires_at->locale(app()->getLocale())->translatedFormat('d M Y, H:i:s').' WIB']) }}
                </div>

                {{-- Payment Section --}}
                <div class="payment-section mt-4 pt-4" style="border-top: 1px solid var(--line);">
                    <div class="payment-header mb-3" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong style="font-size: 14.5px; color: var(--strong); display: flex; align-items: center; gap: 6px;">
                                {{ __('Payment Options') }}
                            </strong>
                            <p class="muted small-copy mt-0.5">{{ __('Xendit (Virtual Account, QRIS, E-Wallet, Card)') }}</p>
                        </div>
                        <span class="badge badge-published" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">
                            {{ __('Xendit Test Sandbox') }}
                        </span>
                    </div>

                    {{-- Xendit Payment Action --}}
                    <div class="payment-box p-3 mb-3" style="background: var(--surface); border: 1px solid var(--line); border-radius: 8px;">
                        @if($order->xendit_invoice_url)
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 13px; font-weight: 600; color: var(--strong);">{{ __('Active Xendit Invoice') }}</span>
                                    <small class="muted" style="font-family: monospace; font-size: 11px;">ID: {{ $order->xendit_invoice_id }}</small>
                                </div>
                                <p class="small-copy muted">
                                    {{ __('An invoice has been generated on Xendit. Please continue to the checkout simulator or check payment confirmation.') }}
                                </p>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
                                    <a href="{{ $order->xendit_invoice_url }}" target="_blank" rel="noopener" class="button" style="background: #0070ba; border-color: #0070ba; color: #fff; flex: 1; text-align: center;">
                                        {{ __('Open Xendit Checkout ↗') }}
                                    </a>
                                    <a href="{{ route('orders.xendit.check', $order) }}" class="button secondary" style="flex: 1; text-align: center;">
                                        {{ __('Check Payment Status') }}
                                    </a>
                                </div>
                            </div>
                        @else
                            <form action="{{ route('orders.xendit.checkout', $order) }}" method="post">
                                @csrf
                                <button type="submit" class="button full" style="background: #0f62fe; border-color: #0f62fe; color: #ffffff; font-weight: 600; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                    <span>{{ __('Pay via Xendit Sandbox →') }}</span>
                                </button>
                                <p class="muted small-copy mt-2 text-center" style="font-size: 11.5px; text-align: center;">
                                    {{ __('You will be redirected to official Xendit checkout for secure test payments.') }}
                                </p>
                            </form>
                        @endif
                    </div>

                    {{-- Local instant demo simulation --}}
                    @if(app()->environment('local','testing') && config('ticketing.demo_payments'))
                        <div class="demo-box mt-3 pt-3" style="border-top: 1px dashed var(--line);">
                            <div class="demo-note mb-2" style="font-size: 12px;">
                                <strong>{{ __('Instant Demo Simulation') }}</strong>
                                <p class="small-copy mt-0.5">{{ __('Instant test without leaving the website:') }}</p>
                            </div>
                            <form action="{{ route('orders.pay', $order) }}" method="post">
                                @csrf
                                <button class="button secondary full small" type="submit">{{ __('Simulate instant payment →') }}</button>
                            </form>
                        </div>
                    @endif

                    <form action="{{ route('orders.cancel', $order) }}" method="post" data-confirm="{{ __('Cancel order and restore ticket stock?') }}">
                        @csrf
                        <button class="button secondary full mt-3" style="color: #b91c1c; border-color: #fca5a5;">{{ __('Cancel order') }}</button>
                    </form>
                </div>
            @endif
        </div>
        @if($order->status === 'paid')
        <div class="tickets-heading-group">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px; margin-bottom: 8px;">
                <div>
                    <h2>{{ __('Your tickets') }}</h2>
                    <p class="muted">{{ __('Save this ticket number and present the QR code at the venue entrance.') }}</p>
                    @if($order->payment_method)
                        <p class="small-copy mt-1" style="color: #059669; font-size: 12.5px; font-weight: 500;">
                            ✓ {{ __('Paid via :method', ['method' => strtoupper($order->payment_method) . ($order->payment_channel ? ' ('.$order->payment_channel.')' : '')]) }}
                        </p>
                    @endif
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <a href="{{ route('orders.ticket.pdf', $order) }}" class="button" style="background: #0284c7; border-color: #0284c7; color: #ffffff; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>{{ __('Download E-Ticket (PDF)') }}</span>
                    </a>
                    <form action="{{ route('orders.resend.email', $order) }}" method="post" style="display: inline;">
                        @csrf
                        <button type="submit" class="button secondary" style="display: inline-flex; align-items: center; gap: 6px;" title="{{ __('Send copy of invoice & e-ticket to your email') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span>{{ __('Send to Email') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @foreach($order->items as $item)
            @foreach($item->tickets as $ticket)
                <article class="issued-ticket">
                    <div>
                        <h3>{{ $item->event_title }}</h3>
                        <p>{{ $item->ticket_name }} · {{ __('1 person') }}</p>
                        <code>{{ $ticket->code }}</code>
                    </div>
                    <div class="issued-ticket-actions">
                        <x-status-badge :status="$ticket->status" />
                        <button type="button" class="button small" data-open-modal="ticket-pass-{{ $ticket->id }}" style="display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            <span>{{ __('View QR Pass') }}</span>
                        </button>
                    </div>
                </article>

                {{-- Interactive Mobile-First Digital Admission Pass Modal --}}
                <x-modal :id="'ticket-pass-' . $ticket->id" :title="__('Digital Admission Pass')">
                    <div class="mobile-pass-card">
                        <div class="pass-header">
                            <span class="pass-badge">{{ $item->ticket_name }}</span>
                            <h3 class="pass-event">{{ $item->event_title }}</h3>
                            <p class="pass-venue">
                                {{ $item->venue }}<br>
                                {{ $item->event_start_time ? $item->event_start_time->locale(app()->getLocale())->translatedFormat('l, d F Y · H:i') : '-' }} WIB
                            </p>
                        </div>

                        <div class="pass-qr-wrapper">
                            @if($ticket->qr_code_svg)
                                <img src="{{ $ticket->qr_code_svg }}" alt="QR Code {{ $ticket->code }}" class="pass-qr-img" width="220" height="220">
                            @endif
                            <div class="pass-scan-line" aria-hidden="true"></div>
                        </div>

                        <div class="pass-code-pill">
                            <span class="pass-code-label">{{ __('TICKET CODE') }}</span>
                            <code class="pass-code-val">{{ $ticket->code }}</code>
                        </div>

                        <div class="pass-security-indicator">
                            <span class="live-pulse-dot" aria-hidden="true"></span>
                            <span>{{ __('Official Entry Pass • Live Admission Token') }}</span>
                        </div>

                        <div class="pass-holder-info">
                            <div>
                                <small class="muted">{{ __('TICKET HOLDER') }}</small>
                                <strong>{{ $order->customer_name ?? $order->user->name }}</strong>
                            </div>
                            <div style="text-align: right;">
                                <small class="muted">{{ __('ADMISSION STATUS') }}</small>
                                <div><x-status-badge :status="$ticket->status" /></div>
                            </div>
                        </div>

                        <div class="pass-warning-note">
                            {{ __('Show this screen directly to venue staff at the gate. Screenshots or duplicate copies will be rejected by the ticket scanner.') }}
                        </div>
                    </div>
                </x-modal>
            @endforeach
        @endforeach 
        @endif
    </section>
</x-layout>