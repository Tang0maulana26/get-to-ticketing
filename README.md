# Get To Ticketing

**Get To Ticketing** adalah aplikasi web *full-stack* pemesanan tiket konser dan manajemen *gate check-in* berbasis **Laravel 13** dan **PostgreSQL**. Proyek ini dirancang sebagai portofolio rekayasa perangkat lunak untuk mendemonstrasikan pengelolaan transaksi konkuren, keamanan tiket kriptografis, serta operasional pemindaian tiket secara *real-time* maupun *offline*.

---

## Fitur Utama

### 1. Katalog & Pemesanan Tiket (*Ticketing & Checkout*)
- **Eksplorasi & Filter Acara:** Pencarian konser berdasarkan kata kunci, kategori, dan kota dengan antarmuka responsif (*light/dark mode*).
- **Penguncian Stok Konkuren:** Menggunakan transaksi database dan *row-level locking* (`SELECT ... FOR UPDATE`) untuk mencegah *overselling* saat terjadi pemesanan bersamaan.
- **Idempotensi & Reservasi Waktu:** Setiap sesi checkout dilindungi `checkout_key` unik dengan batas waktu pembayaran **15 menit** sebelum kuota otomatis dilepas kembali.
- **Kode Promo & Diskon:** Validasi kupon diskon (persentase maupun nominal tetap) secara interaktif sebelum checkout.

### 2. Pembayaran & Penerbitan E-Ticket
- **Simulasi Lokal & Integrasi Xendit Sandbox:** Mendukung penyelesaian pembayaran instan untuk pengujian lokal maupun pembuatan tagihan (*invoice*) melalui API Xendit Sandbox beserta *webhook callback*.
- **E-Ticket PDF & Email Otomatis:** Menerbitkan kode tiket UUID unik pasca-pembayaran, lengkap dengan unduhan E-Ticket PDF (`DomPDF`), pengiriman ke email pembeli, serta tampilan *Mobile QR Pass*.
- **Manajemen Riwayat Tiket:** Halaman *"My Tickets"* dilengkapi pengelompokan tab **Semua Pesanan**, **Tiket Aktif**, dan **Riwayat Tiket Lalu**.

### 3. Gate Check-In & Scanner (*Hybrid Online/Offline*)
- **Multi-Input Scanner:** Mendukung pemindaian melalui kamera ponsel/webcam (`jsQR`), unggah gambar QR, input kode manual, hingga alat *barcode/PDA laser scanner* fisik.
- **Verifikasi Kriptografis Anti-Pemalsuan:** Setiap QR tiket dilengkapi tanda tangan digital **HMAC-SHA256** (`UUID.Signature`) untuk mencegah pemalsuan tiket.
- **Mode Offline & Auto-Sync 2 Arah:** Panitia dapat mengunduh *manifest* tiket ke penyimpanan perangkat untuk memindai tanpa koneksi internet, lalu menyinkronkan antrean *check-in* secara otomatis antar-perangkat saat kembali *online*.
- **Pengaman Operasional:** Dilengkapi pengatur waktu buka gate (*start-time window lock*), modal konfirmasi 2 langkah pada aksi manual, serta opsi penyimpanan riwayat scan lokal.

### 4. Panel Admin & Keamanan Sistem
- **Dashboard & Manajemen Acara:** Statistik penjualan dan kehadiran gate, CRUD event dan kategori tiket (dengan proteksi kuota tiket yang sudah terjual), serta CRUD kode diskon.
- **Manajemen Peran & Reset Data Aman:** Pengaturan hak akses (`admin`, `gate_staff`, `customer`), ekspor data pelanggan ke CSV, serta fitur *Reset Data Pembeli* berpengaman ganda tanpa merusak data master konser.
- **Dwibahasa & Keamanan Ketat:** Mendukung Bahasa Indonesia dan Inggris (`ID` / `EN`) secara penuh, dilengkapi *Content Security Policy* (CSP) ketat, proteksi CSRF, dan *rate limiting*.

---

## Teknologi yang Digunakan

| Lapisan | Teknologi |
| :--- | :--- |
| **Backend** | PHP 8.3+, Laravel 13 |
| **Database** | PostgreSQL (Transaksi & Row Locking) |
| **Frontend** | Blade, Tailwind CSS 4, Vanilla JavaScript, Vite |
| **Dokumen & QR** | `barryvdh/laravel-dompdf`, `chillerlan/php-qrcode`, `jsQR` (Self-hosted) |
| **Payment Gateway** | Xendit Invoice API (Sandbox) & Local Instant Simulator |
| **Pengujian** | PHPUnit (83 Feature & Unit Tests, 381 Assertions) |

---

## Menjalankan Secara Lokal (*Quick Start*)

Pastikan **PHP 8.3+**, **Composer**, **Node.js/npm**, dan **PostgreSQL** sudah terpasang dan aktif di komputer lokal.

```powershell
# 1. Pasang dependensi PHP dan frontend
composer install
npm install

# 2. Salin konfigurasi environment dan buat app key
Copy-Item .env.example .env
php artisan key:generate

# 3. Sesuaikan koneksi PostgreSQL di .env, lalu buat database lokal & test
php scripts/create-local-databases.php

# 4. Jalankan migrasi skema dan data awal (seeder)
php artisan migrate --seed

# 5. Bangun aset produksi frontend dan jalankan server
npm run build
php artisan serve
```

> **Catatan Kredensial Demo:** Saat `migrate --seed` dijalankan, sistem membuat akun demo (`admin` dan `customer`) dengan kata sandi acak yang tersimpan secara lokal di `storage/app/private/demo-accounts.json`.

---

## Pengujian Otomatis

Pastikan `.env.testing` mengarah ke database pengujian terpisah (`ticketing_test`), lalu jalankan:

```powershell
php artisan test
```

Status pengujian saat ini: **83 tes lulus (381 assertions)** — mencakup alur katalog, penguncian stok checkout, diskon, pembayaran, pembuatan PDF, otorisasi peran, hingga pemindaian gate.

---

## Dokumentasi Teknis Lengkap (`docs/`)

Penjelasan mendalam mengenai cara kerja kode, arsitektur database, alur transaksi, dan mekanisme kerja tiap fitur tersedia di dalam folder **[`docs/`](docs/README.md)**:

1. **[Indeks Dokumentasi](docs/README.md)** — Panduan pengantar dan daftar isi dokumentasi.
2. **[Arsitektur & Struktur Kode](docs/01-arsitektur-dan-struktur-kode.md)** — Peta direktori, alur *request-response*, dan relasi tabel database.
3. **[Alur Checkout, Penguncian Stok & Pembayaran](docs/02-alur-checkout-stok-dan-pembayaran.md)** — Bedah cara kerja `BookingService`, reservasi 15 menit, validasi diskon, Xendit, dan penerbitan E-Ticket PDF.
4. **[Sistem Gate Scanner & Sinkronisasi Offline](docs/03-sistem-gate-scanner-dan-offline-sync.md)** — Cara kerja kamera/PDA scanner, tanda tangan HMAC-SHA256, *offline manifest*, dan *2-way sync*.
5. **[Frontend, Dwibahasa, Keamanan & Operasional](docs/04-frontend-dwibahasa-dan-keamanan.md)** — Struktur kamus bahasa `lang/`, aturan CSP, manajemen admin, dan panduan *troubleshooting* lokal.

---

## Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).
