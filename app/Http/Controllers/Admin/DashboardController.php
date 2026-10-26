<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Event, Order, User};
use App\Services\BookingService;

class DashboardController extends Controller
{
    public function index(BookingService $bookings)
    {
        $bookings->expire();
        return view('admin.dashboard', ['eventCount' => Event::count(), 'orderCount' => Order::count(), 'revenue' => Order::where('status', 'paid')->sum('total'), 'customerCount' => User::where('role', 'customer')->count(), 'orders' => Order::with(['user', 'items'])->latest()->paginate(10)]);
    }
    public function customers()
    {
        return view('admin.customers', ['customers' => User::where('role', 'customer')->withCount('orders')->withSum(['orders as spent' => fn ($q) => $q->where('status', 'paid')], 'total')->latest()->paginate(20)]);
    }
}
