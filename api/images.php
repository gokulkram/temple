<?php
/**
 * Every image file on disk, for the `image` field-type picker. Distinct
 * from gallery.php: this is existing_images() (every file), not
 * gallery_photos rows (captioned/ordered DB entries) - matches
 * image_field() in admin/_layout.php, which picks from this same list.
 */
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_fail('method not allowed', 405);
}
api_require_auth();

api_ok(existing_images());
