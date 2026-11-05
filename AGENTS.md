# Aturan Kerja Get To Ticketing

Dokumen ini adalah acuan utama untuk agen coding di proyek Laravel ini. Jelaskan setiap keputusan dan hasil kepada pemilik proyek dalam bahasa Indonesia yang mudah dipahami. Baca kode yang relevan sebelum mengubahnya; jangan menganggap rencana sebagai fitur yang sudah selesai.

## Tujuan dan cakupan

- Bangun aplikasi ticketing konser yang mudah dipelajari dan layak menjadi portofolio profesional. Struktur kategori dan event dibuat modular agar dapat dipakai ulang untuk jenis acara lain nanti; dukungan film belum menjadi target aktif.
- Stack saat ini: Laravel 13, Blade, Tailwind CSS 4 melalui Vite/npm, dan PostgreSQL lokal. Laravel Herd dapat menyediakan PHP; server pengembangan dapat dijalankan dengan `php artisan serve`. Node.js dipakai untuk aset frontend, bukan sebagai backend saat ini.
- Tampilan pengunjung: *light mode* sebagai bawaan (putih hangat dan biru muda), *dark mode* opsional (`public/theme.js`), sudut tegas yang modern, animasi ringan, alur beli yang jelas. Tampilan Admin: rapi, presisi, dan mudah dioperasikan.
- Jangan memigrasikan backend ke Node.js, menghubungkan Supabase/cloud, menerbitkan situs ke produksi, atau menghapus pengaman stok tanpa permintaan eksplisit pengguna.

## Kondisi proyek saat aturan ini diperbarui (5 November 2026)

- **Katalog & Pemesanan:** Katalog konser, filter kategori/kota, detail event dengan galeri foto, stepper kuantitas tiket (batas maks. 20 tiket/pesanan), serta validasi kode promo/diskon interaktif via AJAX (`DiscountCheckController`) sudah berjalan penuh.
- **Transaksi & E-Ticket:** `app/Services/BookingService.php` menangani transaksi pemesanan, penguncian stok (`lockForUpdate`), idempotensi (`checkout_key`), pembatalan/kedaluwarsa 15 menit (`routes/console.php`), simulasi pembayaran instan lokal, integrasi Xendit Sandbox (`XenditPaymentController`), penerbitan tiket UUID, pengiriman email konfirmasi (`OrderPaidMail`), serta pembuatan PDF E-Ticket (`TicketPdfController`) dan *Mobile QR Pass*.
- **Riwayat Pelanggan ("My Tickets"):** Dilengkapi pengelompokan tab **Semua Pesanan**, **Tiket Aktif** (acara mendatang), dan **Riwayat Tiket Lalu** (acara selesai/pesanan kedaluwarsa).
- **Gate Check-In & Scanner (`GateCheckInController` & `public/gate-scanner.js`):** Mendukung kamera HP/laptop (`jsqr.min.js`), unggah gambar QR, alat fisik *PDA laser scanner*, verifikasi tanda tangan kriptografis **HMAC-SHA256** anti-pemalsuan, pelindung input format UUID (`Str::isUuid`) agar bebas error PostgreSQL `SQLSTATE[22P02]`, mode 100% offline (*Local Manifest*) dengan *auto-sync* 2 arah antar-perangkat panitia, modal konfirmasi 2 langkah pada tombol *Quick Action*, serta saklar toggle simpan riwayat scan lokal (`gate_recent_scans_{eventId}`).
- **Panel Admin:** Mencakup dashboard 5 kartu metrik sejajar (`stat-grid`), CRUD event & kategori tiket (dengan perlindungan stok terjual), CRUD kode diskon, ringkasan & ekspor CSV pelanggan, manajemen pengguna & peran (`admin`, `gate_staff`, `customer`), serta fitur **Reset Data Pembeli** (`BuyerResetController`) dengan verifikasi ganda (kata sandi admin + frasa konfirmasi).
- **Dokumentasi Teknis:** `README.md` di root menyajikan ringkasan formal portofolio, sedangkan penjelasan teknis mendalam mengenai cara kerja seluruh kode tersimpan di folder **`docs/`** (`docs/01-...` s.d. `docs/04-...`).
- **Hasil pengujian terakhir yang tercatat:** **83 tes lulus, 381 assertion**.

## Aturan implementasi

