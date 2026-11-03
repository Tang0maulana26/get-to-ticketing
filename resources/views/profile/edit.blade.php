{{-- User account profile page with privacy controls and account deletion. --}}
<x-layout title="Profil Akun">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">ACCOUNT SETTINGS</p>
                <h1>Profil Akun.</h1>
                <p class="muted">Kelola identitas kontak, username, dan kata sandi akunmu.</p>
            </div>
            <div>
                <span class="badge {{ $user->isAdmin() ? 'badge-published' : 'badge-draft' }}">
                    Status Akun: {{ $user->isAdmin() ? 'Administrator' : 'Pelanggan' }}
                </span>
            </div>
        </div>

        <form class="admin-form" action="{{ route('profile.update') }}" method="post">
            @csrf
            @method('PUT')

            <div class="panel">
                <h2>Data Pribadi & Identitas</h2>

                <x-field label="Nama Lengkap" 
                         name="name" 
                         :value="$user->name" 
                         required 
                         maxlength="100" />

                <x-field label="Username (Panggilan unik untuk login)" 
                         name="username" 
                         :value="$user->username" 
                         required 
                         maxlength="50" 
                         pattern="[a-zA-Z0-9_\-]+" />

                <x-field label="Alamat Email" 
                         name="email" 
                         type="email" 
                         :value="$user->email" 
                         required 
                         maxlength="255" />

                <div class="p-3 mt-3" style="background: var(--surface); border-radius: 6px; font-size: 12px; color: var(--muted);">
                    🛡️ <strong>Prinsip Privasi Aman:</strong> Kami hanya menyimpan nama, username, dan email untuk keperluan penerbitan tiket Anda. Kami tidak mengumpulkan data sensitif seperti nomor telepon, NIK, atau lokasi Anda.
                </div>
            </div>

            <div class="panel">
                <h2>Keamanan & Kata Sandi</h2>
                <p class="muted small-copy mb-4">Kosongkan kolom di bawah jika tidak ingin mengganti kata sandi.</p>

                <label class="field">
                    <span>Kata Sandi Saat Ini (wajib jika mengganti sandi)</span>
                    <input type="password" name="current_password" autocomplete="current-password">
                </label>

                <label class="field">
                    <span>Kata Sandi Baru</span>
                    <input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password">
                    <small>Minimal 10 karakter, mengandung kombinasi huruf dan angka.</small>
                    @error('password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </label>

                <label class="field">
                    <span>Ulangi Kata Sandi Baru</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </label>

                <div class="mt-6">
                    <button class="button full" type="submit">Simpan Pembaruan Profil →</button>
                </div>
            </div>
        </form>

        <div class="panel mt-8" style="border: 1px solid #fecaca; background: #fffdfd; max-width: 680px;">
            <h3 style="color: #991b1b; margin-bottom: 6px;">🗑️ Hapus Akun & Data Pribadi Saya</h3>
            <p class="muted small-copy mb-4">
                Sesuai hak privasi pengguna (Right to be Forgotten), Anda dapat menghapus akun Anda secara permanen. Seluruh data identitas, tiket, dan riwayat pesanan Anda akan dibersihkan dari sistem.
            </p>
            <form action="{{ route('profile.destroy') }}" method="post" data-confirm="Apakah Anda yakin ingin menghapus akun Anda secara permanen? Tindakan ini tidak dapat dibatalkan.">
                @csrf
                @method('DELETE')
                <label class="field mb-3">
                    <span>Ketik Kata Sandi Anda Saat Ini untuk Konfirmasi Penghapusan:</span>
                    <input type="password" name="delete_password" required placeholder="Kata sandi akun Anda">
                    @error('delete_password')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </label>
                <button class="button secondary" style="color: #b91c1c; border-color: #f87171;" type="submit">
                    Hapus Akun Saya Secara Permanen
                </button>
            </form>
        </div>
    </section>
</x-layout>
