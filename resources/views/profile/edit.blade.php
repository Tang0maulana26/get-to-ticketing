{{-- User account profile page with privacy controls and account deletion. --}}
<x-layout :title="__('Account Profile')">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">ACCOUNT SETTINGS</p>
                <h1>{{ __('Account Profile.') }}</h1>
                <p class="muted">{{ __('Manage your contact identity, username, and account password.') }}</p>
            </div>
            <div>
                <span class="badge {{ $user->isAdmin() ? 'badge-published' : 'badge-draft' }}">
                    {{ __('Account Status: :role', ['role' => $user->isAdmin() ? __('Administrator') : __('Customer')]) }}
                </span>
            </div>
        </div>

        <form class="admin-form" action="{{ route('profile.update') }}" method="post">
            @csrf
            @method('PUT')

            <div class="panel">
                <h2>{{ __('Personal Data & Identity') }}</h2>

                <x-field :label="__('Full name')" 
                         name="name" 
                         :value="$user->name" 
                         required 
                         maxlength="100" />

                <x-field :label="__('Username (unique handle for login)')" 
                         name="username" 
                         :value="$user->username" 
                         required 
                         maxlength="50" 
                         pattern="[a-zA-Z0-9_\-]+" />

                <x-field :label="__('Email Address')" 
                         name="email" 
                         type="email" 
                         :value="$user->email" 
                         required 
                         maxlength="255" />

                <div class="p-3 mt-3" style="background: var(--surface); border-radius: 6px; font-size: 12px; color: var(--muted);">
                    🛡️ <strong>{{ __('Safe Privacy Policy: We only store name, username, and email for ticket issuance. We do not collect sensitive data like phone number, national ID, or location.') }}</strong>
                </div>
            </div>

            <div class="panel">
                <h2>{{ __('Security & Password') }}</h2>
                <p class="muted small-copy mb-4">{{ __('Leave blank if you do not wish to change your password.') }}</p>

                <label class="field">
                    <span>{{ __('Current Password (required to change password)') }}</span>
                    <input type="password" name="current_password" autocomplete="current-password">
                </label>

                <label class="field">
                    <span>{{ __('New Password') }}</span>
                    <input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password">
                    <small>{{ __('At least 10 characters, containing letters and numbers.') }}</small>
                    @error('password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="field">
                    <span>{{ __('Confirm New Password') }}</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </label>

                <div class="mt-6">
                    <button class="button full" type="submit">{{ __('Save Profile Updates →') }}</button>
                </div>
            </div>
        </form>

        <div class="panel mt-8" style="border: 1px solid #fecaca; background: #fffdfd; max-width: 680px;">
            <h3 style="color: #991b1b; margin-bottom: 6px;">🗑️ {{ __('Delete My Account & Personal Data') }}</h3>
            <p class="muted small-copy mb-4">
                {{ __('Under the Right to be Forgotten, you may permanently delete your account. All your personal data, tickets, and order history will be removed from the system.') }}
            </p>
            <form action="{{ route('profile.destroy') }}" method="post" data-confirm="{{ __('Are you sure you want to permanently delete your account? This action cannot be undone.') }}">
                @csrf
                @method('DELETE')
                <label class="field mb-3">
                    <span>{{ __('Type Your Current Password to Confirm Deletion:') }}</span>
                    <input type="password" name="delete_password" required placeholder="{{ __('Your account password') }}">
                    @error('delete_password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </label>
                <button class="button secondary" style="color: #b91c1c; border-color: #f87171;" type="submit">
                    {{ __('Permanently Delete My Account') }}
                </button>
            </form>
        </div>
    </section>
</x-layout>
