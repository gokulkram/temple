<?php
/**
 * Shared chrome for every admin page, plus the small field-rendering helpers
 * used by the generic editor.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/collections.php';

function admin_head(string $title, bool $chrome = true): void
{
    ?><!doctype html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title) ?> · கோயில் நிர்வாகம்</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Tamil:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css?v=<?= filemtime(__DIR__ . '/assets/admin.css') ?>">
</head>
<body>
<?php if ($chrome): ?>
<div class="topbar">
    <div class="brand">
        <a href="index.php">கோயில் நிர்வாகம்</a>
        <span>Temple Admin</span>
    </div>
    <a href="../index.php" target="_blank" rel="noopener">தளத்தைப் பார் / View site ↗</a>
    <span class="who"><?= esc(current_user() ?? '') ?></span>
    <a href="logout.php">வெளியேறு / Log out</a>
</div>
<?php endif;
}

function admin_foot(): void
{
    echo "\n</body>\n</html>\n";
}

/** Render any queued success/error messages. */
function show_flash(): void
{
    foreach (take_flash() as $f) {
        printf('<div class="flash %s">%s</div>', esc($f['type']), esc($f['msg']));
    }
}

/**
 * Render one form field.
 * $name is the full input name, e.g. rows[3][title_ta].
 */
function field(array $def, string $name, ?string $value): void
{
    $id   = 'f' . substr(md5($name), 0, 8);
    $req  = !empty($def['required']);
    $type = $def['type'] ?? 'text';
    ?>
    <div class="field<?= $req ? ' req' : '' ?>">
        <label for="<?= $id ?>"><?= esc($def['label']) ?></label>
        <?php if ($type === 'area' || $type === 'rich'): ?>
            <textarea id="<?= $id ?>" name="<?= esc($name) ?>"
                      class="<?= $type === 'rich' ? 'tall' : '' ?>"
                      <?= $req ? 'required' : '' ?>><?= esc($value) ?></textarea>
        <?php elseif ($type === 'emoji'): ?>
            <input type="text" id="<?= $id ?>" name="<?= esc($name) ?>" class="emoji"
                   maxlength="8" value="<?= esc($value) ?>">
        <?php elseif ($type === 'image'): ?>
            <?php image_field($name, $value, $id); ?>
        <?php else: ?>
            <input type="text" id="<?= $id ?>" name="<?= esc($name) ?>"
                   value="<?= esc($value) ?>" <?= $req ? 'required' : '' ?>>
        <?php endif; ?>
        <?php if (!empty($def['hint'])): ?>
            <div class="hint"><?= $def['hint'] ?></div>
        <?php endif; ?>
        <?php if ($type === 'rich'): ?>
            <div class="hint">HTML அனுமதிக்கப்படும்: &lt;br&gt; &lt;em&gt; &lt;strong&gt; &lt;a&gt; &lt;span&gt;.
                மற்ற குறியீடுகள் நீக்கப்படும். <em>(Other tags are stripped automatically.)</em></div>
        <?php endif; ?>
    </div>
    <?php
}

/** A thumbnail, a dropdown of existing images, and an upload box. */
function image_field(string $name, ?string $value, string $id): void
{
    require_once __DIR__ . '/../includes/upload.php';
    $files = existing_images();
    ?>
    <div class="thumbrow">
        <img src="<?= esc('../' . IMAGE_URL . '/' . (string) $value) ?>" alt="" loading="lazy">
        <div style="flex:1">
            <select id="<?= $id ?>" name="<?= esc($name) ?>">
                <?php foreach ($files as $f): ?>
                    <option value="<?= esc($f) ?>" <?= $f === $value ? 'selected' : '' ?>><?= esc($f) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="hint">புதிய படத்தை <a href="gallery.php">படத்தொகுப்பு</a> பக்கத்தில் பதிவேற்றவும்.
                <em>(Upload new photos on the Gallery page.)</em></div>
        </div>
    </div>
    <?php
}

/** Read a posted value, trimmed, always a string. */
function post_str(array $src, string $key): string
{
    $v = $src[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}
