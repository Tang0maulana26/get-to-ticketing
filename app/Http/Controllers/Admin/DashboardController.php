<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin overview: headline numbers, recent orders, simple customer list, and CSV export.
 */
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

    public function exportCustomersCsv(): StreamedResponse
    {
        $customers = User::where('role', 'customer')
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($query) => $query->where('status', 'paid')], 'total')
            ->latest()
            ->get();

        $filename = 'data-pembeli-' . now()->format('Y-m-d') . '.csv';

        $callback = function () use ($customers) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Microsoft Excel opens it cleanly
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'ID',
                'Nama',
                'Username',
                'Email',
                'Peran',
                'Jumlah Pesanan',
                'Total Pembelian Lunas (Rp)',
                'Tanggal Bergabung',
            ]);

            foreach ($customers as $c) {
                fputcsv($handle, [
                    $c->id,
                    $c->name,
                    $c->username ?? '-',
                    $c->email,
                    $c->role,
                    $c->orders_count,
                    $c->spent ?? 0,
                    $c->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
