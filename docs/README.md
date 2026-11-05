# Dokumentasi Teknis — Get To Ticketing

Selamat datang di pusat dokumentasi internal **Get To Ticketing**. Folder `docs/` ini dibuat khusus sebagai panduan pegangan (*handbook*) agar kapan pun kamu membuka kembali proyek ini di masa depan, kamu bisa langsung mengingat dan memahami bagaimana setiap baris kode, logika bisnis, dan fitur di aplikasi ini bekerja dari hulu ke hilir.

---

## Daftar Isi Dokumentasi

Silakan baca dokumen berikut secara berurutan atau langsung menuju topik yang ingin kamu pelajari:

### 1. [Arsitektur & Struktur Kode (`01-arsitektur-dan-struktur-kode.md`)](01-arsitektur-dan-struktur-kode.md)
- Peta folder proyek dan fungsi masing-masing direktori.
- Bagaimana alur perjalanan *Request* pengguna mulai dari klik di browser hingga menjadi *Response* halaman.
- Skema tabel database PostgreSQL dan bagaimana model Eloquent (`User`, `Event`, `TicketCategory`, `Order`, `Ticket`, `Discount`) saling terhubung.

### 2. [Alur Checkout, Penguncian Stok & Pembayaran (`02-alur-checkout-stok-dan-pembayaran.md`)](02-alur-checkout-stok-dan-pembayaran.md)
- Bedah tuntas `app/Services/BookingService.php` (jantung utama aplikasi).
- Cara kerja `lockForUpdate()` agar stok tiket tidak pernah minus (*overselling*) saat dipesan bersamaan.
- Cara kerja `checkout_key` (idempotensi) dan timer kedaluwarsa 15 menit.
- Cara kerja perhitungan diskon promo, simulasi pembayaran lokal, integrasi Xendit Sandbox, hingga pembuatan PDF E-Ticket dan pengiriman email.

### 3. [Sistem Gate Scanner & Sinkronisasi Offline (`03-sistem-gate-scanner-dan-offline-sync.md`)](03-sistem-gate-scanner-dan-offline-sync.md)
- Cara kerja halaman Gate Scanner (`resources/views/gate/scan.blade.php` & `public/gate-scanner.js`).
- Rahasia keamanan QR Code menggunakan tanda tangan digital **HMAC-SHA256** (`UUID.Signature`) agar tiket tidak bisa dipalsukan.
- Cara kerja pemindaian kamera (`jsqr.min.js`) dan alat scanner fisik PDA/Laser.
- Cara kerja **Mode 100% Offline** (*Local Manifest*) dan **Sinkronisasi 2 Arah Antar-Perangkat** (*Multi-Device Sync*).
- Pengaturan kunci sistem gate, jendela waktu buka (`start_time`), modal konfirmasi 2 langkah, dan toggle simpan riwayat scan.

### 4. [Frontend, Dwibahasa, Keamanan & Operasional (`04-frontend-dwibahasa-dan-keamanan.md`)](04-frontend-dwibahasa-dan-keamanan.md)
- Cara kerja sistem dwibahasa (Indonesia & Inggris) di Blade dan JavaScript (`data-i18n`), serta aturan penyusunan `lang/id.json` dan `lang/en.json`.
- Cara kerja fitur Admin (CRUD Event, proteksi stok terjual, kelola peran user, dan fitur *Reset Data Pembeli* dengan pengaman ganda).
- Konfigurasi keamanan *Content Security Policy* (`SecurityHeaders.php`).
- Tips menjalankan server lokal (Laravel Herd vs `php artisan serve` + Vite) dan cara mengatasi kendala umum (*troubleshooting*).

### 5. [Deployment Cloud: Database Supabase & Web Hosting Gratis (`05-deployment-supabase-dan-render.md`)](05-deployment-supabase-dan-render.md)
- Panduan lengkap menghubungkan PostgreSQL cloud di Supabase (Session Pooler IPv4).
- Cara kerja kontainerisasi `Dockerfile` (Multi-stage Node 22 + PHP 8.4-FPM + Nginx).
- Langkah demi langkah publikasi gratis ke Render.com beserta pengaturan Environment Variables.

---

## Ringkasan Cepat Perintah Penting

| Kebutuhan | Perintah Terminal |
| :--- | :--- |
| Menjalankan server Laravel | `php artisan serve` |
| Mengompilasi aset CSS & JS (Produksi/Aman) | `npm run build` |
| Menjalankan mode pengembangan frontend (Vite HMR) | `npm run dev` |
| Menjalankan seluruh pengujian otomatis (83 tes) | `php artisan test` |
| Melepas pesanan pending yang sudah lewat 15 menit | `php artisan orders:expire` |
| Membersihkan cache konfigurasi & view | `php artisan optimize:clear` |

