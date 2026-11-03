<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket & Bukti Pembayaran</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            color: #1e293b;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 30px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            color: #94a3b8;
        }
        .content {
            padding: 24px;
        }
        .badge-success {
            display: inline-block;
            background-color: #d1fae5;
            color: #065f46;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .summary-card {
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 16px;
            margin-bottom: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .summary-row:last-child {
            margin-bottom: 0;
        }
        .summary-label {
            color: #64748b;
        }
        .summary-value {
            font-weight: 600;
            color: #0f172a;
        }
        .event-card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            background-color: #ffffff;
        }
        .event-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        .event-detail {
            font-size: 12px;
            color: #475569;
            margin-bottom: 4px;
        }
        .ticket-list {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed #cbd5e1;
        }
        .ticket-item {
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
        }
        .ticket-code {
            font-family: monospace;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            color: #0284c7;
        }
        .pdf-notice {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 14px 16px;
            border-radius: 4px;
            font-size: 12px;
            color: #1e40af;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .action-button {
            display: block;
            text-align: center;
            background-color: #0284c7;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 16px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>GET TO TICKETING</h1>
            <p>Konfirmasi Pembayaran & E-Ticket Resmi</p>
        </div>

        <div class="content">
            <span class="badge-success">&#10003; Pembayaran Berhasil</span>
            <p style="font-size: 14px; margin-top: 0;">
                Halo <strong>{{ $order->customer_name ?? $order->user->name }}</strong>, pembayaran untuk pesanan Anda telah kami terima dan tiket Anda resmi diterbitkan.
            </p>

            <div class="summary-card">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="color: #64748b; padding-bottom: 6px;">No. Pesanan</td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a; padding-bottom: 6px;">#{{ $order->number }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding-bottom: 6px;">Metode Pembayaran</td>
                        <td style="text-align: right; font-weight: 600; color: #0f172a; padding-bottom: 6px;">{{ strtoupper($order->payment_method ?? 'VERIFIED') }} {{ $order->payment_channel ? '('.$order->payment_channel.')' : '' }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding-bottom: 6px;">Waktu Pembayaran</td>
                        <td style="text-align: right; font-weight: 600; color: #0f172a; padding-bottom: 6px;">{{ $order->paid_at ? $order->paid_at->format('d M Y, H:i') : now()->format('d M Y, H:i') }} WIB</td>
                    </tr>
                    @if($order->discount_amount > 0)
                        <tr>
                            <td style="color: #64748b; padding-bottom: 6px;">Diskon ({{ $order->discount_code }})</td>
                            <td style="text-align: right; font-weight: 600; color: #16a34a; padding-bottom: 6px;">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr style="border-top: 1px solid #e2e8f0;">
                        <td style="font-weight: 700; color: #0f172a; padding-top: 8px;">Total Pembayaran</td>
                        <td style="text-align: right; font-weight: 800; color: #0284c7; font-size: 15px; padding-top: 8px;">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            @foreach($order->items as $item)
                <div class="event-card">
                    <h3 class="event-title">{{ $item->event_title }}</h3>
                    <div class="event-detail">
                        <strong>Jadwal:</strong> {{ $item->event_start_time ? $item->event_start_time->format('l, d M Y - H:i') : '-' }} WIB
                    </div>
                    <div class="event-detail">
                        <strong>Lokasi:</strong> {{ $item->venue ?? 'Main Stage' }}
                    </div>
                    <div class="event-detail">
                        <strong>Kategori:</strong> {{ $item->ticket_category_name }} &bull; {{ $item->quantity }} Tiket
                    </div>

                    <div class="ticket-list">
                        <div style="font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">Kode Tiket Masuk:</div>
                        @foreach($item->tickets as $ticket)
                            <div class="ticket-item">
                                <span>Tiket #{{ $loop->iteration }}</span>
                                <span class="ticket-code">{{ $ticket->code }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="pdf-notice">
                <strong>&#128206; Lampiran E-Ticket (PDF):</strong><br>
                File E-Ticket lengkap dengan <strong>QR Code resmi</strong> telah dilampirkan pada email ini. Silakan unduh dan simpan berkas PDF tersebut untuk ditunjukkan saat proses check-in di gate masuk venue konser.
            </div>

            <a href="{{ route('orders.show', $order) }}" class="action-button">
                Buka & Unduh Tiket di Web
            </a>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Get to Ticketing. All rights reserved.<br>
            Email ini dibuat secara otomatis oleh sistem Get to Ticketing.
        </div>
    </div>
</body>
</html>

