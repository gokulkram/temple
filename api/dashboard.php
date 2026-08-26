<?php
/** Mirrors admin/index.php's tile loop: one entry per section + gallery count. */
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/collections.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_fail('method not allowed', 405);
}
api_require_auth();

$sections = [];
foreach (collections() as $key => $col) {
    $total = 0;
    foreach (array_keys($col['lists']) as $table) {
        $total += row_count($table);
    }
    $sections[] = ['key' => $key, 'label' => $col['label'], 'icon' => $col['icon'], 'count' => $total];
}

api_ok([
    'sections'      => $sections,
    'gallery_count' => row_count('gallery_photos'),
]);
