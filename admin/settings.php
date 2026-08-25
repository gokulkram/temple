<?php
/** Site-wide key/value settings, grouped so related fields sit together. */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';
require_login();

$groups = setting_groups();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $settingsCol = db()->selectCollection('settings');
    try {
        foreach ($groups as $keys) {
            foreach ($keys as $k) {
                $posted = is_array($_POST['s'] ?? null) ? ($_POST['s'][$k] ?? null) : null;
                // Absent, or not a plain string (an array smuggled in as
                // s[key][]=x), means "leave the stored value alone". Without
                // this, post_str() returned '' and silently blanked it.
                if (!is_string($posted)) {
                    continue;
                }
                $raw = trim($posted);
                $settingsCol->updateOne(
                    ['_id' => $k],
                    ['$set' => ['svalue' => setting_type($k) === 'rich' ? clean_html($raw) : $raw]]
                );
            }
        }
        flash('சேமிக்கப்பட்டது. (Settings saved.)');
    } catch (Throwable $e) {
        error_log('Settings save failed: ' . $e->getMessage());
        flash('சேமிக்க முடியவில்லை. (Could not save.)', 'err');
    }
    redirect('settings.php');
}

// Fetch current values plus the per-field hint stored alongside them.
$rows = [];
foreach (db()->selectCollection('settings')->find() as $r) {
    $rows[$r['_id']] = ['skey' => $r['_id'], 'svalue' => $r['svalue'], 'notes' => $r['notes'] ?? ''];
}

admin_head('தள அமைப்புகள் / Site Settings');
?>
<div class="wrap">
    <h1>⚙️ தள அமைப்புகள் / Site Settings</h1>
    <p class="sub">Temple details, the hero section, search-engine text and the footer.</p>

    <?php show_flash(); ?>

    <div class="help">
        <strong>பகிர்வு முகவரி / Sharing URLs:</strong>
        <code>og_url</code> and <code>og_image</code> must be full
        <code>https://</code> addresses once the site is live, or WhatsApp and
        Facebook previews will show no image.
    </div>

    <form method="post">
        <?= csrf_field() ?>
        <?php foreach ($groups as $title => $keys): ?>
            <h2><?= esc($title) ?></h2>
            <div class="panel">
                <?php foreach ($keys as $k):
                    if (!isset($rows[$k])) { continue; }
                    field(
                        [
                            'label'    => $k,
                            'type'     => setting_type($k),
                            'hint'     => $rows[$k]['notes'] ? esc($rows[$k]['notes']) : '',
                            'required' => false,
                        ],
                        "s[$k]",
                        $rows[$k]['svalue']
                    );
                endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="actions">
            <button class="btn" type="submit">சேமி / Save changes</button>
            <a class="btn ghost" href="index.php">திரும்பு / Back</a>
        </div>
    </form>
</div>
<?php admin_foot();