- Ikuti pola Laravel: route tipis, Form Request untuk validasi, policy/middleware untuk otorisasi, dan service (`BookingService`) untuk aturan bisnis lintas controller. Pertahankan nama dan struktur kode yang konsisten.
- Setiap checkout, perubahan stok, penggunaan diskon, pembatalan, penyelesaian pembayaran, dan *check-in* gate harus aman terhadap permintaan bersamaan (*concurrency*). Gunakan transaksi database dan penguncian baris (`lockForUpdate`); jangan mengurangi stok atau memvalidasi tiket hanya di frontend.
- Simpan harga sebagai bilangan bulat Rupiah. Pertahankan *snapshot* harga/nama item pesanan di `order_items` agar riwayat tidak berubah ketika admin mengedit kategori tiket. Lindungi stok yang sudah terjual saat admin mengubah kapasitas atau menghapus tiket.
- Gunakan komponen Blade yang rapi, ikon SVG yang konsisten (tidak berlebihan), serta tampilkan pesan validasi dan status pesanan yang jelas. Hindari teks berlabel "demo" pada UI publik kecuali pada tombol *Instant Demo Simulation* di halaman detail pesanan.
- Jangan menaruh ekspresi Blade `{{ ... }}` di tengah nilai properti CSS inline seperti `style="display: {{ ... }};"`. Gunakan direktif Blade `@if / @else` yang membungkus nilai CSS valid utuh, atau direktif `@style([...])` agar bebas dari error parser linter editor.
- **Dwibahasa & Struktur File Bahasa:** Seluruh teks antarmuka wajib mendukung dwibahasa (ID & EN) melalui `{{ __('...') }}` yang diselaraskan di `lang/id.json` dan `lang/en.json`. Kedua file JSON wajib tetap valid RFC 8259 (tanpa komentar `//` mentah di luar string), bebas dari key duplikat, dan dikelompokkan secara alfabetis di dalam 8 blok section menggunakan penanda key JSON (`"// --- 1. COMMON & NAVIGATION ---"`, dst.). Untuk komponen interaktif JavaScript, kirimkan kamus terjemahan melalui atribut `data-i18n`.
- Pertahankan escaping Blade (atau pembuatan elemen DOM aman dengan `textContent` di JS), CSRF, pembatasan *rate limit* (`throttle`), pemeriksaan hak akses, validasi unggahan gambar, dan *security headers*.
- **CSP & Hardware Permissions:** Pastikan `Permissions-Policy: camera=(self)` dan `media-src 'self' blob:` diizinkan di `SecurityHeaders.php`. Seluruh library operasional (seperti `jsqr.min.js` dan `gate-scanner.js`) wajib di-host lokal di `public/` agar mematuhi aturan CSP `script-src 'self'` dan bekerja 100% offline.
- **Otorisasi Gate:** Konfigurasi sistem gate (saklar buka/tutup sistem dan jendela waktu scan `start_time`) dibatasi hanya untuk `admin`. Peran `gate_staff` dibatasi untuk operasi pemindaian dan sinkronisasi antrean tiket.

## Database dan data lokal

- PostgreSQL lokal saat ini memakai `127.0.0.1:5433`; konfigurasi aplikasi ada di `.env`. Data cluster lokal berada di `storage/app/private/postgres/`. Data PostgreSQL bukan file SQL biasa: jangan hapus, pindah, atau edit folder cluster secara manual.
- `.env`, `.env.testing`, kredensial demo (`storage/app/private/demo-accounts.json`), `public/hot`, `vendor`, `node_modules`, dan folder data PostgreSQL diabaikan oleh Git. Jangan mencetak rahasia atau memasukkannya ke commit. Untuk contoh konfigurasi publik, perbarui `.env.example` tanpa nilai rahasia.
- Ubah skema melalui migration baru dan verifikasi dampaknya pada data lama. Jangan menjalankan `migrate:fresh`, `db:wipe`, reseed destruktif, atau mengganti database aktif tanpa instruksi eksplisit dari pengguna. Gunakan database pengujian terpisah (`ticketing_test`).

## Verifikasi dan pemeliharaan

- Untuk perubahan backend yang memengaruhi perilaku, jalankan tes (`php artisan test`) dan pastikan seluruh pengujian lulus. Untuk perubahan frontend, jalankan `npm run build` agar aset di `public/build/` selalu mutakhir tanpa bergantung pada server dev Vite (`public/hot`).
- Jika ada penambahan atau perubahan cara kerja fitur besar di masa mendatang, perbarui juga panduan terkait di dalam folder `docs/` agar dokumentasi teknis tetap sinkron dengan kode.
