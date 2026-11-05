# 4. Frontend, Dwibahasa, Keamanan & Operasional

Dokumen ini menjelaskan bagaimana tampilan antarmuka dibangun, bagaimana sistem terjemahan 2 bahasa (Indonesia & Inggris) bekerja di Blade maupun JavaScript, bagaimana aturan keamanan diterapkan, serta panduan operasional pengembangan lokal.

---

## 4.1 Sistem Dwibahasa (Indonesia & Inggris)

Seluruh teks antarmuka pengunjung, panitia gate, dan admin mendukung **Bahasa Indonesia (`id`)** dan **Bahasa Inggris (`en`)**.

### 1. Bagaimana Pergantian Bahasa Bekerja?
- Saat pengguna mengklik tombol **`ID` / `EN`** di navbar atas (`resources/views/components/layout.blade.php`), request dikirim ke `LocaleController::switch`.
- Controller menyimpan pilihan bahasa ke dalam `session('locale')`.
- Pada setiap *request*, middleware `app/Http/Middleware/SetLocale.php` membaca session tersebut dan memanggil `App::setLocale($locale)`.

### 2. Struktur Kamus Bahasa (`lang/id.json` & `lang/en.json`)
Karena format JSON standar (RFC 8259) tidak mengizinkan komentar mentah `//` di luar tanda kutip (yang akan membuat fungsi `json_decode()` Laravel error), kedua file kamus diorganisasikan ke dalam **8 modul terstruktur** menggunakan *comment key* yang valid JSON dan diurutkan secara alfabetis (`A–Z`) di setiap bagiannya:

1. `"// --- 1. COMMON & NAVIGATION ---"`: Navbar, footer, tombol aksi umum, badge status.
2. `"// --- 2. CATALOG & BOOKING PANEL ---"`: Katalog konser, filter, detail event, stepper tiket, promo diskon.
3. `"// --- 3. CHECKOUT, ORDERS & TICKETS ---"`: Checkout, pembayaran, tab *My Tickets*, Mobile QR Pass, email.
4. `"// --- 4. GATE SCANNER & CHECK-IN ---"`: Seluruh teks operasional Gate Scanner & modal konfirmasi.
5. `"// --- 5. AUTHENTICATION & RECOVERY ---"`: Login, register, lupa kata sandi, reset kata sandi.
6. `"// --- 6. USER PROFILE & SETTINGS ---"`: Edit profil, ubah sandi, hapus akun pribadi.
7. `"// --- 7. ADMIN DASHBOARD & EVENTS ---"`: Statistik dashboard, CRUD event, kategori tiket, CRUD diskon.
8. `"// --- 8. ADMIN USERS, CUSTOMERS & RESET ---"`: Direktori pelanggan, manajemen role, reset data pembeli.

> **Tips Penting Saat Menambah Teks Baru:**
> - Di file Blade atau Controller PHP, bungkus teks dengan `{{ __('Teks Bahasa Inggris') }}`.
> - Jika satu kata bahasa Inggris memiliki arti berbeda di konteks lain, buat kalimat/key yang lebih spesifik di Blade (misal `__('Book ticket')` vs `__('Order details')`), jangan membuat key yang duplikat di dalam satu file JSON.

### 3. Terjemahan pada JavaScript Interaktif (`data-i18n`)
File JavaScript eksternal (`resources/js/app.js` dan `public/gate-scanner.js`) tidak bisa memanggil fungsi PHP `__()` secara langsung. Solusi yang digunakan di proyek ini adalah:
1. Di file Blade (seperti `catalog/show.blade.php` dan `gate/scan.blade.php`), kita menyiapkan array PHP berisi kamus kata yang dibutuhkan JS, lalu menyematkannya ke atribut HTML `data-i18n="{{ json_encode($i18n) }}"`.
2. Di JavaScript, skrip membaca `JSON.parse(element.dataset.i18n)` melalui fungsi pembantu `t(key, fallback, replacements)`. Dengan demikian, seluruh pesan *alert*, status scan, dan rincian potongan diskon otomatis mengikuti bahasa yang sedang aktif.

---

## 4.2 Komponen Frontend & Aturan Penulisan Blade/CSS

1. **Tema Light & Dark Mode Tanpa Kedip (`public/theme.js`)**
   - Secara bawaan aplikasi tampil dalam *Light Mode* (putih hangat dan biru muda). Jika pengguna menekan tombol tema di navbar, `theme.js` menyimpan preferensi ke `localStorage` dan memasang class `.dark` pada elemen `<html>` sebelum halaman dirender.
2. **Interaktivitas Panel Pemesanan (`resources/js/app.js`)**
   - Mengatur tombol stepper `+` / `−` kuantitas tiket, menghitung total harga secara langsung, membatasi kuota maksimal 20 tiket per pesanan, serta mengelola kotak promo diskon (`#apply-discount-btn`, *loading spinner*, tombol hapus `×` `#discount-clear-btn`, dan *badge feedback*).
