<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'eventCount' => Event::count(),
            'orderCount' => Order::count(),
            'revenue' => Order::where('status', 'paid')->sum('total'),
            'customerCount' => User::where('role', 'customer')->count(),
            'orders' => Order::with(['user', 'items'])->latest()->paginate(10),
        ]);
    }

    public function customers()
    {
        return view('admin.customers', [
            'customers' => User::where('role', 'customer')
                ->withCount('orders')
                ->withSum(['orders as spent' => fn ($query) => $query->where('status', 'paid')], 'total')
                ->latest()
                ->paginate(20),
        ]);
    }
}
