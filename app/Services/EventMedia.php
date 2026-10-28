<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventMedia
{
    private const MAX_EDGE = 1600;

    /**
     * Re-encode an upload as WebP to strip metadata and prevent serving active content.
     */
    public function store(UploadedFile $file): string
    {
        $source = imagecreatefromstring($file->getContent());
        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min(1, self::MAX_EDGE / max($width, $height));

        $output = imagecreatetruecolor((int) ($width * $ratio), (int) ($height * $ratio));
        imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
        imagecopyresampled($output, $source, 0, 0, 0, 0, imagesx($output), imagesy($output), $width, $height);

        ob_start();
        imagewebp($output, null, 82);
        $path = 'events/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, ob_get_clean());

        return $path;
    }
}
