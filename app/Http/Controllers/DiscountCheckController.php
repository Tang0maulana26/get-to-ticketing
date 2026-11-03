<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountCheckController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'integer', 'min:0'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $subtotal = (int) $validated['subtotal'];
        $eventId = $validated['event_id'] ?? null;

        $discount = Discount::where('code', $code)->first();
        if (! $discount) {
            return response()->json([
                'ok' => false,
                'message' => 'Kode diskon tidak ditemukan.',
            ], 422);
        }

        $reason = null;
        if (! $discount->isApplicable($subtotal, $reason, $eventId)) {
            return response()->json([
                'ok' => false,
                'message' => $reason ?? 'Kode diskon tidak dapat digunakan.',
            ], 422);
        }

        $discountAmount = $discount->calculateDiscount($subtotal);
        $finalTotal = max(0, $subtotal - $discountAmount);

        return response()->json([
            'ok' => true,
            'code' => $discount->code,
            'name' => $discount->name,
            'type' => $discount->type,
            'value' => $discount->value,
            'discount_amount' => $discountAmount,
            'final_total' => $finalTotal,
            'message' => 'Kode diskon ' . $discount->code . ' berhasil digunakan!',
        ]);
    }
}

