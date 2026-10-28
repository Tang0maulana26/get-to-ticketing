<?php

use App\Services\BookingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('orders:expire', function (BookingService $bookings) {
    $this->info('Expired reservations processed: '.$bookings->expire());
})->purpose('Release expired ticket reservations');

// Requires `php artisan schedule:work` (local) or a cron entry (server).
Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
