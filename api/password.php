<?php
/**
 * Change the logged-in user's password. Same validation as admin/password.php,
 * plus revoking every api_tokens row for this user (including the one used
 * for this request) - forces re-login everywhere, mirroring what
 * session_regenerate_id(true) does for the web session.
 */
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail('method not allowed', 405);
}

$ctx = api_require_auth();
$user = $ctx['user'];

$body = api_json_body();
$current = is_string($body['current'] ?? null) ? $body['current'] : '';
$new = is_string($body['new'] ?? null) ? $body['new'] : '';
$again = is_string($body['again'] ?? null) ? $body['again'] : '';

$errors = [];
if (!password_verify($current, (string) $user['password_hash'])) {
    $errors[] = 'Current password is incorrect.';
}
if (mb_strlen($new) < 10) {
    $errors[] = 'New password must be at least 10 characters.';
}
if ($new !== $again) {
    $errors[] = 'New passwords do not match.';
}

if ($errors) {
    api_fail(implode(' ', $errors), 400);
}

db()->selectCollection('users')->updateOne(
    ['_id' => $user['_id']],
    ['$set' => ['password_hash' => password_hash($new, PASSWORD_DEFAULT)]]
);
api_revoke_all_tokens($user['_id']);

api_ok();
