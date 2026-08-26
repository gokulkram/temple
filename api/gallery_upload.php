<?php
/**
 * Multipart photo upload - mirrors admin/gallery.php's "upload" action.
 * The only api/ endpoint whose request body isn't JSON; the response still is.
 */
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/upload.php';

api_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail('method not allowed', 405);
}

$files = $_FILES['photos'] ?? null;

// PHP silently empties $_POST/$_FILES if the request exceeded
// post_max_size/upload_max_filesize - report that plainly instead of a
// misleading "added: 0".
if (!$files || !is_array($files['name']) || count($files['name']) === 0) {
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 0) {
        api_fail('upload too large or empty - nothing was received by the server', 400);
    }
    api_fail('no files were selected', 400);
}

$gallery = db()->selectCollection('gallery_photos');
$top = $gallery->find([], ['sort' => ['sort_order' => -1], 'limit' => 1])->toArray();
$next = $top ? (int) $top[0]['sort_order'] : 0;

$added = 0;
$errors = [];

for ($i = 0; $i < count($files['name']); $i++) {
    if ((int) $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
        continue;
    }
    $one = [
        'name'     => $files['name'][$i],
        'type'     => $files['type'][$i],
        'tmp_name' => $files['tmp_name'][$i],
        'error'    => $files['error'][$i],
        'size'     => $files['size'][$i],
    ];
    $res = handle_upload($one, pathinfo((string) $one['name'], PATHINFO_FILENAME));
    if (!$res['ok']) {
        $errors[] = ((string) $one['name']) . ': ' . $res['error'];
        continue;
    }
    $next += 10;
    $gallery->insertOne([
        'filename'    => $res['filename'],
        'label'       => 'New photo',
        'caption'     => 'New photo',
        'sort_order'  => $next,
        'uploaded_at' => new \MongoDB\BSON\UTCDateTime(),
    ]);
    $added++;
}

api_ok(['added' => $added, 'errors' => $errors]);
