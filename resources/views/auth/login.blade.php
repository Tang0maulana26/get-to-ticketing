{{-- Login page with username or email support, forgot password link and demo convenience. --}}
<x-layout title="Masuk">
    <section class="auth-layout shell">
        <div class="auth-intro">
            <p class="eyebrow">WELCOME BACK</p>
            <h1>Momennya<br>tinggal selangkah.</h1>
            <p>Masuk untuk melanjutkan pemesanan dan menemukan semua tiketmu di satu tempat.</p>
            <div class="auth-art">GOOD<br><span>TIMES</span><br>AHEAD. ↗</div>
        </div>

        <div class="auth-card">
            <h2>Senang kamu kembali.</h2>
            <p class="muted">Masuk ke akun The Get To kamu.</p>

            <form action="{{ route('login') }}" method="post" data-fetch-form data-redirect-from="redirect">
                @csrf
                <x-field label="Email atau Username" 
                         name="login" 
                         :value="old('login', old('email'))" 
                         required 
                         autocomplete="username" 
                         maxlength="255" 
                         placeholder="nama@email.com atau username" />

                <div class="field">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <span>Kata sandi</span>
                        <a href="{{ route('password.request') }}" class="text-link" style="font-size: 12px;">Lupa kata sandi?</a>
                    </div>
                    <div class="password-field-wrapper">
                        <input type="password" name="password" required autocomplete="current-password" maxlength="200">
                        <button type="button" class="password-toggle-btn" aria-label="Tampilkan kata sandi" data-toggle-password>
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
                    @error('password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <label class="check-field mb-4" style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Ingat saya di perangkat ini</span>
                </label>

                <button class="button full" type="submit">Masuk →</button>
            </form>

            <p class="auth-switch mt-4">
                Belum punya akun? <a class="text-link" href="{{ route('register') }}">Daftar sekarang</a>
            </p>

            <div class="privacy-badge mt-4 pt-3" style="border-top: 1px solid var(--line); font-size: 11px; color: var(--muted); display: flex; align-items: center; gap: 6px;">
                <span>🔒</span>
                <span>The Get To menjaga privasi Anda: Tanpa pelacak pihak ketiga dan data disimpan secara aman.</span>
            </div>
        </div>
    </section>
</x-layout>
