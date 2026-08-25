<?php
/**
 * Image upload handling.
 *
 * A file upload is the most dangerous thing an admin panel does, so this runs
 * four independent defences rather than trusting any single one:
 *
 *   1. The browser-supplied name and MIME type are ignored completely.
 *   2. The real type is read from the file's own bytes (getimagesize).
 *   3. The image is decoded and re-encoded through GD. A JPEG with PHP
 *      appended to it does not survive being turned back into pixels.
 *   4. The saved name is generated here, always ending in .jpg, and
 *      images/.htaccess refuses to execute anything in that folder anyway.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Process one uploaded file.
 * Returns ['ok' => true, 'filename' => '...'] or ['ok' => false, 'error' => '...'].
 */
function handle_upload(array $file, string $slug = 'photo'): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return fail('Malformed upload.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return fail('No file was selected.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return fail('That file is larger than the server allows.');
        default:
            return fail('Upload failed (code ' . (int) $file['error'] . ').');
    }

    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return fail(sprintf('That image is %.1f MB. The limit is %d MB.',
            $file['size'] / 1048576, MAX_UPLOAD_BYTES / 1048576));
    }

    // Must be a genuine uploaded temp file, not a path someone supplied.
    if (!is_uploaded_file($file['tmp_name'])) {
        return fail('Upload rejected.');
    }

    // Type comes from the file's own header bytes, never from the browser.
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return fail('That file is not a readable image.');
    }
    [$width, $height, $type] = $info;

    $readers = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG  => 'imagecreatefrompng',
        IMAGETYPE_GIF  => 'imagecreatefromgif',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
    ];
    if (!isset($readers[$type]) || !function_exists($readers[$type])) {
        return fail('Only JPEG, PNG, GIF and WebP images can be uploaded.');
    }

    // Guard against decompression bombs before allocating memory for pixels.
    if ($width * $height > 50_000_000) {
        return fail('That image has too many pixels to process.');
    }

    $src = @$readers[$type]($file['tmp_name']);
    if (!$src) {
        return fail('That image could not be read.');
    }

    // Phone photos carry rotation in EXIF rather than in the pixels.
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $src = apply_exif_rotation($src, (int) ($exif['Orientation'] ?? 1));
    }

    if (!is_dir(IMAGE_DIR)) { mkdir(IMAGE_DIR, 0755, true); }
    if (!is_dir(THUMB_DIR)) { mkdir(THUMB_DIR, 0755, true); }

    $filename = unique_filename($slug);

    $full = resize_within($src, MAX_IMAGE_EDGE);
    // Re-encoding is what neutralises anything hidden in the original bytes.
    $saved = imagejpeg($full, IMAGE_DIR . '/' . $filename, 82);
    imagedestroy($full);

    if (!$saved) {
        imagedestroy($src);
        return fail('The image could not be saved. Check folder permissions.');
    }

    $thumb = resize_within($src, THUMB_EDGE);
    imagejpeg($thumb, THUMB_DIR . '/' . $filename, 78);
    imagedestroy($thumb);
    imagedestroy($src);

    @chmod(IMAGE_DIR . '/' . $filename, 0644);

    return ['ok' => true, 'filename' => $filename];
}

function fail(string $msg): array
{
    return ['ok' => false, 'error' => $msg];
}

/** A collision-proof name we control entirely. Always .jpg. */
function unique_filename(string $slug): string
{
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $slug) ?? '');
    $slug = trim($slug, '-') ?: 'photo';
    $slug = substr($slug, 0, 40);
    do {
        $name = $slug . '-' . bin2hex(random_bytes(4)) . '.jpg';
    } while (file_exists(IMAGE_DIR . '/' . $name));
    return $name;
}

/** Scale down so the longest side is at most $max. Never scales up. */
function resize_within(GdImage $src, int $max): GdImage
{
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1.0, $max / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    // Flatten any transparency onto white, since we always write JPEG.
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    return $dst;
}

function apply_exif_rotation(GdImage $img, int $orientation): GdImage
{
    return match ($orientation) {
        3       => imagerotate($img, 180, 0) ?: $img,
        6       => imagerotate($img, -90, 0) ?: $img,
        8       => imagerotate($img, 90, 0) ?: $img,
        default => $img,
    };
}

/** Remove a photo and its thumbnail. Refuses anything but a bare filename. */
function delete_image(string $filename): bool
{
    if ($filename === '' || basename($filename) !== $filename
        || str_contains($filename, '..')) {
        return false;
    }
    @unlink(THUMB_DIR . '/' . $filename);
    return @unlink(IMAGE_DIR . '/' . $filename);
}

/** Images already present in /images, for the "choose existing" picker. */
function existing_images(): array
{
    $out = [];
    foreach (glob(IMAGE_DIR . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [] as $p) {
        $out[] = basename($p);
    }
    sort($out);
    return $out;
}
