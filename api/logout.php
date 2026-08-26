<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail('method not allowed', 405);
}

$ctx = api_require_auth();
api_revoke_token($ctx['token_hash']);
api_ok();
