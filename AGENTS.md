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
- Hasil pengujian terakhir yang tercatat: 23 tes lulus, 97 assertion (`storage/logs/test-results.json`). Itu adalah hasil saat dicatat, bukan jaminan bahwa perubahan berikutnya sudah lolos.
- Belum siap produksi: pembayaran masih demo, seat map/QR check-in belum dibuat, dukungan film belum dibuat, pemeriksaan browser/admin dan responsif belum lengkap, dokumentasi setup dan `.env.example` perlu diselaraskan, dan deployment/cloud database belum dikerjakan.

## Aturan implementasi

- Ikuti pola Laravel: route tipis, Form Request untuk validasi, policy/middleware untuk otorisasi, dan service untuk aturan bisnis yang dipakai lintas controller. Pertahankan nama dan struktur kode yang konsisten.
- Setiap checkout, perubahan stok, pembatalan, dan penyelesaian pembayaran harus aman terhadap permintaan bersamaan. Gunakan transaksi database dan penguncian baris yang relevan; jangan mengurangi stok hanya di frontend.
- Simpan harga sebagai bilangan bulat rupiah. Pertahankan snapshot harga/nama item pesanan agar riwayat tidak berubah ketika admin mengedit kategori tiket. Lindungi stok yang sudah terjual saat admin mengubah kapasitas atau menghapus tiket.
- Jenis acara dibuat modular melalui kategori dan field event yang umum. Tambahkan detail khusus jenis acara hanya saat ada kebutuhan nyata; jangan mewajibkan atribut film pada event konser.
- Gunakan komponen Blade yang dapat dipakai ulang untuk elemen visual berulang. Tampilkan pesan validasi dan status pesanan yang jelas. Jaga aksesibilitas dasar, responsivitas, dan performa; hindari animasi atau dependensi berat tanpa alasan.
- Pertahankan escaping Blade, CSRF, pembatasan request sensitif, pemeriksaan hak akses, validasi unggahan, dan security headers. Jangan menampilkan kredensial atau mengaktifkan pembayaran demo pada produksi.

## Database dan data lokal

- PostgreSQL lokal saat ini memakai `127.0.0.1:5433`; konfigurasi aplikasi ada di `.env`. Data cluster lokal berada di `storage/app/private/postgres/`. Data PostgreSQL bukan file SQL biasa: jangan hapus, pindah, atau edit folder cluster secara manual.
- `.env`, `.env.testing`, kredensial demo, `vendor`, `node_modules`, dan folder data PostgreSQL diabaikan oleh Git. Jangan mencetak rahasia atau memasukkannya ke commit. Untuk contoh konfigurasi publik, perbarui `.env.example` tanpa nilai rahasia.
- Ubah skema melalui migration baru dan verifikasi dampaknya pada data lama. Jangan menjalankan `migrate:fresh`, `db:wipe`, reseed destruktif, atau mengganti database aktif tanpa instruksi eksplisit dari pengguna. Gunakan database pengujian terpisah.
- Jika nanti pindah ke PostgreSQL cloud, siapkan backup/restore atau dump/import data, lalu ubah host, port, database, pengguna, sandi, dan SSL di `.env` penyebaran. Migrasi ke cloud tidak otomatis menghapus data lokal.

## Verifikasi dan pekerjaan berikutnya

- Untuk perubahan backend yang memengaruhi perilaku, jalankan tes yang relevan (`php artisan test`) dan tambahkan tes untuk risiko nyata, terutama otorisasi, checkout, dan stok. Untuk perubahan frontend, jalankan `npm run build` dan periksa alur di browser bila memungkinkan. Jangan menyatakan fitur selesai hanya karena build berhasil.
- Prioritas dekat: periksa alur pengunjung dan admin di browser, selaraskan README dan `.env.example` dengan PostgreSQL/Herd, lalu revisi UI agar lebih khas sesuai arahan pengguna. Sebelum deployment, rencanakan pembayaran nyata, penyimpanan berkas, keamanan operasional, dan pemindahan data PostgreSQL dari folder OneDrive ke lokasi server yang tepat.
- Catat fitur yang belum ada secara jujur; jangan membuat akun, akses admin, URL deployment, atau status pengujian yang tidak terverifikasi.
