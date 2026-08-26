<?php
/** Site-wide settings, mirrors admin/settings.php. */
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/collections.php';
require_once __DIR__ . '/../includes/sanitize.php';

api_require_auth();

$groups = setting_groups();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = [];
    foreach (db()->selectCollection('settings')->find() as $r) {
        $rows[$r['_id']] = ['svalue' => $r['svalue'], 'notes' => $r['notes'] ?? ''];
    }
    $values = [];
    foreach ($groups as $keys) {
        foreach ($keys as $k) {
            if (isset($rows[$k])) {
                $values[$k] = [
                    'svalue' => $rows[$k]['svalue'],
                    'notes'  => $rows[$k]['notes'],
                    'type'   => setting_type($k),
                ];
            }
        }
    }
    api_ok(['groups' => $groups, 'values' => $values]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail('method not allowed', 405);
}

$body = api_json_body();
$posted = is_array($body['s'] ?? null) ? $body['s'] : [];

$settingsCol = db()->selectCollection('settings');
try {
    // Iterate the whitelist from setting_groups(), never the client's keys
    // directly - otherwise a request could write an arbitrary settings doc.
    foreach ($groups as $keys) {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $posted) || !is_string($posted[$k])) {
                continue; // absent, or not a plain string: leave the stored value alone
            }
            $raw = trim($posted[$k]);
            $settingsCol->updateOne(
                ['_id' => $k],
                ['$set' => ['svalue' => setting_type($k) === 'rich' ? clean_html($raw) : $raw]]
            );
        }
    }
} catch (Throwable $e) {
    error_log('api/settings.php save failed: ' . $e->getMessage());
    api_fail('could not save', 500);
}

api_ok();
