<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiscountRequest;
use App\Models\Discount;
use App\Models\Event;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $query = Discount::query()->with('event')->latest();

        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', $search)
                  ->orWhere('name', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('active', false);
            }
        }

        return view('admin.discounts.index', [
            'discounts' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.discounts.form', [
            'discount' => new Discount(['active' => true, 'type' => 'percent', 'value' => 10]),
            'events' => Event::where('status', 'published')->orderBy('title')->get(),
            'isEdit' => false,
        ]);
    }

    public function store(DiscountRequest $request)
    {
        Discount::create($request->validated());

        return to_route('admin.discounts.index')
            ->with('success', 'Kode diskon berhasil dibuat.');
    }

    public function edit(Discount $discount)
    {
        return view('admin.discounts.form', [
            'discount' => $discount,
            'events' => Event::where('status', 'published')->orderBy('title')->get(),
            'isEdit' => true,
        ]);
    }

    public function update(DiscountRequest $request, Discount $discount)
    {
        $discount->update($request->validated());

        return to_route('admin.discounts.index')
            ->with('success', 'Kode diskon berhasil diperbarui.');
    }

    public function destroy(Discount $discount)
    {
        if ($discount->orders()->exists()) {
            $discount->update(['active' => false]);
            return to_route('admin.discounts.index')
                ->with('success', 'Kode diskon telah digunakan oleh pesanan, status dinonaktifkan.');
        }

        $discount->delete();

        return to_route('admin.discounts.index')
            ->with('success', 'Kode diskon berhasil dihapus.');
    }
}

