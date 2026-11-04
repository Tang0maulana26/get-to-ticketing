<?php

// All web routes: public catalog, guest auth, password reset, customer orders, profile, and the admin area.

use App\Http\Controllers\{AuthController, CatalogController, DiscountCheckController, GateCheckInController, LocaleController, OrderController, PasswordResetController, ProfileController, TicketPdfController, XenditPaymentController};
use App\Http\Controllers\Admin\{BuyerResetController, DashboardController, DiscountController, EventController, TicketCategoryController, UserController};
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], '/locale/{lang?}', [LocaleController::class, 'switch'])->name('locale.switch');

// Xendit Webhook Callback (CSRF exempt)
Route::post('/xendit/webhook', [XenditPaymentController::class, 'webhook'])->name('xendit.webhook');

Route::get('/', [CatalogController::class, 'index'])->name('catalog');
Route::get('/events/{event:slug}', [CatalogController::class, 'show'])->name('events.show');
Route::post('/discounts/check', [DiscountCheckController::class, 'check'])->middleware('throttle:30,1')->name('discounts.check');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->middleware('throttle:5,1');

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/events/{event}/checkout', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('checkout');
    Route::get('/my-tickets', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('throttle:10,1')->name('orders.cancel');
    Route::post('/orders/{order}/demo-payment', [OrderController::class, 'pay'])->middleware('throttle:10,1')->name('orders.pay');

    // Xendit Checkout & Status Sync
    Route::post('/orders/{order}/xendit/checkout', [XenditPaymentController::class, 'checkout'])->middleware('throttle:10,1')->name('orders.xendit.checkout');
    Route::get('/orders/{order}/xendit/check', [XenditPaymentController::class, 'check'])->middleware('throttle:20,1')->name('orders.xendit.check');

    // E-Ticket PDF Download & Email Delivery
    Route::get('/orders/{order}/ticket-pdf', [TicketPdfController::class, 'download'])->name('orders.ticket.pdf');
    Route::post('/orders/{order}/resend-email', [TicketPdfController::class, 'resendEmail'])->middleware('throttle:10,1')->name('orders.resend.email');

    // Gate Check-in & Scanner (Admin & Gate Staff)
    Route::get('/gate/scan', [GateCheckInController::class, 'index'])->name('gate.scan');
    Route::post('/gate/check-in', [GateCheckInController::class, 'checkIn'])->name('gate.check-in');
    Route::post('/gate/settings', [GateCheckInController::class, 'updateSettings'])->name('gate.settings');
    Route::get('/gate/manifest/{event}', [GateCheckInController::class, 'manifest'])->name('gate.manifest');
    Route::post('/gate/sync-batch', [GateCheckInController::class, 'syncBatch'])->name('gate.sync-batch');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', AdminOnly::class])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/customers', [DashboardController::class, 'customers'])->name('customers');
    Route::get('/customers/export-csv', [DashboardController::class, 'exportCustomersCsv'])->name('customers.export');

    // Events & Tickets
    Route::resource('events', EventController::class);
    Route::post('/events/{event}/ticket-categories', [TicketCategoryController::class, 'store'])->name('tickets.store');
    Route::put('/events/{event}/ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'update'])->name('tickets.update');
    Route::delete('/events/{event}/ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'destroy'])->name('tickets.destroy');

    // Discounts
    Route::resource('discounts', DiscountController::class)->except(['show']);

    // User & Role Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Safe Buyer Data Reset
    Route::get('/reset-data', [BuyerResetController::class, 'index'])->name('reset.index');
    Route::post('/reset-data/all', [BuyerResetController::class, 'resetAll'])->name('reset.all');
    Route::post('/reset-data/customer/{user}', [BuyerResetController::class, 'resetCustomer'])->name('reset.customer');
});
