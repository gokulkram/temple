<?php
/** Change the logged-in user's password. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $current = (string) ($_POST['current'] ?? '');
    $new     = (string) ($_POST['new'] ?? '');
    $again   = (string) ($_POST['again'] ?? '');

    $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $hash = (string) $st->fetchColumn();

    if (!password_verify($current, $hash)) {
        $errors[] = 'தற்போதைய கடவுச்சொல் தவறு. (Current password is incorrect.)';
    }
    if (mb_strlen($new) < 10) {
        $errors[] = 'புதிய கடவுச்சொல் குறைந்தது 10 எழுத்துகள் இருக்க வேண்டும். (New password must be at least 10 characters.)';
    }
    if ($new !== $again) {
        $errors[] = 'புதிய கடவுச்சொற்கள் பொருந்தவில்லை. (New passwords do not match.)';
    }

    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
        // A password change should invalidate any other stolen session.
        session_regenerate_id(true);
        flash('கடவுச்சொல் மாற்றப்பட்டது. (Password changed.)');
        redirect('index.php');
    }
}

admin_head('கடவுச்சொல் மாற்று / Change Password');
?>
<div class="wrap">
    <h1>🔑 கடவுச்சொல் மாற்று / Change Password</h1>
    <p class="sub">Signed in as <strong><?= esc(current_user() ?? '') ?></strong>.</p>

    <?php show_flash(); ?>
    <?php foreach ($errors as $e): ?>
        <div class="flash err"><?= esc($e) ?></div>
    <?php endforeach; ?>

    <form method="post" class="panel" autocomplete="off" style="max-width:480px">
        <?= csrf_field() ?>
        <div class="field req">
            <label for="c">தற்போதைய கடவுச்சொல் / Current password</label>
            <input type="password" id="c" name="current" required autofocus>
        </div>
        <div class="field req">
            <label for="n">புதிய கடவுச்சொல் / New password</label>
            <input type="password" id="n" name="new" required minlength="10">
            <div class="hint">குறைந்தது 10 எழுத்துகள். (At least 10 characters.)</div>
        </div>
        <div class="field req">
            <label for="a">மீண்டும் உள்ளிடவும் / Repeat new password</label>
            <input type="password" id="a" name="again" required minlength="10">
        </div>
        <button class="btn" type="submit">மாற்று / Change password</button>
        <a class="btn ghost" href="index.php">திரும்பு / Back</a>
    </form>
</div>
<?php admin_foot();
