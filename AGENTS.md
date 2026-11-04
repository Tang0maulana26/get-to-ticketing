# Aturan kerja Get to Ticketing

Dokumen ini adalah acuan untuk agen coding di proyek Laravel ini. Jelaskan keputusan dan hasil kepada pemilik proyek dalam bahasa Indonesia yang mudah dipahami. Baca kode yang relevan sebelum mengubahnya; jangan menganggap rencana sebagai fitur yang sudah selesai.

## Tujuan dan cakupan

- Bangun aplikasi ticketing konser yang mudah dipelajari dan layak menjadi portofolio. Struktur kategori dan event harus dapat dipakai ulang untuk jenis acara lain nanti; dukungan film belum menjadi target aktif.
- Stack saat ini: Laravel, Blade, Tailwind CSS melalui Vite/npm, dan PostgreSQL lokal. Laravel Herd dapat menyediakan PHP; server pengembangan dapat dijalankan dengan `php artisan serve`. Node.js dipakai untuk aset frontend, bukan sebagai backend saat ini.
- Tampilan pengunjung: light mode sebagai bawaan, putih dan biru muda, dark mode opsional, sudut tegak yang tetap modern, animasi ringan, alur beli yang jelas. Admin: sederhana dan mudah dioperasikan. Revisi visual yang lebih khas masih diperlukan.
- Jangan memigrasikan backend ke Node.js, menghubungkan Supabase/cloud, menerbitkan situs, atau menambah pembayaran nyata tanpa permintaan pengguna.

## Kondisi proyek saat aturan ini ditulis (26 Oktober 2026)

- Katalog, filter kategori, detail event dengan gambar dan deskripsi, login/register, pemesanan, riwayat tiket, pembayaran demo lokal, serta CRUD event dan kategori tiket admin sudah ada di kode. Admin juga memiliki ringkasan pelanggan sederhana.
- `app/Services/BookingService.php` menangani transaksi pemesanan, penguncian stok, idempotensi, pembatalan/kedaluwarsa, dan penerbitan tiket setelah pembayaran demo. `routes/console.php` menjadwalkan kedaluwarsa pesanan. Jangan menghapus pengaman stok saat mengubah alur checkout.
- Model berada di `app/Models`, validasi di `app/Http/Requests`, otorisasi di `app/Policies` dan middleware, controller di `app/Http/Controllers`, skema di `database/migrations`, antarmuka Blade di `resources/views`, dan aset di `resources/css` serta `resources/js`.
- Gate Check-In & Scanner mendukung kamera HP/laptop, alat PDA scanner fisik, verifikasi HMAC anti-pemalsuan, serta mode offline penuh dengan auto-sync 2 arah antar-perangkat panitia.
- Hasil pengujian terakhir yang tercatat: 81 tes lulus, 371 assertion.

## Aturan implementasi

