<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Order;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $user->name = $validated['name'];
        $user->username = strtolower(trim($validated['username']));
        $user->email = strtolower(trim($validated['email']));

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profil akun kamu berhasil diperbarui.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'delete_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Safety guard: Cannot delete the last admin
        if ($user->role === 'admin' && \App\Models\User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['delete_password' => 'Tidak dapat menghapus akun admin terakhir di sistem.']);
        }

        $userId = $user->id;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($userId) {
            $user = User::findOrFail($userId);
            $orders = Order::where('user_id', $userId)->with('items')->lockForUpdate()->get();

            foreach ($orders as $order) {
                if ($order->status === 'pending') {
                    app(BookingService::class)->release($order, 'cancelled');
                } else {
                    if ($order->status === 'paid') {
                        foreach ($order->items as $item) {
                            TicketCategory::whereKey($item->ticket_category_id)
                                ->lockForUpdate()
                                ->first()
                                ?->increment('stock', $item->quantity);
                        }
                    }

                    if ($order->discount_id && $order->status === 'paid') {
                        $discount = Discount::whereKey($order->discount_id)->lockForUpdate()->first();
                        if ($discount && $discount->used_count > 0) {
                            $discount->decrement('used_count');
                        }
                    }
                }

                $itemIds = $order->items->pluck('id');
                if ($itemIds->isNotEmpty()) {
                    \App\Models\Ticket::whereIn('order_item_id', $itemIds)->delete();
                    \App\Models\OrderItem::whereIn('id', $itemIds)->delete();
                }

                $order->delete();
            }

            $user->delete();
        }, 3);

        return to_route('catalog')->with('success', 'Akun Anda dan seluruh data pribadi terkait telah berhasil dihapus.');
    }
}
