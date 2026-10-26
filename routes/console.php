<?php

use App\Services\BookingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('orders:expire', function (BookingService $bookings) { $this->info('Expired reservations processed: '.$bookings->expire()); })->purpose('Release expired ticket reservations');
Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
