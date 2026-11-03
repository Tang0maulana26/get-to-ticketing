{{-- Forgot password request page. --}}
<x-layout title="Lupa Kata Sandi">
    <section class="auth-layout shell">
        <div class="auth-intro">
            <p class="eyebrow">PASSWORD RECOVERY</p>
            <h1>Akses akunmu<br>tetap aman.</h1>
            <p>Masukkan alamat email yang terdaftar untuk menerima petunjuk dan tautan pengaturan ulang kata sandi.</p>
            <div class="auth-art">STAY<br><span>SAFE</span><br>& SOUND. ↗</div>
        </div>

        <div class="auth-card">
            <h2>Lupa kata sandi?</h2>
            <p class="muted">Ketik email akunmu untuk mengatur ulang kata sandi.</p>

            @if(session('status'))
                <div class="notice success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if(session('demo_reset_link'))
                <div class="demo-note mb-4" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px;">
                    <strong style="color: #1e40af;">Simulasi Demo Lokal:</strong>
                    <p class="small-copy mt-1" style="color: #1e3a8a;">
                        Pada mode portofolio lokal tanpa server email eksternal, Anda dapat langsung mengklik tautan di bawah ini:
                    </p>
                    <a href="{{ session('demo_reset_link') }}" class="button small secondary mt-2" style="display: inline-block;">
                        Buka Formulir Kata Sandi Baru ↗
                    </a>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="post">
                @csrf
                <x-field label="Alamat Email Akun" name="email" type="email" required autocomplete="email" maxlength="255" />

                <button class="button full mt-2" type="submit">Kirim Tautan Reset →</button>
            </form>

            <p class="auth-switch">
                Sudah ingat kata sandi? <a class="text-link" href="{{ route('login') }}">Kembali ke masuk</a>
            </p>
        </div>
    </section>
</x-layout>

