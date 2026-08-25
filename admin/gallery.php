<?php
/**
 * Gallery manager: upload new photos, edit captions, reorder, delete.
 *
 * Uploads go through includes/upload.php, which re-encodes every image so
 * nothing but pixels survives.
 */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../includes/upload.php';
require_login();

// ---------------------------------------------------------------- upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    csrf_check();

    $files = $_FILES['photos'] ?? null;
    $added = 0;
    $errs  = [];

    if ($files && is_array($files['name'])) {
        $next = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_photos')->fetchColumn();

        for ($i = 0; $i < count($files['name']); $i++) {
            if ((int) $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $one = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $res = handle_upload($one, pathinfo((string) $one['name'], PATHINFO_FILENAME));
            if (!$res['ok']) {
                $errs[] = htmlspecialchars((string) $one['name']) . ': ' . $res['error'];
                continue;
            }
            $next += 10;
            db()->prepare('INSERT INTO gallery_photos (filename, label, caption, sort_order)
                           VALUES (?, ?, ?, ?)')
                ->execute([$res['filename'], 'புதிய படம்', 'புதிய படம் / New photo', $next]);
            $added++;
        }
    }

    if ($added)  { flash("$added படம்(கள்) சேர்க்கப்பட்டன. ($added photo(s) added — add captions below.)"); }
    foreach ($errs as $e) { flash($e, 'err'); }
    if (!$added && !$errs) { flash('எந்த படமும் தேர்ந்தெடுக்கப்படவில்லை. (No file selected.)', 'err'); }

    redirect('gallery.php');
}

// ---------------------------------------------------------------- save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    csrf_check();

    // A submission with no rows at all is almost always a malformed or
    // truncated POST, not a deliberate "delete every photo". Treating it as
    // the latter would destroy the gallery and its files in one click, so
    // refuse it. Emptying the gallery deliberately means deleting the last
    // photo while at least one other row is still present.
    if (empty($_POST['rows']) || !is_array($_POST['rows'])) {
        flash('எந்த படமும் அனுப்பப்படவில்லை — எதுவும் மாற்றப்படவில்லை. '
            . '(No photo rows were submitted, so nothing was changed.)', 'err');
        redirect('gallery.php');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $submitted = $_POST['rows'];
        $kept  = [];
        $order = 0;

        foreach ($submitted as $r) {
            if (!is_array($r)) { continue; }
            $id = (int) (is_scalar($r['id'] ?? null) ? $r['id'] : 0);
            if ($id <= 0) { continue; }
            $order += 10;
            $pdo->prepare('UPDATE gallery_photos SET label = ?, caption = ?, sort_order = ? WHERE id = ?')
                ->execute([post_str($r, 'label'), post_str($r, 'caption'), $order, $id]);
            $kept[] = $id;
        }

        // Deletion is explicit: the browser names the photos it removed.
        // Never inferred from absence - a malformed POST would otherwise wipe
        // the whole gallery and unlink every file.
        $toDelete = [];
        foreach ((array) ($_POST['deleted'] ?? []) as $d) {
            if (is_scalar($d) && ctype_digit((string) $d) && (int) $d > 0) {
                $toDelete[] = (int) $d;
            }
        }

        $orphans = [];
        if ($toDelete) {
            $ph = implode(',', array_fill(0, count($toDelete), '?'));
            $st = $pdo->prepare("SELECT filename FROM gallery_photos WHERE id IN ($ph)");
            $st->execute($toDelete);
            $orphans = $st->fetchAll(PDO::FETCH_COLUMN);
            $pdo->prepare("DELETE FROM gallery_photos WHERE id IN ($ph)")->execute($toDelete);
        }
        $pdo->commit();

        // Only unlink a file once no row anywhere still points at it.
        foreach ($orphans as $f) {
            $st = db()->prepare('SELECT COUNT(*) FROM gallery_photos WHERE filename = ?');
            $st->execute([$f]);
            $inUse = (int) $st->fetchColumn() > 0
                || $f === setting('hero_image');
            if (!$inUse) { delete_image($f); }
        }

        flash('சேமிக்கப்பட்டது. (Gallery saved.)');
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Gallery save failed: ' . $e->getMessage());
        flash('சேமிக்க முடியவில்லை. (Could not save — nothing was changed.)', 'err');
    }
    redirect('gallery.php');
}

$photos = all('gallery_photos');
$sec    = sections()['gallery'] ?? ['title_ta' => '', 'title_en' => ''];

