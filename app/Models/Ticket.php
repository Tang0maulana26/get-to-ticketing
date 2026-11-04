<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An individual issued ticket with a unique code, created after payment completes.
 */
class Ticket extends Model
{
    protected $fillable = ['code', 'status', 'checked_in_at'];
    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime'];
    }
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Compute a secure cryptographic HMAC signature for this ticket to prevent offline forgery.
     */
    public function getHmacSignature(): string
    {
        $appKey = (string) config('app.key');
        return substr(hash_hmac('sha256', $this->code, $appKey), 0, 16);
    }

    /**
     * Returns the full QR admission payload: UUID + '.' + HMAC signature.
     */
    public function getQrPayload(): string
    {
        return $this->code . '.' . $this->getHmacSignature();
    }

    /**
     * Verifies if a given scanned code and signature match the authentic server signature.
     */
    public static function verifySignature(string $code, string $signature): bool
    {
        $appKey = (string) config('app.key');
        $expected = substr(hash_hmac('sha256', $code, $appKey), 0, 16);
        return hash_equals($expected, $signature);
    }

    /**
     * Generate an inline Data-URI SVG QR code for mobile e-pass admission.
     */
    public function getQrCodeSvgAttribute(): string
    {
        try {
            $options = new \chillerlan\QRCode\QROptions([
                'outputInterface' => \chillerlan\QRCode\Output\QRMarkupSVG::class,
                'outputBase64' => true,
                'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
            ]);

            return (new \chillerlan\QRCode\QRCode($options))->render($this->getQrPayload());
        } catch (\Throwable $e) {
            report($e);
            return '';
        }
    }
}
