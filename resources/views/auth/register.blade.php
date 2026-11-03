{{-- Registration page with username, confirmed password, and privacy assurance. --}}
<x-layout title="Buat akun">
    <section class="auth-layout shell">
        <div class="auth-intro">
            <p class="eyebrow">BE PART OF THE MOMENT</p>
            <h1>Cerita baru.<br>Mulai dari kamu.</h1>
            <p>Buat akun untuk menyimpan tiket dan bersiap untuk konser berikutnya.</p>
            <div class="auth-art">SEE YOU<br><span>IN THE</span><br>CROWD. ↗</div>
        </div>

        <div class="auth-card">
            <h2>Buat akunmu.</h2>
            <p class="muted">Mulai perjalanan menuju panggung favorit.</p>

            <form action="{{ route('register') }}" method="post">
                @csrf
                <x-field label="Nama lengkap" name="name" required autocomplete="name" maxlength="100" placeholder="Nama lengkap Anda" />

                <x-field label="Username (panggilan unik)" 
                         name="username" 
                         required 
                         autocomplete="username" 
                         maxlength="50" 
                         placeholder="contoh: budi_konser" 
                         pattern="[a-zA-Z0-9_\-]+" 
                         title="Hanya huruf, angka, garis bawah (_), dan tanda hubung (-)" />

                <x-field label="Email" name="email" type="email" required autocomplete="email" maxlength="255" placeholder="nama@email.com" />

                <div class="field">
                    <span>Kata sandi</span>
                    <div class="password-field-wrapper">
                        <input type="password" name="password" required minlength="10" maxlength="200" autocomplete="new-password">
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
                    <small>Minimal 10 karakter, mengandung kombinasi huruf dan angka.</small>
                    @error('password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field">
                    <span>Ulangi kata sandi</span>
                    <div class="password-field-wrapper">
                        <input type="password" name="password_confirmation" required autocomplete="new-password">
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
                </div>

                <button class="button full mt-2" type="submit">Buat akun →</button>
            </form>

            <p class="auth-switch mt-4">
                Sudah punya akun? <a class="text-link" href="{{ route('login') }}">Masuk</a>
            </p>

            <div class="privacy-badge mt-4 pt-3" style="border-top: 1px solid var(--line); font-size: 11px; color: var(--muted); display: flex; align-items: flex-start; gap: 8px;">
                <span style="font-size: 14px;">🛡️</span>
                <span><strong>Privasi & Keamanan:</strong> Kami hanya menyimpan nama, username, dan email untuk pengiriman tiket. Tanpa pelacak pihak ketiga, tanpa iklan, dan data Anda tidak dijual.</span>
            </div>
        </div>
    </section>
</x-layout>