- Ikuti pola Laravel: route tipis, Form Request untuk validasi, policy/middleware untuk otorisasi, dan service untuk aturan bisnis yang dipakai lintas controller. Pertahankan nama dan struktur kode yang konsisten.
- Setiap checkout, perubahan stok, pembatalan, penyelesaian pembayaran, dan check-in gate harus aman terhadap permintaan bersamaan. Gunakan transaksi database dan penguncian baris (`lockForUpdate`); jangan mengurangi stok atau memvalidasi tiket hanya di frontend.
- Simpan harga sebagai bilangan bulat rupiah. Pertahankan snapshot harga/nama item pesanan agar riwayat tidak berubah ketika admin mengedit kategori tiket. Lindungi stok yang sudah terjual saat admin mengubah kapasitas atau menghapus tiket.
- Jenis acara dibuat modular melalui kategori dan field event yang umum. Tambahkan detail khusus jenis acara hanya saat ada kebutuhan nyata; jangan mewajibkan atribut film pada event konser.
- Gunakan komponen Blade yang dapat dipakai ulang untuk elemen visual berulang. Tampilkan pesan validasi dan status pesanan yang jelas. Jaga aksesibilitas dasar, responsivitas, dan performa; hindari animasi atau dependensi berat tanpa alasan.
- Jangan menaruh ekspresi Blade `{{ ... }}` di tengah nilai properti CSS inline seperti `style="display: {{ ... }};"`. Gunakan direktif Blade `@if / @else` yang membungkus nilai CSS valid utuh, atau direktif `@style([...])` agar bebas dari error parser linter editor.
- Seluruh teks antarmuka wajib mendukung dwibahasa (ID & EN) melalui `{{ __('...') }}` yang diselaraskan di `lang/id.json` dan `lang/en.json`. Untuk komponen interaktif frontend, kirimkan kamus terjemahan melalui atribut `data-i18n` agar pesan status dan alert JavaScript otomatis mengikuti bahasa aktif.
- Pertahankan escaping Blade, CSRF, pembatasan request sensitif, pemeriksaan hak akses, validasi unggahan, dan security headers. Jangan menampilkan kredensial atau mengaktifkan pembayaran demo pada produksi.
- CSP & Hardware Permissions: Jika menggunakan fitur perangkat keras (seperti kamera scanner), pastikan `Permissions-Policy: camera=(self)` dan `media-src 'self' blob:` diizinkan di `SecurityHeaders.php`. Seluruh library operasional (seperti `jsqr.min.js`) wajib di-host lokal di `public/` agar mematuhi aturan CSP `script-src 'self'` dan dapat bekerja 100% offline tanpa internet luar.
- Otorisasi Gate: Konfigurasi sistem gate (saklar buka/tutup sistem dan jendela waktu scan `start_time`) dibatasi hanya untuk `admin`. Peran `gate_staff` dibatasi untuk operasi operasional scan dan sinkronisasi antrean tiket.

## Database dan data lokal

- PostgreSQL lokal saat ini memakai `127.0.0.1:5433`; konfigurasi aplikasi ada di `.env`. Data cluster lokal berada di `storage/app/private/postgres/`. Data PostgreSQL bukan file SQL biasa: jangan hapus, pindah, atau edit folder cluster secara manual.
- `.env`, `.env.testing`, kredensial demo, `vendor`, `node_modules`, dan folder data PostgreSQL diabaikan oleh Git. Jangan mencetak rahasia atau memasukkannya ke commit. Untuk contoh konfigurasi publik, perbarui `.env.example` tanpa nilai rahasia.
- Ubah skema melalui migration baru dan verifikasi dampaknya pada data lama. Jangan menjalankan `migrate:fresh`, `db:wipe`, reseed destruktif, atau mengganti database aktif tanpa instruksi eksplisit dari pengguna. Gunakan database pengujian terpisah.
- Jika nanti pindah ke PostgreSQL cloud, siapkan backup/restore atau dump/import data, lalu ubah host, port, database, pengguna, sandi, dan SSL di `.env` penyebaran. Migrasi ke cloud tidak otomatis menghapus data lokal.

## Verifikasi dan pekerjaan berikutnya

- Untuk perubahan backend yang memengaruhi perilaku, jalankan tes yang relevan (`php artisan test`) dan tambahkan tes untuk risiko nyata, terutama otorisasi, checkout, dan stok. Untuk perubahan frontend, jalankan `npm run build` dan periksa alur di browser bila memungkinkan. Jangan menyatakan fitur selesai hanya karena build berhasil.
- Prioritas dekat: periksa alur pengunjung dan admin di browser, selaraskan README dan `.env.example` dengan PostgreSQL/Herd, lalu revisi UI agar lebih khas sesuai arahan pengguna. Sebelum deployment, rencanakan pembayaran nyata, penyimpanan berkas, keamanan operasional, dan pemindahan data PostgreSQL dari folder OneDrive ke lokasi server yang tepat.
- Catat fitur yang belum ada secara jujur; jangan membuat akun, akses admin, URL deployment, atau status pengujian yang tidak terverifikasi.
