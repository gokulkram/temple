<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

// Already signed in? Go straight to the dashboard.
if (is_logged_in()) {
    redirect('index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $error = attempt_login(post_str($_POST, 'username'), (string) ($_POST['password'] ?? ''));
    if ($error === null) {
        redirect('index.php');
    }
}

admin_head('உள்நுழைவு / Log in', false);
?>
<div class="login-wrap">
    <div class="login-card">
        <div class="om">ॐ</div>
        <h1>அருள்மிகு ஆனைமலை கருப்பராயர்<br>திருக்கோவில் — நிர்வாகம்</h1>

        <?php if (!empty($_GET['timeout'])): ?>
            <div class="flash err">நேரம் முடிந்ததால் வெளியேற்றப்பட்டீர்கள்.
                <em>(Signed out after inactivity.)</em></div>
        <?php endif; ?>
        <?php if (!empty($_GET['bye'])): ?>
            <div class="flash ok">வெளியேறிவிட்டீர்கள். <em>(Signed out.)</em></div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="flash err"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <div class="field">
                <label for="u">பயனர்பெயர் / Username</label>
                <input type="text" id="u" name="username" required autofocus
                       value="<?= esc(post_str($_POST, 'username')) ?>">
            </div>
            <div class="field">
                <label for="p">கடவுச்சொல் / Password</label>
                <input type="password" id="p" name="password" required>
            </div>
            <button class="btn" type="submit" style="width:100%">உள்நுழை / Log in</button>
        </form>
    </div>
</div>
<?php admin_foot();
