{{-- Admin edit user and role form. --}}
<x-layout title="Edit Pengguna" :admin="true">
    <section class="shell page-space">
        <a class="back-link" href="{{ route('admin.users.index') }}">← Daftar Pengguna</a>

        <div class="section-heading">
            <div>
                <p class="eyebrow">USER PROFILE & PERMISSIONS</p>
                <h1>Edit Data Pengguna.</h1>
                <p class="muted">Perbarui data kontak, kata sandi, dan peran hak akses pengguna.</p>
            </div>
        </div>

        <form class="admin-form" action="{{ route('admin.users.update', $user) }}" method="post">
            @csrf
            @method('PUT')

            <div class="panel">
                <h2>Identitas Akun</h2>

                <x-field label="Nama Lengkap" 
                         name="name" 
                         :value="$user->name" 
                         required 
                         maxlength="100" />

                <x-field label="Username (Panggilan unik)" 
                         name="username" 
                         :value="$user->username" 
                         required 
                         maxlength="50" />

                <x-field label="Alamat Email" 
                         name="email" 
                         type="email" 
                         :value="$user->email" 
                         required 
                         maxlength="255" />

                <label class="field">
                    <span>Peran Akses (Role)</span>
                    <select name="role" @disabled(auth()->id() === $user->id)>
                        <option value="customer" @selected(old('role', $user->role) === 'customer')>
                            Pelanggan (Customer) — Pemesanan tiket, riwayat akun
                        </option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>
                            Admin — Akses penuh ke dashboard, event, diskon & pengguna
                        </option>
                    </select>
                    @if(auth()->id() === $user->id)
                        <input type="hidden" name="role" value="admin">
                        <small class="muted">Anda tidak dapat mengubah peran akun admin Anda sendiri.</small>
                    @else
                        <small class="muted">Pilih 'Admin' untuk memberikan hak akses penuh ke panel pengelola.</small>
                    @endif
                </label>
            </div>

            <div class="panel">
                <h2>Ganti Kata Sandi (Opsional)</h2>
                <p class="muted small-copy mb-4">Kosongkan jika tidak ingin mengganti kata sandi pengguna.</p>

                <label class="field">
                    <span>Kata Sandi Baru</span>
                    <input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password">
                    <small>Minimal 10 karakter, kombinasi huruf dan angka.</small>
                </label>

                <label class="field">
                    <span>Konfirmasi Kata Sandi Baru</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </label>

                <div class="mt-6">
                    <button class="button full" type="submit">Simpan Perubahan Pengguna →</button>
                </div>
            </div>
        </form>

        @if($user->role === 'customer' && $user->orders_count > 0)
            <div class="panel mt-8" style="border: 1px solid #fecaca; background: #fff5f5;">
                <h3 style="color: #991b1b;">⚠️ Area Reset Pesanan Pelanggan Ini</h3>
                <p class="muted small-copy mb-4">
                    Pelanggan ini memiliki {{ $user->orders_count }} pesanan tercatat. Anda dapat mereset riwayat pesanan pelanggan ini tanpa menghapus akunnya.
                </p>
                <form action="{{ route('admin.reset.customer', $user) }}" method="post" data-confirm="Yakin ingin mereset dan menghapus seluruh riwayat pesanan pelanggan {{ $user->name }}? Stok tiket reservasi akan dikembalikan.">
                    @csrf
                    <label class="field mb-4">
                        <span>Masukkan Kata Sandi Admin Anda untuk Konfirmasi:</span>
                        <input type="password" name="current_password" required placeholder="Kata sandi admin aktif">
                    </label>
                    <button class="button secondary" style="color: #b91c1c; border-color: #f87171;" type="submit">
                        Reset Pesanan {{ $user->name }} Saja
                    </button>
                </form>
            </div>
        @endif
    </section>
</x-layout>

