<?php
/** Dashboard: one tile per editable area, with a live row count on each. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';
require_login();

function row_count(string $table): int
{
    return (int) db()->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}

$cols = collections();
admin_head('கட்டுப்பாட்டு பலகை / Dashboard');
?>
<div class="wrap">
    <h1>கட்டுப்பாட்டு பலகை</h1>
    <p class="sub">Dashboard — choose a section to edit. Changes appear on the site immediately.</p>

    <?php show_flash(); ?>

    <div class="tiles">
        <a class="tile" href="settings.php">
            <span class="ico">⚙️</span>
            <span class="name">தள அமைப்புகள் / Site Settings</span>
            <span class="count">பெயர், தொலைபேசி, முதல் பகுதி, SEO</span>
        </a>

        <?php foreach ($cols as $key => $c):
            $total = 0;
            foreach ($c['lists'] as $table => $_) { $total += row_count($table); } ?>
            <a class="tile" href="edit.php?c=<?= esc($key) ?>">
                <span class="ico"><?= esc($c['icon']) ?></span>
                <span class="name"><?= esc($c['label']) ?></span>
                <span class="count"><?= $total ?> உருப்படிகள் / items</span>
            </a>
        <?php endforeach; ?>

        <a class="tile" href="gallery.php">
            <span class="ico">🖼️</span>
            <span class="name">கோயில் படங்கள் / Gallery</span>
            <span class="count"><?= row_count('gallery_photos') ?> படங்கள் / photos</span>
        </a>

        <a class="tile" href="password.php">
            <span class="ico">🔑</span>
            <span class="name">கடவுச்சொல் மாற்று / Password</span>
            <span class="count">Change your login password</span>
        </a>
    </div>
</div>
<?php admin_foot();
