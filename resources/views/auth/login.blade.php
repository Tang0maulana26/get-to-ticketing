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
            <form action="{{ route('login') }}" method="post" data-fetch-form data-redirect-from="redirect">@csrf<x-field label="Email" name="email" type="email" required autocomplete="email" maxlength="255" /><label class="field"><span>Kata sandi</span><input type="password" name="password" required autocomplete="current-password" maxlength="200"></label><button class="button full">Masuk →</button></form>
            <p class="auth-switch">Belum punya akun? <a class="text-link" href="{{ route('register') }}">Daftar sekarang</a></p>
        </div>
    </section>
</x-layout>