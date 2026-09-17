<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Re-encodes an uploaded image through GD before it's ever written to disk.
 *
 * The `image`/`mimes` validation rules only check the file's declared
 * extension and a header sniff — they don't guarantee the bytes after that
 * header are harmless. A polyglot file (valid JPEG header, arbitrary/script
 * payload appended after it) or embedded EXIF metadata would pass
 * validation untouched and be served back to every visitor as-is. Decoding
 * the upload into a raster image and re-encoding it from scratch keeps only
 * the pixel data GD actually understood, discarding everything else.
 */
class ImageReencoder
{
    private const EXTENSION_BY_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @return array{extension: string, contents: string}
     */
    public function reencode(UploadedFile $file): array
    {
        $imageInfo = @getimagesize($file->getRealPath());

        if ($imageInfo === false || ! isset(self::EXTENSION_BY_MIME[$imageInfo['mime']])) {
            throw new RuntimeException('This file is not a genuine JPEG, PNG, or WebP image.');
        }

        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw new RuntimeException('This file could not be decoded as an image.');
        }

        $extension = self::EXTENSION_BY_MIME[$imageInfo['mime']];

        // No imagedestroy() call: GD images are garbage-collected like any
        // other object since PHP 8, and the explicit free is deprecated
        // outright as of PHP 8.5.
        ob_start();
        match ($extension) {
            'png' => imagepng($image),
            'webp' => imagewebp($image, quality: 90),
            default => imagejpeg($image, quality: 90),
        };
        $contents = ob_get_clean();

        return ['extension' => $extension, 'contents' => $contents];
    }
}
