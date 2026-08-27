<?php
/**
 * Public, read-only, unauthenticated site content for the visitor mobile app.
 * Mirrors index.php's fetch burst exactly - same helpers, same collections,
 * no pagination (this is a small, single-temple dataset).
 *
 * Deliberately never calls api_require_auth() - this is the one endpoint
 * meant to be reachable with no token at all.
 */
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

// GET-only, cookie-free, credential-free public data - safe from any origin.
// Scoped to this file only, so no admin endpoint gains this header.
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_fail('method not allowed', 405);
}

try {
    $set = settings();
    $set['hero_image_url'] = IMAGE_URL . '/' . ($set['hero_image'] ?? '');

    $photos = [];
    foreach (all('gallery_photos') as $p) {
        $p['url'] = IMAGE_URL . '/' . $p['filename'];
        $p['thumb_url'] = IMAGE_URL . '/thumbs/' . $p['filename'];
        $p['has_thumb'] = is_file(THUMB_DIR . '/' . $p['filename']);
        $p['uploaded_at'] = ($p['uploaded_at'] ?? null) instanceof \MongoDB\BSON\UTCDateTime
            ? $p['uploaded_at']->toDateTime()->format('c')
            : null;
        $photos[] = $p;
    }

    api_ok([
        'settings' => $set,
        'sections' => sections(),
        'nav_links' => all('nav_links'),
        'about_paragraphs' => all('about_paragraphs'),
        'about_rows' => all('about_rows'),
        'history_entries' => all('history_entries'),
        'deity_cards' => all('deity_cards'),
        'festival_items' => all('festival_items'),
        'location_items' => all('location_items'),
        'visit_cards' => all('visit_cards'),
        'gallery_photos' => $photos,
    ]);
} catch (Throwable $e) {
    error_log('api/public.php failed: ' . $e->getMessage());
    api_fail('could not load site content', 500);
}