3. **Aturan CSS Inline di Blade (Menghindari Error Linter Editor)**
   - Jangan pernah menulis ekspresi Blade `{{ ... }}` di tengah properti `style="..."` seperti `style="display: {{ $cond ? 'block' : 'none' }};"` karena parser CSS di VS Code akan menganggapnya syntax error.
   - Gunakan direktif `@style(['display: none' => !$cond])` atau `@if($cond) style="display: block;" @else style="display: none;" @endif`.

---

## 4.3 Fitur Panel Admin & Pengaman Data

1. **Dashboard Admin (`resources/views/admin/dashboard.blade.php`)**
   - Menampilkan 5 kartu metrik sejajar (`stat-grid`): *Total events*, *Total orders*, *Paid order revenue*, *Customers*, dan *Gate attendance* (rasio tiket yang sudah check-in dibanding total tiket valid).
2. **Perlindungan Stok Saat Admin Mengedit Tiket (`TicketCategoryController`)**
   - Jika suatu kategori tiket sudah terjual sebanyak 15 tiket dari kapasitas 100 (sisa stok 85), lalu Admin mengubah kapasitas total menjadi 50, sistem menghitung selisih tiket yang sudah terjual/dipesan dan menyesuaikan `stock` baru secara proporsional. Admin juga **dilarang** menurunkan kapasitas di bawah jumlah tiket yang sudah terjual.
   - Jika kategori tiket yang sudah pernah dibeli dihapus oleh Admin, sistem tidak akan merusak riwayat pesanan lama.
3. **Reset Data Pembeli Berpengaman Ganda (`BuyerResetController`)**
   - Fitur ini dirancang untuk mengosongkan seluruh riwayat transaksi (`orders`, `order_items`, `tickets`), mereset kuota terpakai diskon (`used_count = 0`), dan mengembalikan stok tiket (`stock = capacity`) ke keadaan awal **tanpa menghapus data konser, galeri poster, maupun akun pengguna**.
   - Untuk mencegah ketidaksengajaan, Admin wajib melewati 2 lapis verifikasi: memasukkan **Kata Sandi Admin Aktif** dan mengetik frasa konfirmasi persis **`RESET-DATA-PEMBELI`**.

---

## 4.4 Keamanan & Header HTTP (`SecurityHeaders.php`)

Setiap respons dari aplikasi dilindungi oleh middleware `app/Http/Middleware/SecurityHeaders.php`:
- **`Content-Security-Policy` (CSP):**
  - `script-src 'self' 'unsafe-inline'`: Hanya mengizinkan skrip dari domain aplikasi sendiri. Oleh sebab itu, library seperti `jsqr.min.js` di-host secara lokal di folder `public/`, bukan dari CDN luar.
  - `media-src 'self' blob:`: Mengizinkan stream kamera lokal pada halaman Gate Scanner.
- **`Permissions-Policy: camera=(self), microphone=(), geolocation=()`:**
  - Membuka akses perangkat keras kamera **hanya** untuk domain aplikasi sendiri dan menutup akses mikrofon serta lokasi.
- **`X-Frame-Options: DENY` & `X-Content-Type-Options: nosniff`:**
  - Mencegah serangan *clickjacking* dan *MIME-type sniffing*.

---

## 4.5 Panduan Pengembangan Lokal & Troubleshooting

### 1. Mengapa Website Error / Lambat Saat Menjalankan `npm run dev` + `php artisan serve` + Laravel Herd Sekaligus?
- **Penyebab:**
  - Ketika kamu menjalankan `npm run dev`, Vite membuat file penanda bernama **`public/hot`** yang memaksa browser memuat CSS & JS dari server dev Vite (`localhost:5173`).
  - Jika di saat yang sama Laravel Herd dan `php artisan serve` berjalan berbarengan, atau jika terminal `npm run dev` tertutup sedangkan file `public/hot` masih tertinggal, halaman web akan gagal memuat CSS/JS atau mengalami bentrok port.
- **Solusi Terbaik Sehari-hari:**
  - Gunakan cukup **satu** server PHP saja (pilih `php artisan serve` di terminal **atau** gunakan domain `.test` dari Laravel Herd, jangan keduanya sekaligus).
  - Untuk tampilan yang stabil dan ringan, jalankan **`npm run build`**. Perintah ini menghasilkan aset matang di `public/build/` dan menghapus file `public/hot`, sehingga kamu tidak perlu menyalakan `npm run dev` terus-menerus di latar belakang.

### 2. Konfigurasi Database PostgreSQL Lokal
- Proyek ini pada lingkungan pengembangan lokal menggunakan PostgreSQL di port **`5433`** (atau `5432` sesuai instalasi di `.env`), dengan database utama `ticketing_local` dan database pengujian `ticketing_test`.
- Folder cluster data lokal berada di `storage/app/private/postgres/`. **Jangan pernah menghapus, memindahkan, atau mengedit isi folder cluster tersebut secara manual** agar database tidak korup.
- Selalu gunakan database terpisah (`ticketing_test` di `.env.testing`) saat menjalankan `php artisan test` agar data konser dan transaksi di `ticketing_local` tetap aman.

