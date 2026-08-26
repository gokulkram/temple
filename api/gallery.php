<?php
/**
 * Gallery list + caption/order/delete save. Mirrors admin/gallery.php's
 * "save" action exactly - NOT wrapped in a transaction (matches existing,
 * documented behavior: a mid-loop failure can leave partial writes), a
 * request with no `rows` key at all is refused rather than treated as
 * "delete everything", deletion is explicit ids only, and a deleted photo's
 * file is only unlinked once no gallery row references it and it isn't the
 * site's hero_image.
 */
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/upload.php';

api_require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $photos = [];
    foreach (all('gallery_photos') as $p) {
        $hasThumb = is_file(THUMB_DIR . '/' . $p['filename']);
        $p['has_thumb'] = $hasThumb;
        $p['thumb_url'] = IMAGE_URL . '/thumbs/' . $p['filename'];
        $p['url'] = IMAGE_URL . '/' . $p['filename'];
        // uploaded_at arrives as a BSON UTCDateTime; flatten to ISO 8601 so
        // the mobile client never has to know about Mongo's date wire format.
        $p['uploaded_at'] = $p['uploaded_at']->toDateTime()->format('c');
        $photos[] = $p;
    }
    api_ok($photos);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail('method not allowed', 405);
}

$body = api_json_body();

// A request with no `rows` key at all - or an empty array - is almost
// always malformed, not a deliberate "delete every photo": mirrors
// admin/gallery.php's empty($_POST['rows']) check exactly. An empty JSON
// array is trivial for a client to send by accident (unlike a browser
// form, which simply omits absent fields), so this must reject `[]` too,
// not just a missing key.
if (empty($body['rows']) || !is_array($body['rows'])) {
    api_fail('no photo rows were submitted, so nothing was changed', 400);
}

$gallery = db()->selectCollection('gallery_photos');
try {
    $order = 0;
    foreach ($body['rows'] as $r) {
        if (!is_array($r)) {
            continue;
        }
        $rawId = is_scalar($r['id'] ?? null) ? trim((string) $r['id']) : '';
        if (!preg_match('/^[a-f0-9]{24}$/i', $rawId)) {
            continue;
        }
        $label = is_string($r['label'] ?? null) ? trim($r['label']) : '';
        $caption = is_string($r['caption'] ?? null) ? trim($r['caption']) : '';
        $order += 10;
        $gallery->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($rawId)],
            ['$set' => ['label' => $label, 'caption' => $caption, 'sort_order' => $order]]
        );
    }

    // Deletion is explicit: only ids the client names, never inferred from absence.
    $toDelete = [];
    foreach ((array) ($body['deleted'] ?? []) as $d) {
        if (is_scalar($d) && preg_match('/^[a-f0-9]{24}$/i', trim((string) $d))) {
            $toDelete[] = new \MongoDB\BSON\ObjectId(trim((string) $d));
        }
    }

    $orphans = [];
    if ($toDelete) {
        foreach ($gallery->find(['_id' => ['$in' => $toDelete]], ['projection' => ['filename' => 1]]) as $doc) {
            $orphans[] = $doc['filename'];
        }
        $gallery->deleteMany(['_id' => ['$in' => $toDelete]]);
    }

    // Only unlink a file once no row anywhere still points at it.
    foreach ($orphans as $f) {
        $inUse = $gallery->countDocuments(['filename' => $f]) > 0 || $f === setting('hero_image');
        if (!$inUse) {
            delete_image($f);
        }
    }
} catch (Throwable $e) {
    error_log('api/gallery.php save failed: ' . $e->getMessage());
    api_fail('save failed partway through - reload to check what was saved', 500);
}

api_ok();
