<?php

namespace App\Http\Controllers;

use App\Mail\OrderPaidMail;
use App\Models\Order;
use App\Services\TicketPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class TicketPdfController extends Controller
{
    /**
     * Download the official concert E-Ticket as an A4 PDF with individual QR codes.
     */
    public function download(Order $order, TicketPdfService $pdfService)
    {
        Gate::authorize('view', $order);

        if ($order->status !== 'paid') {
            return redirect()->route('orders.show', $order)
                ->with('error', __('E-Ticket hanya dapat diunduh untuk pesanan yang telah lunas.'));
        }

        $order->loadMissing(['items.tickets', 'user']);

        return $pdfService->generate($order)->download("E-Ticket-{$order->number}.pdf");
    }

    /**
     * Resend the E-Ticket and payment receipt email with attached PDF.
     */
    public function resendEmail(Request $request, Order $order)
    {
        Gate::authorize('view', $order);

        if ($order->status !== 'paid') {
            return back()->with('error', __('Email bukti tiket hanya dapat dikirim untuk pesanan yang telah lunas.'));
        }

        $order->loadMissing(['items.tickets', 'user']);

        $recipientEmail = $order->customer_email ?? $order->user?->email;
        if (! $recipientEmail) {
            return back()->with('error', __('Alamat email penerima tidak ditemukan.'));
        }

        try {
            Mail::to($recipientEmail)->send(new OrderPaidMail($order));

            return back()->with('success', __('Bukti pembayaran dan E-Ticket (PDF) telah dikirim ke email :email.', ['email' => $recipientEmail]));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('Gagal mengirim email. Silakan coba beberapa saat lagi.'));
        }
    }
}