admin_head('கோயில் படங்கள் / Gallery');
?>
<div class="wrap">
    <h1>🖼️ கோயில் படங்கள் / Gallery</h1>
    <p class="sub">Upload photos, write captions, drag to reorder. The order here is the order on the site.</p>

    <?php show_flash(); ?>

    <h2>புதிய படம் பதிவேற்று / Upload photos</h2>
    <form method="post" enctype="multipart/form-data" class="panel">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">
        <div class="field">
            <label for="up">படங்களைத் தேர்ந்தெடு / Choose images</label>
            <input type="file" id="up" name="photos[]" accept="image/*" multiple required>
            <div class="hint">
                JPEG, PNG, WebP அல்லது GIF · அதிகபட்சம் <?= (int) (MAX_UPLOAD_BYTES / 1048576) ?> MB.
                Images are automatically rotated, resized to <?= MAX_IMAGE_EDGE ?>px and re-encoded.
            </div>
        </div>
        <button class="btn" type="submit">பதிவேற்று / Upload</button>
    </form>

    <form method="post" id="gal">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">

        <h2>படங்கள் / Photos <span style="font-weight:400;color:var(--muted)">(<?= count($photos) ?>)</span></h2>

        <?php if (!$photos): ?>
            <p class="empty">இன்னும் படங்கள் இல்லை. (No photos yet — upload some above.)</p>
        <?php endif; ?>

        <div class="photos" id="photolist">
            <?php foreach ($photos as $i => $p):
                $thumb = '../' . IMAGE_URL . '/thumbs/' . $p['filename'];
                $full  = '../' . IMAGE_URL . '/' . $p['filename'];
                $src   = is_file(THUMB_DIR . '/' . $p['filename']) ? $thumb : $full; ?>
                <div class="photo" draggable="true">
                    <img src="<?= esc($src) ?>" alt="" loading="lazy">
                    <div class="body">
                        <div class="panel-head" style="margin-bottom:.5rem">
                            <span class="drag" title="Drag to reorder">⠿</span>
                            <span class="idx"><?= $i + 1 ?></span>
                            <span class="ttl"></span>
                            <button type="button" class="btn danger small js-del">நீக்கு</button>
                        </div>
                        <input type="hidden" name="rows[<?= $i ?>][id]" value="<?= (int) $p['id'] ?>">
                        <?php
                        field(['label' => 'குறுந்தலைப்பு / Short label', 'type' => 'text', 'required' => true],
                              "rows[$i][label]", $p['label']);
                        field(['label' => 'விளக்கம் / Caption', 'type' => 'text', 'required' => true,
                               'hint' => 'Shown enlarged, and read aloud by screen readers.'],
                              "rows[$i][caption]", $p['caption']);
                        ?>
                        <div class="hint"><?= esc($p['filename']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <button class="btn" type="submit">சேமி / Save changes</button>
            <a class="btn ghost" href="index.php">திரும்பு / Back</a>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('gal');
    const list = document.getElementById('photolist');

    form.addEventListener('click', function (e) {
        const del = e.target.closest('.js-del');
        if (!del) return;
        if (!confirm('இந்த படத்தை நீக்கவா? சேமித்த பிறகு நிரந்தரமாக நீக்கப்படும்.\n\nDelete this photo? The file is removed permanently when you save.')) return;

        const card = del.closest('.photo');
        // Name the photo being deleted. The server removes only what is listed
        // here, so a malformed submission can never wipe the gallery.
        const idInput = card.querySelector('input[name$="[id]"]');
        const id = idInput ? parseInt(idInput.value, 10) : 0;
        if (id > 0) {
            const marker = document.createElement('input');
            marker.type = 'hidden';
            marker.name = 'deleted[]';
            marker.value = String(id);
            form.appendChild(marker);
        }
        card.remove();
        renumber();
    });

    let dragged = null;
    list.addEventListener('dragstart', function (e) {
        dragged = e.target.closest('.photo');
        if (dragged) dragged.classList.add('dragging');
    });
    list.addEventListener('dragend', function () {
        dragged?.classList.remove('dragging');
        dragged = null;
        renumber();
    });
    list.addEventListener('dragover', function (e) {
        if (!dragged) return;
        const over = e.target.closest('.photo');
        if (!over || over === dragged) return;
        e.preventDefault();
        const rect = over.getBoundingClientRect();
        const after = (e.clientX - rect.left) > rect.width / 2;
        list.insertBefore(dragged, after ? over.nextSibling : over);
    });

    function renumber() {
        list.querySelectorAll(':scope > .photo').forEach(function (card, i) {
            card.querySelector('.idx').textContent = i + 1;
            card.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/^rows\[[^\]]*\]/, 'rows[' + i + ']');
            });
        });
    }

    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
})();
</script>
<?php admin_foot();
