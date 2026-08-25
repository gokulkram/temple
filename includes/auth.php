<?php
/**
 * Sessions, login, CSRF and lockout.
 *
 * Every admin page starts with require_login(); every form that changes
 * something carries a CSRF token that is checked before the write happens.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

use MongoDB\BSON\UTCDateTime;

/** Start a hardened session. Safe to call more than once. */
function session_start_secure(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,      // JavaScript cannot read the session cookie
        'secure'   => $https,    // only sent over HTTPS once the site has a cert
        'samesite' => 'Strict',  // not sent on cross-site requests
    ]);
    session_start();

    // Log out an idle session rather than leaving the admin open on a shared PC.
    if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > SESSION_IDLE_MAX) {
        logout();
        redirect('login.php?timeout=1');
    }
    $_SESSION['last_seen'] = time();
}

function is_logged_in(): bool
{
    session_start_secure();
    return !empty($_SESSION['user_id']);
}

function current_user(): ?string
{
    return $_SESSION['username'] ?? null;
}

/** Guard for every admin page. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/**
 * Attempt a login.
 * Returns null on success, or a message to show the user on failure.
 */
function attempt_login(string $username, string $password): ?string
{
    session_start_secure();

    $user = db()->selectCollection('users')->findOne(['username' => $username]);

    $lockedUntil = $user && $user['locked_until'] !== null
        ? $user['locked_until']->toDateTime()->getTimestamp()
        : null;
    if ($lockedUntil !== null && $lockedUntil > time()) {
        $mins = (int) ceil(($lockedUntil - time()) / 60);
        return "கணக்கு தற்காலிகமாக பூட்டப்பட்டுள்ளது. $mins நிமிடங்களில் மீண்டும் முயற்சிக்கவும். "
             . "(Account locked. Try again in $mins minute" . ($mins === 1 ? '' : 's') . ".)";
    }

    // password_verify() is constant-time. When the user does not exist we still
    // run a hash so that a wrong username and a wrong password cost the same.
    $ok = $user
        ? password_verify($password, $user['password_hash'])
        : password_verify($password, '$2y$10$usesomesillystringforsalt0000000000000000000000000000');

    if (!$ok) {
        if ($user) {
            record_failure($user);
        }
        return 'தவறான பயனர்பெயர் அல்லது கடவுச்சொல். (Incorrect username or password.)';
    }

    $users = db()->selectCollection('users');

    // Re-hash if PHP's default cost or algorithm has moved on since signup.
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $users->updateOne(
            ['_id' => $user['_id']],
            ['$set' => ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]]
        );
    }

    $users->updateOne(
        ['_id' => $user['_id']],
        ['$set' => ['failed_attempts' => 0, 'locked_until' => null, 'last_login' => new UTCDateTime()]]
    );

    // New session ID on privilege change, to defeat session fixation.
    session_regenerate_id(true);
    $_SESSION['user_id']   = (string) $user['_id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['last_seen'] = time();

    return null;
}

function record_failure(object $user): void
{
    $users = db()->selectCollection('users');
    $n = (int) $user['failed_attempts'] + 1;
    if ($n >= LOGIN_MAX_TRIES) {
        $lockUntil = new UTCDateTime((time() + LOGIN_LOCK_MINS * 60) * 1000);
        $users->updateOne(
            ['_id' => $user['_id']],
            ['$set' => ['failed_attempts' => 0, 'locked_until' => $lockUntil]]
        );
    } else {
        $users->updateOne(['_id' => $user['_id']], ['$set' => ['failed_attempts' => $n]]);
    }
}

function logout(): void
{
    session_start_secure();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'],
                  $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------------------------------------------------------------- CSRF

function csrf_token(): string
{
    session_start_secure();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Hidden input to drop inside every form that writes. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/**
 * Verify the token on a POST. Without this, any site the admin visits while
 * logged in could silently submit changes on their behalf.
 */
function csrf_check(): void
{
    session_start_secure();
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || $sent === '' || empty($_SESSION['csrf'])
        || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(400);
        exit('Security token mismatch. Go back, reload the page and try again.');
    }
}

// ---------------------------------------------------------------- flash

function flash(string $msg, string $type = 'ok'): void
{
    session_start_secure();
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function take_flash(): array
{
    session_start_secure();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
