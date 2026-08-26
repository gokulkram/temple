<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail('method not allowed', 405);
}

$body = api_json_body();
$username = is_string($body['username'] ?? null) ? trim($body['username']) : '';
$password = is_string($body['password'] ?? null) ? $body['password'] : '';

if ($username === '' || $password === '') {
    api_fail('username and password required', 400);
}

$result = api_verify_credentials($username, $password);
if (!$result['ok']) {
    api_fail($result['error'], 401);
}

$issued = api_issue_token($result['user']['_id']);
api_ok([
    'token'      => $issued['token'],
    'username'   => $result['user']['username'],
    'expires_at' => $issued['expires_at'],
]);
