{{-- Reset password form page. --}}
<x-layout :title="__('Reset password')">
    <section class="auth-layout shell">
        <div class="auth-intro">
            <p class="eyebrow">NEW PASSWORD</p>
            <h1>{{ __('Start fresh with a new password.') }}</h1>
            <p>{{ __('Create a strong, memorable password to secure your concert tickets.') }}</p>
            <div class="auth-art">NEW<br><span>KEY</span><br>FORWARD. ↗</div>
        </div>

        <div class="auth-card">
            <h2>{{ __('Set a new password.') }}</h2>
            <p class="muted">{{ __('Choose a new password for your The Get To account.') }}</p>

            <form action="{{ route('password.update') }}" method="post">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <x-field label="Email" name="email" type="email" :value="$email" required autocomplete="email" maxlength="255" />

                <label class="field">
                    <span>{{ __('New Password') }}</span>
                    <input type="password" name="password" required minlength="10" maxlength="200" autocomplete="new-password">
                    <small>{{ __('At least 10 characters, containing letters and numbers.') }}</small>
                    @error('password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="field">
                    <span>{{ __('Confirm New Password') }}</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>

                <button class="button full mt-4" type="submit">{{ __('Save New Password →') }}</button>
            </form>

            <p class="auth-switch">
                {{ __('Want to cancel?') }} <a class="text-link" href="{{ route('login') }}">{{ __('Back to login') }}</a>
            </p>
        </div>
    </section>
</x-layout>

