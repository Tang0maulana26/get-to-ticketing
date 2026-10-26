# Get To Ticketing

Get To Ticketing adalah proyek belajar full-stack untuk membangun aplikasi pemesanan tiket konser. Proyek ini dibuat sebagai latihan memahami alur aplikasi dari tampilan, validasi, database, sampai transaksi pemesanan, sekaligus menjadi bahan portofolio.

Fokus awalnya adalah konser. Struktur event dan kategori dibuat agar dapat digunakan kembali untuk jenis acara lain di masa depan. Aplikasi ini masih dalam tahap belajar dan belum siap digunakan untuk transaksi nyata.

## Tujuan belajar

Lewat proyek ini, saya berlatih untuk:

- Membangun aplikasi dengan PHP dan Laravel.
- Membuat halaman dengan Blade dan styling Tailwind CSS.
- Mengelola data menggunakan PostgreSQL, migration, dan Eloquent ORM.
- Membuat autentikasi, validasi form, dan pembatasan akses admin.
- Menjaga stok tiket saat beberapa pemesanan terjadi bersamaan.
- Menyusun komponen dan alur yang dapat dikembangkan kembali.

## Fitur yang sudah tersedia

- Katalog konser dengan filter kategori.
- Halaman detail event yang memuat poster, deskripsi, lokasi, dan pilihan tiket.
- Registrasi, login, dan riwayat tiket pengguna.
- Pemesanan dengan reservasi stok selama 15 menit.
- Pembayaran demo lokal untuk menyelesaikan pesanan.
- Admin untuk mengelola event dan kategori tiket, termasuk nama kategori seperti Regular atau VIP.
- Data demo event dan poster ilustrasi fiktif.

Pembayaran hanya simulasi. Integrasi payment gateway, denah kursi, QR check-in, dukungan film, dan deployment belum dibuat.

## Teknologi

- PHP 8.3+
- Laravel 13
- PostgreSQL
- Blade
- Tailwind CSS 4
- Vite dan npm

Laravel menangani backend dan halaman server. Node.js/npm hanya digunakan untuk memasang serta membangun aset frontend.

## Menjalankan secara lokal

Pastikan PHP, Composer, Node.js/npm, dan PostgreSQL sudah terpasang dan server PostgreSQL sedang berjalan. Laravel Herd dapat menyediakan PHP di Windows. Perintah `php artisan serve` menjalankan server web pengembangan.

1. Clone repository, lalu buka terminal pada folder proyek.
2. Pasang dependensi dan salin konfigurasi lingkungan:

   ```powershell
   composer install
   Copy-Item .env.example .env
   npm install
   ```

3. Ubah konfigurasi database dalam `.env` menjadi PostgreSQL dan isi koneksi lokalmu:

   ```dotenv
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=ticketing_local
   DB_USERNAME=postgres
   DB_PASSWORD=password-postgres-lokal
   ```

   Sesuaikan port, pengguna, dan sandi dengan instalasi PostgreSQL di komputermu. Jangan commit file `.env`.

4. Buat application key dan database lokal:

   ```powershell
   php artisan key:generate
   php scripts/create-local-databases.php
   ```

   Script database membuat `ticketing_local` dan `ticketing_test` bila belum ada. Pengguna PostgreSQL yang dipakai perlu memiliki izin membuat database.

5. Jalankan migration dan isi data demo:

   ```powershell
   php artisan migrate --seed
   ```

   Akun demo dibuat dengan sandi acak. Kredensialnya disimpan lokal di `storage/app/private/demo-accounts.json`; jangan membagikan atau mengunggah file itu.

6. Buka dua terminal pada folder proyek. Jalankan server Laravel di terminal pertama dan Vite di terminal kedua:

   ```powershell
   php artisan serve
   ```

   ```powershell
   npm run dev
   ```

   Buka alamat yang ditampilkan oleh Artisan, biasanya http://127.0.0.1:8000.

## Menjalankan pengujian

Atur `.env.testing` agar memakai database terpisah `ticketing_test`, lalu jalankan:

```powershell
php artisan test
```

Hasil pengujian terakhir yang tercatat saat dokumentasi ini diperbarui adalah 23 tes lulus dengan 97 assertion. Jalankan ulang tes setelah mengubah kode untuk mengetahui kondisi terbaru.

## Keamanan data lokal

File `.env`, kredensial demo, dan data PostgreSQL lokal tidak seharusnya masuk ke Git. Migration mengubah struktur database; hindari perintah yang menghapus dan membuat ulang database jika masih ada data yang ingin disimpan.

## Catatan pengembangan

Proyek ini masih dikembangkan sebagai sarana belajar. Sebelum dipakai oleh pengguna sungguhan, aplikasi memerlukan pemeriksaan keamanan dan alur secara menyeluruh, payment gateway, pengelolaan data dan gambar untuk server, serta konfigurasi deployment.

## Lisensi

Proyek ini menggunakan lisensi [MIT](LICENSE).
