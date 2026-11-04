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

                <div class="field">
                    <span>{{ __('Current Password (required to change password)') }}</span>
                    <div class="password-field-wrapper">
                        <input type="password" name="current_password" autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" aria-label="{{ __('Show password') }}" data-toggle-password>
                            <svg class="eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-hide" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    @error('current_password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field">
                    <span>{{ __('New Password') }}</span>
                    <div class="password-field-wrapper">
                        <input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" aria-label="{{ __('Show password') }}" data-toggle-password>
                            <svg class="eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-hide" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <small>{{ __('At least 10 characters, containing letters and numbers.') }}</small>
                    @error('password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field">
                    <span>{{ __('Confirm New Password') }}</span>
                    <div class="password-field-wrapper">
                        <input type="password" name="password_confirmation" autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" aria-label="{{ __('Show password') }}" data-toggle-password>
                            <svg class="eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-hide" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="mt-6">
                    <button class="button full" type="submit">{{ __('Save Profile Updates →') }}</button>
                </div>
            </div>
        </form>

        <div class="panel panel-danger mt-8">
            <h3>🗑️ {{ __('Delete My Account & Personal Data') }}</h3>
            <p class="muted small-copy mb-4">
                {{ __('Under the Right to be Forgotten, you may permanently delete your account. All your personal data, tickets, and order history will be removed from the system.') }}
            </p>
            <form action="{{ route('profile.destroy') }}" method="post" data-confirm="{{ __('Are you sure you want to permanently delete your account? This action cannot be undone.') }}">
                @csrf
                @method('DELETE')
                <div class="field mb-3">
                    <span>{{ __('Type Your Current Password to Confirm Deletion:') }}</span>
                    <div class="password-field-wrapper">
                        <input type="password" name="delete_password" required placeholder="{{ __('Your account password') }}">
                        <button type="button" class="password-toggle-btn" aria-label="{{ __('Show password') }}" data-toggle-password>
                            <svg class="eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-hide" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    @error('delete_password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
                <button class="button danger" type="submit">
                    {{ __('Permanently Delete My Account') }}
                </button>
            </form>
        </div>
    </section>
</x-layout>
