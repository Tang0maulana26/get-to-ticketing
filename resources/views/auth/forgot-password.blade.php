{{-- Forgot password request page. --}}
<x-layout :title="__('Password recovery')">
    <section class="auth-layout shell">
        <div class="auth-intro">
            <p class="eyebrow">PASSWORD RECOVERY</p>
            <h1>{{ __('Your account access remains secure.') }}</h1>
            <p>{{ __('Enter your registered email address to receive password reset instructions and link.') }}</p>
            <div class="auth-art">STAY<br><span>SAFE</span><br>& SOUND. ↗</div>
        </div>

        <div class="auth-card">
            <h2>{{ __('Forgot password?') }}</h2>
            <p class="muted">{{ __('Type your account email to reset your password.') }}</p>

            @if(session('status'))
                <div class="notice success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if(session('demo_reset_link'))
                <div class="demo-note mb-4" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px;">
                    <strong style="color: #1e40af;">{{ __('Password Reset Link:') }}</strong>
                    <p class="small-copy mt-1" style="color: #1e3a8a;">
                        {{ __('Click the button below to proceed with setting your new password:') }}
                    </p>
                    <a href="{{ session('demo_reset_link') }}" class="button small secondary mt-2" style="display: inline-block;">
                        {{ __('Open New Password Form ↗') }}
                    </a>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="post">
                @csrf
                <x-field :label="__('Account Email Address')" name="email" type="email" required autocomplete="email" maxlength="255" />

                <button class="button full mt-2" type="submit">{{ __('Send Reset Link →') }}</button>
            </form>

            <p class="auth-switch">
                {{ __('Remember your password?') }} <a class="text-link" href="{{ route('login') }}">{{ __('Back to login') }}</a>
            </p>
        </div>
    </section>
</x-layout>

