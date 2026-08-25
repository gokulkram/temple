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
        $gallery = db()->selectCollection('gallery_photos');
        $top     = $gallery->find([], ['sort' => ['sort_order' => -1], 'limit' => 1])->toArray();
        $next    = $top ? (int) $top[0]['sort_order'] : 0;

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
            $gallery->insertOne([
                'filename'    => $res['filename'],
                'label'       => 'புதிய படம்',
                'caption'     => 'புதிய படம் / New photo',
                'sort_order'  => $next,
                'uploaded_at' => new \MongoDB\BSON\UTCDateTime(),
            ]);
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

    $gallery = db()->selectCollection('gallery_photos');
    try {
        $submitted = $_POST['rows'];
        $order = 0;

        foreach ($submitted as $r) {
            if (!is_array($r)) { continue; }
            $rawId = is_scalar($r['id'] ?? null) ? trim((string) $r['id']) : '';
            if (!preg_match('/^[a-f0-9]{24}$/i', $rawId)) { continue; }
            $order += 10;
            $gallery->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($rawId)],
                ['$set' => ['label' => post_str($r, 'label'), 'caption' => post_str($r, 'caption'), 'sort_order' => $order]]
            );
        }

        // Deletion is explicit: the browser names the photos it removed.
        // Never inferred from absence - a malformed POST would otherwise wipe
        // the whole gallery and unlink every file.
        $toDelete = [];
        foreach ((array) ($_POST['deleted'] ?? []) as $d) {
            if (is_scalar($d) && preg_match('/^[a-f0-9]{24}$/i', trim((string) $d))) {
                $toDelete[] = new \MongoDB\BSON\ObjectId(trim((string) $d));
            }
        }

        $orphans = [];
        if ($toDelete) {
            foreach ($gallery->find(['_id' => ['$in' => $toDelete]], ['projection' => ['filename' => 1]]) as $doc) {
                $orphans[] = $doc['filename'];
            }
            $gallery->deleteMany(['_id' => ['$in' => $toDelete]]);
        }

        // Only unlink a file once no row anywhere still points at it.
        foreach ($orphans as $f) {
            $inUse = $gallery->countDocuments(['filename' => $f]) > 0
                || $f === setting('hero_image');
            if (!$inUse) { delete_image($f); }
        }

        flash('சேமிக்கப்பட்டது. (Gallery saved.)');
    } catch (Throwable $e) {
        error_log('Gallery save failed: ' . $e->getMessage());
        // Each row is its own write now (no cross-collection transaction, unlike
        // admin/edit.php), so a mid-loop failure can leave earlier rows saved -
        // never claim nothing changed here.
        flash('சேமிக்க முடியவில்லை. (Save failed partway through — reload to check what was saved.)', 'err');
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
                        <input type="hidden" name="rows[<?= $i ?>][id]" value="<?= esc($p['id']) ?>">
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
        const id = idInput ? idInput.value : '';
        if (id !== '') {
            const marker = document.createElement('input');
            marker.type = 'hidden';
            marker.name = 'deleted[]';
            marker.value = id;
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
