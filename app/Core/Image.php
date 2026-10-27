<?php
namespace App\Core;

defined('APP_ROOT') || exit;

/**
 * Validates an uploaded image and re-encodes it. Re-encoding strips EXIF data
 * (including GPS location), which matters for member safety on a dating app.
 */
final class Image
{
    public const MAX_BYTES = 12 * 1024 * 1024;
    private const FULL = 1280;
    private const THUMB = 480;

    /** @return array{data:string, thumb:string, mime:string, width:int, height:int} */
    public static function fromUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $tooBig = in_array($file['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            throw new \RuntimeException($tooBig ? 'That photo is too large. Try one under 12 MB.' : 'The upload failed. Please try again.');
        }
        if (!is_uploaded_file($file['tmp_name']) && PHP_SAPI !== 'cli') {
            throw new \RuntimeException('Invalid upload.');
        }
        return self::fromPath($file['tmp_name'], (int) $file['size']);
    }

    public static function fromPath(string $path, int $size): array
    {
        if ($size > self::MAX_BYTES) {
            throw new \RuntimeException('That photo is too large. Try one under 12 MB.');
        }
        $info = @getimagesize($path);
        $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];
        if (!$info || !in_array($info[2], $allowed, true)) {
            throw new \RuntimeException('Please upload a JPG, PNG or WebP photo.');
        }
        if ($info[0] < 200 || $info[1] < 200) {
            throw new \RuntimeException('That photo is too small. Use one at least 200 × 200 pixels.');
        }

        if (!extension_loaded('gd')) {
            // Without GD we can only store the original bytes.
            $raw = (string) file_get_contents($path);
            return ['data' => $raw, 'thumb' => $raw, 'mime' => $info['mime'], 'width' => $info[0], 'height' => $info[1]];
        }

        @ini_set('memory_limit', '384M');
        switch ($info[2]) {
            case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($path); break;
            case IMAGETYPE_PNG:  $src = @imagecreatefrompng($path); break;
            case IMAGETYPE_WEBP: $src = @imagecreatefromwebp($path); break;
            default:             $src = @imagecreatefromgif($path);
        }
        if (!$src) {
            throw new \RuntimeException('We could not read that photo. Try a different one.');
        }
        if ($info[2] === IMAGETYPE_JPEG) {
            $src = self::orient($src, $path);
        }

        $full = self::resize($src, self::FULL);
        $thumb = self::resize($src, self::THUMB);
        imagedestroy($src);

        $result = [
            'data'   => self::jpeg($full, 84),
            'thumb'  => self::jpeg($thumb, 80),
            'mime'   => 'image/jpeg',
            'width'  => imagesx($full),
            'height' => imagesy($full),
        ];
        imagedestroy($full);
        imagedestroy($thumb);
        return $result;
    }

    private static function orient($img, string $path)
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $o = (int) ($exif['Orientation'] ?? 1);
        $angles = [3 => 180, 6 => -90, 8 => 90];
        if (isset($angles[$o])) {
            $rotated = imagerotate($img, $angles[$o], 0);
            if ($rotated) {
                imagedestroy($img);
                return $rotated;
            }
        }
        return $img;
    }

    private static function resize($src, int $max)
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $max / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $dst;
    }

    private static function jpeg($img, int $quality): string
    {
        imageinterlace($img, true);
        ob_start();
        imagejpeg($img, null, $quality);
        return (string) ob_get_clean();
    }
}
