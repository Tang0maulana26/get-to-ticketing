{{-- Reset password form page. --}}
<x-layout title="Atur Ulang Kata Sandi">
    <section class="auth-layout shell">
        <div class="auth-intro">
            <p class="eyebrow">NEW PASSWORD</p>
            <h1>Mulai dengan<br>sandi baru.</h1>
            <p>Buat kata sandi yang aman dan mudah kamu ingat untuk mengamankan tiket konsermu.</p>
            <div class="auth-art">NEW<br><span>KEY</span><br>FORWARD. ↗</div>
        </div>

        <div class="auth-card">
            <h2>Atur kata sandi baru.</h2>
            <p class="muted">Tentukan kata sandi baru untuk akun The Get To.</p>

            <form action="{{ route('password.update') }}" method="post">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <x-field label="Alamat Email" name="email" type="email" :value="$email" required autocomplete="email" maxlength="255" />

                <label class="field">
                    <span>Kata Sandi Baru</span>
                    <input type="password" name="password" required minlength="10" maxlength="200" autocomplete="new-password">
                    <small>Minimal 10 karakter, mengandung kombinasi huruf dan angka.</small>
                    @error('password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="field">
                    <span>Ulangi Kata Sandi Baru</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>

                <button class="button full mt-4" type="submit">Simpan Kata Sandi Baru →</button>
            </form>

            <p class="auth-switch">
                Ingin membatalkan? <a class="text-link" href="{{ route('login') }}">Kembali ke masuk</a>
            </p>
        </div>
    </section>
</x-layout>

