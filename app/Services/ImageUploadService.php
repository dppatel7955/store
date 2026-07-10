<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageUploadService
{
    public const MAX_WIDTH = 1600;

    public const QUALITY = 82;

    /**
     * Store an uploaded image as compressed WebP (falls back to JPEG if WebP unsupported).
     *
     * @return string Public path like /uploads/products/abc.webp
     */
    public static function store(
        UploadedFile $file,
        string $directory,
        string $disk = 'custom_public',
        int $maxWidth = self::MAX_WIDTH,
        int $quality = self::QUALITY,
    ): string {
        if (! extension_loaded('gd')) {
            $path = $file->store($directory, $disk);

            return '/uploads/' . ltrim($path, '/');
        }

        $sourcePath = $file->getRealPath();
        if (! $sourcePath || ! is_readable($sourcePath)) {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        $image = self::createImageResource($sourcePath, $file->getMimeType());
        if ($image === false) {
            $path = $file->store($directory, $disk);

            return '/uploads/' . ltrim($path, '/');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $newHeight = (int) round(($maxWidth / $width) * $height);
            $resized = imagecreatetruecolor($maxWidth, $newHeight);

            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $maxWidth, $newHeight, $transparent);

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $supportsWebp = function_exists('imagewebp');
        $extension = $supportsWebp ? 'webp' : 'jpg';
        $filename = Str::uuid()->toString() . '.' . $extension;
        $relativePath = trim($directory, '/') . '/' . $filename;
        $absolutePath = Storage::disk($disk)->path($relativePath);

        $directoryPath = dirname($absolutePath);
        if (! is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $saved = false;
        if ($supportsWebp) {
            $saved = imagewebp($image, $absolutePath, $quality);
        } else {
            $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, imagesx($image), imagesy($image), $white);
            imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $saved = imagejpeg($canvas, $absolutePath, $quality);
            imagedestroy($canvas);
        }

        imagedestroy($image);

        if (! $saved) {
            throw new RuntimeException('Failed to save compressed image.');
        }

        return '/uploads/' . ltrim($relativePath, '/');
    }

    /**
     * @return \GdImage|false
     */
    private static function createImageResource(string $path, ?string $mime)
    {
        $mime = strtolower((string) $mime);

        return match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($path),
            str_contains($mime, 'png') => @imagecreatefrompng($path),
            str_contains($mime, 'gif') => @imagecreatefromgif($path),
            str_contains($mime, 'webp') && function_exists('imagecreatefromwebp') => @imagecreatefromwebp($path),
            default => self::createFromPathGuess($path),
        };
    }

    /**
     * @return \GdImage|false
     */
    private static function createFromPathGuess(string $path)
    {
        $info = @getimagesize($path);
        if (! $info || empty($info['mime'])) {
            return false;
        }

        return self::createImageResource($path, $info['mime']);
    }
}
