<?php
/**
 * Bearer-token auth for api/*.php.
 *
 * Deliberately does NOT require_once includes/auth.php: that file calls
 * session_start_secure(), which can session_start(), write $_SESSION, and on
 * idle-timeout issue a Location: redirect - none of that belongs in a JSON
 * API. The credential/lockout check below is a small, intentional
 * duplication of attempt_login()'s logic against the same `users` fields
 * (failed_attempts, locked_until, password_hash), not an extraction - it
 * lets a bad password from the phone lock the web login too, which is
 * correct, without risking auth.php's tested session behavior.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

const API_TOKEN_TTL_SECONDS = 30 * 24 * 60 * 60; // sliding 30-day expiry

/** Check username+password the same way attempt_login() does, without touching $_SESSION. */
function api_verify_credentials(string $username, string $password): array
{
    $users = db()->selectCollection('users');
    $user = $users->findOne(['username' => $username]);

    $lockedUntil = $user && $user['locked_until'] !== null
        ? $user['locked_until']->toDateTime()->getTimestamp()
        : null;
    if ($lockedUntil !== null && $lockedUntil > time()) {
        $mins = (int) ceil(($lockedUntil - time()) / 60);
        return ['ok' => false, 'error' => "Account locked. Try again in $mins minute" . ($mins === 1 ? '' : 's') . "."];
    }

    // password_verify() is constant-time; run it even when the user doesn't
    // exist so a wrong username and a wrong password cost the same.
    $ok = $user
        ? password_verify($password, $user['password_hash'])
        : password_verify($password, '$2y$10$usesomesillystringforsalt0000000000000000000000000000');

    if (!$ok) {
        if ($user) {
            api_record_login_failure($user);
        }
        return ['ok' => false, 'error' => 'Incorrect username or password.'];
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $users->updateOne(['_id' => $user['_id']], ['$set' => ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]]);
    }
    $users->updateOne(
        ['_id' => $user['_id']],
        ['$set' => ['failed_attempts' => 0, 'locked_until' => null, 'last_login' => new UTCDateTime()]]
    );

    return ['ok' => true, 'user' => $user];
}

function api_record_login_failure(object $user): void
{
    $users = db()->selectCollection('users');
    $n = (int) $user['failed_attempts'] + 1;
    if ($n >= LOGIN_MAX_TRIES) {
        $lockUntil = new UTCDateTime((time() + LOGIN_LOCK_MINS * 60) * 1000);
        $users->updateOne(['_id' => $user['_id']], ['$set' => ['failed_attempts' => 0, 'locked_until' => $lockUntil]]);
    } else {
        $users->updateOne(['_id' => $user['_id']], ['$set' => ['failed_attempts' => $n]]);
    }
}

/**
 * Issue a new bearer token for a user. The raw token is returned exactly
 * once here; only its sha256 is ever stored. sha256 (not bcrypt) is
 * deliberate: the token is already 256 bits of random data, not a
 * human-chosen secret, so there is nothing for bcrypt's slowness to defend
 * against here, and running bcrypt on every API call would needlessly slow
 * down every request.
 */
function api_issue_token(ObjectId $userId): array
{
    $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $now = time();
    $expires = $now + API_TOKEN_TTL_SECONDS;

    db()->selectCollection('api_tokens')->insertOne([
        'user_id'      => $userId,
        'token_hash'   => hash('sha256', $raw),
        'created_at'   => new UTCDateTime($now * 1000),
        'expires_at'   => new UTCDateTime($expires * 1000),
        'last_used_at' => new UTCDateTime($now * 1000),
        'revoked'      => false,
    ]);

    return ['token' => $raw, 'expires_at' => gmdate('c', $expires)];
}

/**
 * Validate the Authorization header. Returns ['user'=>..., 'token_hash'=>...] or null.
 *
 * Reads via getallheaders(), not $_SERVER['HTTP_AUTHORIZATION'] - Apache's
 * mod_php does not populate the latter for this header (confirmed locally),
 * while getallheaders() reads Apache's request headers table directly and
 * works the same way on the Render deployment (also Apache/mod_php).
 */
function api_authenticate(): ?array
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = '';
    foreach ($headers as $name => $value) {
        if (strcasecmp($name, 'Authorization') === 0) {
            $auth = $value;
            break;
        }
    }
    if (!preg_match('/^Bearer\s+(\S+)$/', $auth, $m)) {
        return null;
    }
    $hash = hash('sha256', $m[1]);

    $tokens = db()->selectCollection('api_tokens');
    $tok = $tokens->findOne(['token_hash' => $hash, 'revoked' => false]);
    if (!$tok || $tok['expires_at']->toDateTime()->getTimestamp() < time()) {
        return null;
    }

    $user = db()->selectCollection('users')->findOne(['_id' => $tok['user_id']]);
    if (!$user) {
        return null;
    }

    // Slide the expiry forward on use, so an actively-used app never needs re-login.
    $now = time();
    $tokens->updateOne(['_id' => $tok['_id']], ['$set' => [
        'last_used_at' => new UTCDateTime($now * 1000),
        'expires_at'   => new UTCDateTime(($now + API_TOKEN_TTL_SECONDS) * 1000),
    ]]);

    return ['user' => $user, 'token_hash' => $hash];
}

/** Guard for every endpoint except login. 401s (JSON, never HTML/redirect) on failure. */
function api_require_auth(): array
{
    $ctx = api_authenticate();
    if ($ctx === null) {
        api_fail('unauthorized', 401);
    }
    return $ctx;
}

function api_revoke_token(string $tokenHash): void
{
    db()->selectCollection('api_tokens')->updateOne(['token_hash' => $tokenHash], ['$set' => ['revoked' => true]]);
}

/** Called on password change (from either surface) so a stolen token doesn't survive it. */
function api_revoke_all_tokens(ObjectId $userId): void
{
    db()->selectCollection('api_tokens')->updateMany(['user_id' => $userId], ['$set' => ['revoked' => true]]);
}
