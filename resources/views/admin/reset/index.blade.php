{{-- Admin buyer data reset page with security guards and double-confirmation. --}}
<x-layout title="Reset Data Pembeli" :admin="true">
    <section class="shell page-space">
        <a class="back-link" href="{{ route('admin.dashboard') }}">← Ringkasan Admin</a>

        <div class="section-heading">
            <div>
                <p class="eyebrow">DATA MAINTENANCE & SAFETY</p>
                <h1>Reset Data Pembeli.</h1>
                <p class="muted">Bersihkan data transaksi uji coba/demo secara aman tanpa merusak master event atau akun pengelola.</p>
            </div>
        </div>

        <div class="stat-grid mb-8">
            <div class="stat-card">
                <p>Total Pesanan</p>
                <strong>{{ $orderCount }}</strong>
                <small class="muted">{{ $paidCount }} lunas · {{ $pendingCount }} pending</small>
            </div>
            <div class="stat-card">
                <p>Tiket Terbit</p>
                <strong>{{ $ticketCount }}</strong>
            </div>
            <div class="stat-card">
                <p>Nilai Transaksi Demo</p>
                <strong>Rp{{ number_format($revenue, 0, ',', '.') }}</strong>
            </div>
            <div class="stat-card">
                <p>Pelanggan Bertransaksi</p>
                <strong>{{ $buyerCount }}</strong>
            </div>
        </div>

        <div class="panel" style="border: 2px solid #ef4444; background: #fffdfd;">
            <div class="mb-6">
                <h2 style="color: #b91c1c;">⚠️ Pengaman Penghapusan Data (Safety Guards)</h2>
                <p class="muted mt-2">
                    Fitur ini dirancang khusus untuk membersihkan data transaksi dummy/portofolio agar sistem dapat diuji ulang dari keadaan bersih.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 1rem; border-radius: 4px;">
                    <strong style="color: #166534;">✓ Data Yang Tetap AMAN (Tidak Dihapus):</strong>
                    <ul class="muted mt-2" style="padding-left: 1.25rem; font-size: 0.9rem;">
                        <li>Semua event konser dan pertunjukan</li>
                        <li>Semua gambar banner dan galeri poster</li>
                        <li>Kategori tiket (Regular, VIP, dll) dan harga</li>
                        <li>Akun Administrator dan akun pengguna terdaftar</li>
                    </ul>
                </div>

                <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 1rem; border-radius: 4px;">
                    <strong style="color: #991b1b;">✕ Data Yang Akan DIBERSIHKAN:</strong>
                    <ul class="muted mt-2" style="padding-left: 1.25rem; font-size: 0.9rem;">
                        <li>Semua pesanan pelanggan (pending, paid, expired)</li>
                        <li>Semua kode tiket yang telah diterbitkan</li>
                        <li>Riwayat checkout dan keranjang transaksi</li>
                        <li>Stok tiket akan dipulihkan 100% ke kapasitas awal</li>
                    </ul>
                </div>
            </div>

            <form action="{{ route('admin.reset.all') }}" method="post" data-confirm="PERINGATAN TERAKHIR: Anda yakin ingin menghapus seluruh data transaksi pembeli sekarang?">
                @csrf

                <div class="form-row">
                    <label class="field">
                        <span>1. Masukkan Kata Sandi Admin Anda Aktif:</span>
                        <input type="password" 
                               name="current_password" 
                               required 
                               autocomplete="current-password" 
                               placeholder="Kata sandi akun admin saat ini">
                        @error('current_password')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="field">
                        <span>2. Ketik frasa konfirmasi: <strong>RESET-DATA-PEMBELI</strong></span>
                        <input type="text" 
                               name="confirmation_phrase" 
                               required 
                               placeholder="RESET-DATA-PEMBELI" 
                               style="font-family: monospace; font-weight: bold; text-transform: uppercase;">
                        @error('confirmation_phrase')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>
                </div>

                <div class="mt-6">
                    <button class="button full" style="background: #b91c1c; border-color: #991b1b; color: #ffffff;" type="submit">
                        Konfirmasi & Bersihkan Seluruh Data Transaksi Pembeli →
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-layout>

