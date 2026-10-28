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
            <form action="{{ route('register') }}" method="post">@csrf<x-field label="Nama lengkap" name="name" required autocomplete="name" maxlength="100" /><x-field label="Email" name="email" type="email" required autocomplete="email" maxlength="255" /><label class="field"><span>Kata sandi</span><input type="password" name="password" required minlength="10" maxlength="200" autocomplete="new-password"><small>Minimal 10 karakter, mengandung huruf dan angka.</small></label><label class="field"><span>Ulangi kata sandi</span><input type="password" name="password_confirmation" required autocomplete="new-password"></label><button class="button full">Buat akun →</button></form>
            <p class="auth-switch">Sudah punya akun? <a class="text-link" href="{{ route('login') }}">Masuk</a></p>
        </div>
    </section>
</x-layout>