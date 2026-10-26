<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventMedia
{
    public function store(UploadedFile $file): string
    {
        // Re-encode uploads to strip metadata and prevent serving active content.
        $source = imagecreatefromstring($file->getContent());
        $width = imagesx($source); $height = imagesy($source);
        $ratio = min(1, 1600 / max($width, $height));
        $output = imagecreatetruecolor((int) ($width * $ratio), (int) ($height * $ratio));
        imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
        imagecopyresampled($output, $source, 0, 0, 0, 0, imagesx($output), imagesy($output), $width, $height);
        ob_start(); imagewebp($output, null, 82); $bytes = ob_get_clean();
        $path = 'events/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $bytes);
        return $path;
    }
}
