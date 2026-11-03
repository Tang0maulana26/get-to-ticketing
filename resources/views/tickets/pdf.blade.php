<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-Ticket - {{ $order->number }}</title>
    <style>
        @page {
            margin: 20px;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        body {
            margin: 0;
            padding: 10px;
            color: #1f2937;
            background-color: #f8fafc;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
        }
        .brand-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px;
        }
        .ticket-wrapper {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .ticket-card {
            width: 100%;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            border-collapse: collapse;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .ticket-main {
            padding: 24px;
            vertical-align: top;
            width: 68%;
            border-right: 2px dashed #cbd5e1;
            position: relative;
        }
        .ticket-stub {
            padding: 20px 16px;
            vertical-align: middle;
            text-align: center;
            width: 32%;
            background-color: #f8fafc;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .event-badge {
            display: inline-block;
            background-color: #0284c7;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .event-name {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 14px 0;
            line-height: 1.25;
        }
        .info-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .info-label {
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .info-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            margin-top: 2px;
        }
        .info-sub {
            color: #475569;
            font-size: 11px;
            font-weight: normal;
        }
        .qr-image {
            width: 140px;
            height: 140px;
            margin: 0 auto 10px auto;
            display: block;
            background: #ffffff;
            padding: 4px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .ticket-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #0f172a;
            background: #e2e8f0;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 6px;
        }
        .admit-badge {
            font-size: 11px;
            font-weight: 800;
            color: #059669;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .status-pill {
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            color: #059669;
            background-color: #d1fae5;
            padding: 2px 6px;
            border-radius: 10px;
            text-transform: uppercase;
        }
        .order-meta {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
            font-size: 10px;
            color: #64748b;
        }
        .terms-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .terms-title {
            font-size: 11px;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .terms-list {
            margin: 0;
            padding-left: 18px;
            color: #64748b;
            font-size: 9.5px;
            line-height: 1.5;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="brand-title">GET TO TICKETING</h1>
        <div class="brand-subtitle">Official Electronic Admission Ticket &bull; Invoice #{{ $order->number }}</div>
    </div>

    @php
        $totalTickets = $order->items->flatMap->tickets->count();
        $ticketIndex = 0;
    @endphp

    @foreach($order->items as $item)
        @foreach($item->tickets as $ticket)
            @php $ticketIndex++; @endphp
            <div class="ticket-wrapper">
                <table class="ticket-card" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="ticket-main">
                            <span class="event-badge">{{ $item->ticket_name }} &bull; Ticket {{ $ticketIndex }} of {{ $totalTickets }}</span>
                            <h2 class="event-name">{{ $item->event_title }}</h2>

                            <table class="info-table" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="width: 50%;">
                                        <div class="info-label">Date & Time / Jadwal Acara</div>
                                        <div class="info-value">
                                            @if($item->event_start_time)
                                                {{ $item->event_start_time->format('l, d M Y - H:i') }} WIB
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </td>
                                    <td style="width: 50%;">
                                        <div class="info-label">Venue / Lokasi</div>
                                        <div class="info-value">
                                            {{ $item->venue ?? 'Main Stage' }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 14px;">
                                        <div class="info-label">Ticket Holder / Nama Pemegang</div>
                                        <div class="info-value">{{ $order->customer_name ?? $order->user->name }}</div>
                                        <div class="info-sub">{{ $order->customer_email ?? $order->user->email }}</div>
                                    </td>
                                    <td style="padding-top: 14px;">
                                        <div class="info-label">Category & Price / Kategori & Harga</div>
                                        <div class="info-value">{{ $item->ticket_name }}</div>
                                        <div class="info-sub">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</div>
                                    </td>
                                </tr>
                            </table>

                            <div class="order-meta">
                                Order No: <strong>#{{ $order->number }}</strong> &bull; 
                                Issued: {{ $ticket->created_at ? $ticket->created_at->format('d M Y, H:i') : now()->format('d M Y, H:i') }} WIB &bull; 
                                Payment: {{ strtoupper($order->payment_method ?? 'VERIFIED') }}
                            </div>
                        </td>

                        <td class="ticket-stub">
                            <div class="admit-badge">ADMIT ONE</div>

                            @if(isset($qrCodes[$ticket->id]))
                                <img src="{{ $qrCodes[$ticket->id] }}" class="qr-image" alt="QR Code">
                            @endif

                            <div class="ticket-code">{{ $ticket->code }}</div>
                            <div><span class="status-pill">{{ $ticket->status ?? 'ACTIVE' }}</span></div>
                            <div style="font-size: 8.5px; color: #94a3b8; margin-top: 8px;">Scan at gate counter for entry verification</div>
                        </td>
                    </tr>
                </table>
            </div>

            @if(!$loop->last || !$loop->parent->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    @endforeach

    <div class="terms-box">
        <div class="terms-title">Terms & Conditions / Syarat & Ketentuan Masuk</div>
        <ol class="terms-list">
            <li>E-Ticket ini adalah bukti masuk yang sah dan wajib ditunjukkan kepada petugas di lokasi acara melalui smartphone atau cetakan fisik.</li>
            <li>Kode QR pada tiket bersifat unik dan hanya berlaku untuk 1 (satu) kali pemindaian masuk (Single Entry). Dilarang menyebarkan atau menggandakan QR code kepada siapapun.</li>
            <li>Penyelenggara acara berhak menolak akses masuk jika kode QR tiket telah dipindai sebelumnya atau dinyatakan tidak valid.</li>
            <li>Harap membawa kartu identitas resmi (KTP / Paspor / SIM / Kartu Pelajar) yang sesuai dengan nama pemegang tiket saat verifikasi.</li>
            <li>Tiket yang telah dibeli tidak dapat ditukar atau dikembalikan (non-refundable), kecuali ada kebijakan khusus dari pihak penyelenggara acara.</li>
        </ol>
    </div>

</body>
</html>

