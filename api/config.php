<?php
/**
 * Serves includes/collections.php's config as data, so the mobile app is
 * driven by the same "one generic editor" field definitions as admin/edit.php
 * and admin/settings.php - a field added server-side needs no app change.
 */
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/collections.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_fail('method not allowed', 405);
}
api_require_auth();

$groups = setting_groups();
$settingTypes = [];
foreach ($groups as $keys) {
    foreach ($keys as $k) {
        $settingTypes[$k] = setting_type($k);
    }
}

api_ok([
    'collections'    => collections(),
    'setting_groups' => $groups,
    'setting_types'  => $settingTypes,
]);
