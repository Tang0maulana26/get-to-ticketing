<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Generates professional E-Ticket PDFs with individual vector QR codes for concert admission.
 */
class TicketPdfService
{
    /**
     * Generate PDF instance containing all valid tickets for the given order.
     */
    public function generate(Order $order): DomPdfInstance
    {
        $order->loadMissing(['items.tickets', 'user']);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        $qrOptions = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'scale' => 6,
        ]);
        $qrCodeGenerator = new QRCode($qrOptions);

        $qrCodes = [];
        foreach ($order->items as $item) {
            foreach ($item->tickets as $ticket) {
                // Save QR code directly as a temporary PNG file in project storage
                // This completely prevents Windows C:\WINDOWS permission failures in Dompdf data-URI parsing
                $qrFilename = 'qr_' . md5($ticket->code) . '.png';
                $qrFilePath = $tempDir . DIRECTORY_SEPARATOR . $qrFilename;

                if (! file_exists($qrFilePath) || filesize($qrFilePath) === 0) {
                    file_put_contents($qrFilePath, $qrCodeGenerator->render($ticket->code));
                }

                $qrCodes[$ticket->id] = str_replace('\\', '/', $qrFilePath);
            }
        }

        $pdf = Pdf::loadView('tickets.pdf', [
            'order' => $order,
            'qrCodes' => $qrCodes,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('tempDir', $tempDir);
        $pdf->setOption('chroot', [base_path(), $tempDir]);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);

        return $pdf;
    }

    /**
     * Output raw PDF binary string (e.g. for email attachment).
     */
    public function output(Order $order): string
    {
        return $this->generate($order)->output();
    }
}
