<?php

namespace Database\Seeders;

use App\Models\{User, Category, Event};
use Illuminate\Support\Facades\{Hash, Storage};
use Illuminate\Support\Str;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!app()->environment('local', 'testing')) { throw new \RuntimeException('Demo seeding is only allowed locally.'); }
        $credentials = [];
        foreach (['admin' => 'Admin The Get To', 'customer' => 'Teman Konser'] as $role => $name) {
            $email = $role.'@thegetto.test';
            if (!User::where('email', $email)->exists()) {
                $password = Str::password(18);
                $user = new User(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
                $user->role = $role; $user->save();
                $credentials[$role] = ['email' => $email, 'password' => $password];
            }
        }
        if ($credentials) { Storage::disk('local')->put('demo-accounts.json', json_encode($credentials, JSON_PRETTY_PRINT)); }
        $category = Category::firstOrCreate(['slug' => 'music'], ['name' => 'Musik']);
        $events = [
            ['Blue Hour Festival', 'blue-hour-festival', 'Jakarta', 'Senayan Park', 'Jl. Gerbang Pemuda No. 3', 21, 275000, 'poster-blue.svg', 'Satu langit, banyak suara. Rayakan musik independen di antara senja dan cahaya kota.'],
            ['Sore di Selatan', 'sore-di-selatan', 'Jakarta', 'M Bloc Live House', 'Jl. Panglima Polim No. 37', 28, 150000, 'poster-sore.svg', 'Sore yang pelan, lagu yang dekat. Temukan ruang untuk bernyanyi bersama.'],
            ['Afterglow Sessions', 'afterglow-sessions', 'Bandung', 'Lapang Suara', 'Jl. Braga No. 18', 35, 200000, 'poster-afterglow.svg', 'Selepas matahari turun, suara-suara baru mengambil alih panggung.'],
            ['Ruang Suara', 'ruang-suara', 'Yogyakarta', 'Taman Budaya', 'Jl. Sriwedani No. 1', 42, 125000, 'poster-ruang.svg', 'Lebih dekat dengan musik. Sebuah malam akustik untuk cerita yang ingin didengar.'],
            ['Midnight Frequency', 'midnight-frequency', 'Surabaya', 'Eastside Hall', 'Jl. Pemuda No. 12', 49, 250000, 'poster-midnight.svg', 'Temukan frekuensimu di tengah ritme elektronik dan instalasi cahaya.'],
            ['Sunday, Unplugged', 'sunday-unplugged', 'Bandung', 'Kebun Kota', 'Jl. Ciumbuleuit No. 8', 56, 175000, 'poster-sunday.svg', 'Musik hangat, udara terbuka, dan hari Minggu yang ingin kamu ulang.'],
        ];
        foreach ($events as $index => [$title, $slug, $city, $venue, $address, $days, $price, $poster, $summary]) {
            $event = Event::firstOrCreate(['slug' => $slug], ['category_id' => $category->id, 'title' => $title, 'summary' => $summary, 'description' => $summary."\n\nNikmati pertunjukan musik dalam suasana yang hangat bersama teman-teman sefrekuensi. Area festival dilengkapi tempat istirahat dan pilihan makanan.\n\nPintu dibuka pukul 16.00 WIB. Pertunjukan dimulai pukul 17.00 WIB. Tunjukkan tiket pada akunmu saat masuk dan ikuti petunjuk petugas.\n\nEvent, jadwal, dan penampil pada halaman demo ini bersifat fiktif untuk keperluan portofolio.", 'banner' => 'images/'.$poster, 'venue' => $venue, 'city' => $city, 'address' => $address, 'start_time' => now()->addDays($days)->setTime(17,0), 'end_time' => now()->addDays($days)->setTime(22,0), 'status' => 'published', 'featured' => $index === 0]);
            foreach (['Regular' => [0, 200, 'Akses area festival dan seluruh pertunjukan.'], 'VIP' => [200000, 50, 'Area depan panggung dan jalur masuk khusus.']] as $name => [$extra, $capacity, $description]) {
                $event->ticketCategories()->firstOrCreate(['name' => $name], ['price' => $price + $extra, 'capacity' => $capacity, 'stock' => $capacity, 'description' => $description, 'max_per_order' => 6, 'active' => true]);
            }
        }
    }
}
