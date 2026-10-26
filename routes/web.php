<?php

use App\Http\Controllers\{AuthController, CatalogController, OrderController};
use App\Http\Controllers\Admin\{DashboardController, EventController, TicketCategoryController};
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('catalog');
Route::get('/events/{event:slug}', [CatalogController::class, 'show'])->name('events.show');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/events/{event}/checkout', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('checkout');
    Route::get('/my-tickets', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('throttle:10,1')->name('orders.cancel');
    Route::post('/orders/{order}/demo-payment', [OrderController::class, 'pay'])->middleware('throttle:10,1')->name('orders.pay');
});
Route::prefix('admin')->name('admin.')->middleware(['auth', AdminOnly::class])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/customers', [DashboardController::class, 'customers'])->name('customers');
    Route::resource('events', EventController::class);
    Route::post('/events/{event}/ticket-categories', [TicketCategoryController::class, 'store'])->name('tickets.store');
    Route::put('/events/{event}/ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'update'])->name('tickets.update');
    Route::delete('/events/{event}/ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'destroy'])->name('tickets.destroy');
});
