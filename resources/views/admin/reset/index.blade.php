{{-- Admin buyer data reset page with security guards and double-confirmation. --}}
<x-layout :title="__('Reset Buyer Data')" :admin="true">
    <section class="shell page-space">
        <a class="back-link" href="{{ route('admin.dashboard') }}">{{ __('← Admin Overview') }}</a>

        <div class="section-heading">
            <div>
                <p class="eyebrow">DATA MAINTENANCE & SAFETY</p>
                <h1>{{ __('Reset Buyer Data.') }}</h1>
                <p class="muted">{{ __('Clean test/demo transactions safely without affecting master events or admin accounts.') }}</p>
            </div>
        </div>

        <div class="stat-grid mb-8">
            <div class="stat-card">
                <p>{{ __('Total Orders') }}</p>
                <strong>{{ $orderCount }}</strong>
                <small class="muted">{{ __(':paid paid · :pending pending', ['paid' => $paidCount, 'pending' => $pendingCount]) }}</small>
            </div>
            <div class="stat-card">
                <p>{{ __('Issued Tickets') }}</p>
                <strong>{{ $ticketCount }}</strong>
            </div>
            <div class="stat-card">
                <p>{{ __('Demo Transaction Value') }}</p>
                <strong>Rp{{ number_format($revenue, 0, ',', '.') }}</strong>
            </div>
            <div class="stat-card">
                <p>{{ __('Transacting Customers') }}</p>
                <strong>{{ $buyerCount }}</strong>
            </div>
        </div>

        <div class="panel panel-danger" style="border-width: 2px; max-width: 860px; margin-inline: auto;">
            <div class="mb-6">
                <h2>{{ __('⚠️ Data Deletion Safety Guards') }}</h2>
                <p class="muted mt-2">
                    {{ __('This feature is designed specifically to clean dummy/portfolio transactions so the system can be re-tested from a fresh state.') }}
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div class="admin-card-safe">
                    <strong>{{ __('✓ Data That Remains SAFE (Not Deleted):') }}</strong>
                    <ul class="muted mt-2" style="padding-left: 1.25rem; font-size: 0.9rem;">
                        <li>{{ __('All concert events and performances') }}</li>
                        <li>{{ __('All banner images and gallery posters') }}</li>
                        <li>{{ __('Ticket categories (Regular, VIP, etc) and prices') }}</li>
                        <li>{{ __('Administrator accounts and registered user accounts') }}</li>
                    </ul>
                </div>

                <div class="admin-card-danger">
                    <strong>{{ __('✕ Data That Will Be CLEARED:') }}</strong>
                    <ul class="muted mt-2" style="padding-left: 1.25rem; font-size: 0.9rem;">
                        <li>{{ __('All customer orders (pending, paid, expired)') }}</li>
                        <li>{{ __('All issued ticket codes') }}</li>
                        <li>{{ __('Checkout history and cart transactions') }}</li>
                        <li>{{ __('Ticket stock will be 100% restored to initial capacity') }}</li>
                    </ul>
                </div>
            </div>

            <form action="{{ route('admin.reset.all') }}" method="post" data-confirm="{{ __('FINAL WARNING: Are you sure you want to delete all buyer transaction data now?') }}">
                @csrf

                <div class="form-row">
                    <label class="field">
                        <span>{{ __('1. Enter Your Active Admin Password:') }}</span>
                        <input type="password" 
                               name="current_password" 
                               required 
                               autocomplete="current-password" 
                               placeholder="{{ __('Current admin account password') }}">
                        @error('current_password')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="field">
                        <span>{!! __('2. Type confirmation phrase: <strong>RESET-DATA-PEMBELI</strong>') !!}</span>
                        <input type="text" 
                               name="confirmation_phrase" 
                               required 
                               placeholder="RESET-DATA-PEMBELI" 
                               style="font-family: monospace; font-weight: bold; text-transform: uppercase;">
                        @error('confirmation_phrase')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>
                </div>

                <div class="mt-6">
                    <button class="button danger full" type="submit">
                        {{ __('Confirm & Clear All Buyer Transaction Data →') }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-layout>

