<?php
/**
 * Generic section editor.
 *
 * All seven list tables share the same shape, so one page driven by
 * includes/collections.php replaces seven near-identical CRUD screens.
 *
 * Rows are submitted as rows[<table>][<n>][<field>]. The submission order is
 * the display order, so dragging a card and saving is what reorders it.
 * A row the admin deleted is simply absent from the submission, and any
 * database row whose id did not come back is removed.
 */
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';
require_login();

$key = (string) ($_GET['c'] ?? '');
$col = collection($key);
if (!$col) {
    http_response_code(404);
    exit('Unknown section.');
}

// ---------------------------------------------------------------- save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mongo   = db();
    $session = null;

    try {
        $session = $mongo->getManager()->startSession();
        $session->startTransaction();

        // Section heading, if this collection has one. Only touch it when both
        // fields actually arrived and are non-empty - a heading is never
        // legitimately blank, and a partial POST must not erase it.
        if (!empty($col['section'])
            && isset($_POST['section_title_ta'], $_POST['section_title_en'])) {
            $ta = post_str($_POST, 'section_title_ta');
            $en = post_str($_POST, 'section_title_en');
            if ($ta !== '' && $en !== '') {
                $mongo->selectCollection('sections')->updateOne(
                    ['_id' => $col['section']],
                    ['$set' => ['title_ta' => $ta, 'title_en' => $en]],
                    ['session' => $session]
                );
            }
        }

        // Each rendered list posts a marker naming itself. Without this, a
        // truncated or malformed POST would look identical to "the admin
        // deleted every row", and the delete below would empty the table.
        $present = array_flip((array) ($_POST['lists'] ?? []));

        foreach ($col['lists'] as $table => $def) {
            if (!isset($present[$table])) {
                continue;   // this list never reached us; leave it untouched
            }

            $submitted = $_POST['rows'][$table] ?? [];
            if (!is_array($submitted)) {
                $submitted = [];   // malformed; treat as "nothing to update"
            }
            $names   = array_column($def['fields'], 'name');
            $types   = array_column($def['fields'], 'type', 'name');
            $order   = 0;
            $coll    = $mongo->selectCollection($table);

            foreach ($submitted as $row) {
                if (!is_array($row)) {
                    continue;
                }

                // The id decides which row gets overwritten, so validate it
                // rather than casting: an empty string means "new row", and
                // anything else must be a well-formed 24-char ObjectId hex
                // string - never trusted as-is.
                $rawId = $row['id'] ?? '';
                if (!is_scalar($rawId)) {
                    continue;                       // malformed row, skip it
                }
                $rawId = trim((string) $rawId);
                if ($rawId !== '' && !preg_match('/^[a-f0-9]{24}$/i', $rawId)) {
                    continue;
                }

                $values  = [];
                $badType = false;
                foreach ($names as $n) {
                    $raw = $row[$n] ?? '';
                    if (!is_string($raw)) {
                        // An array where a string belongs means a tampered or
                        // broken form. Skip the row rather than storing ''.
                        $badType = true;
                        break;
                    }
                    $raw = trim($raw);
                    // Fields that may carry markup are run through the
                    // allowlist before storage as well as on output.
                    $values[$n] = (($types[$n] ?? 'text') === 'rich')
                        ? clean_html($raw)
                        : $raw;
                }
                if ($badType) {
                    continue;
                }

                // Skip a row the admin added but left completely blank.
                if (implode('', $values) === '') {
                    continue;
                }

                $order += 10;
                $values['sort_order'] = $order;

                if ($rawId !== '') {
                    $coll->updateOne(
                        ['_id' => new \MongoDB\BSON\ObjectId($rawId)],
                        ['$set' => $values],
                        ['session' => $session]
                    );
                } else {
                    $coll->insertOne($values, ['session' => $session]);
                }
            }

            // Deletion is EXPLICIT, never inferred from a row being absent.
            //
            // Inferring it meant any malformed or truncated POST - a stray
            // string where an array belonged, a dropped field - looked exactly
            // like "the admin deleted everything", and the table was emptied.
            // Now the browser names the ids it deleted, so the worst a broken
            // submission can do is fail to save an edit.
            $toDelete = [];
            foreach ((array) ($_POST['deleted'][$table] ?? []) as $d) {
                if (is_scalar($d) && preg_match('/^[a-f0-9]{24}$/i', trim((string) $d))) {
                    $toDelete[] = new \MongoDB\BSON\ObjectId(trim((string) $d));
                }
            }
            if ($toDelete) {
                $coll->deleteMany(['_id' => ['$in' => $toDelete]], ['session' => $session]);
            }
        }

        $session->commitTransaction();
        flash('சேமிக்கப்பட்டது. (Saved.)');
    } catch (Throwable $e) {
        if ($session && $session->isInTransaction()) {
            try {
                $session->abortTransaction();
            } catch (Throwable $ignored) {
                // The original $e above is what gets logged/flashed either way.
            }
        }
        error_log('Save failed: ' . $e->getMessage());
        flash('சேமிக்க முடியவில்லை. (Could not save — nothing was changed.)', 'err');
    } finally {
        $session?->endSession();
    }

    redirect('edit.php?c=' . urlencode($key));
}

// ---------------------------------------------------------------- render
$section = !empty($col['section']) ? (sections()[$col['section']] ?? null) : null;

admin_head($col['label']);
?>
<div class="wrap">
    <h1><?= esc($col['icon']) ?> <?= esc($col['label']) ?></h1>
    <p class="sub">Edit the text below and press Save. Drag ⠿ to reorder, and use Delete to remove an item.</p>

    <?php show_flash(); ?>

    <form method="post" id="editor">
        <?= csrf_field() ?>

        <?php if ($section): ?>
            <h2>பகுதி தலைப்பு / Section heading</h2>
            <div class="panel">
                <?php
                field(['label' => 'தலைப்பு (தமிழ்)', 'type' => 'text', 'required' => true],
                      'section_title_ta', $section['title_ta']);
                field(['label' => 'Title (English)', 'type' => 'text', 'required' => true],
                      'section_title_en', $section['title_en']);
                ?>
            </div>
        <?php endif; ?>

        <?php foreach ($col['lists'] as $table => $def):
            $rows = all($table); ?>
            <h2><?= esc($def['label']) ?> <span style="font-weight:400;color:var(--muted)">(<?= count($rows) ?>)</span></h2>

            <!-- Tells the server this list really was rendered and submitted. -->
            <input type="hidden" name="lists[]" value="<?= esc($table) ?>">

            <div class="list" data-table="<?= esc($table) ?>">
                <?php foreach ($rows as $i => $r): ?>
                    <div class="panel item" draggable="true">
                        <div class="panel-head">
                            <span class="drag" title="Drag to reorder">⠿</span>
                            <span class="idx"><?= $i + 1 ?></span>
                            <span class="ttl"><?= esc(mb_strimwidth(strip_tags((string) $r[$def['summary']]), 0, 70, '…')) ?></span>
                            <button type="button" class="btn danger small js-del">நீக்கு / Delete</button>
                        </div>
                        <input type="hidden" name="rows[<?= esc($table) ?>][<?= $i ?>][id]" value="<?= esc($r['id']) ?>">
                        <?php foreach ($def['fields'] as $f) {
                            field($f, "rows[$table][$i][{$f['name']}]", $r[$f['name']] ?? '');
                        } ?>
                    </div>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <p class="empty">இன்னும் உருப்படிகள் இல்லை. (No items yet — use Add below.)</p>
                <?php endif; ?>
            </div>

            <!-- Blank row cloned by the Add button. __i__ is renumbered on insert. -->
            <template data-tpl="<?= esc($table) ?>">
                <div class="panel item" draggable="true">
                    <div class="panel-head">
                        <span class="drag" title="Drag to reorder">⠿</span>
                        <span class="idx">•</span>
                        <span class="ttl">புதிய உருப்படி / New item</span>
                        <button type="button" class="btn danger small js-del">நீக்கு / Delete</button>
                    </div>
                    <input type="hidden" name="rows[<?= esc($table) ?>][__i__][id]" value="">
                    <?php foreach ($def['fields'] as $f) {
                        field($f, "rows[$table][__i__][{$f['name']}]", '');
                    } ?>
                </div>
            </template>

            <button type="button" class="btn ghost js-add" data-table="<?= esc($table) ?>">
                + சேர் / Add <?= esc($def['label']) ?>
            </button>
        <?php endforeach; ?>

        <div class="actions">
            <button class="btn" type="submit">சேமி / Save changes</button>
            <a class="btn ghost" href="index.php">திரும்பு / Back</a>
            <span class="spacer"></span>
            <a class="btn ghost" href="../index.php" target="_blank" rel="noopener">தளத்தைப் பார் ↗</a>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('editor');

    // ---- delete ----
    form.addEventListener('click', function (e) {
        const del = e.target.closest('.js-del');
        if (del) {
            const panel = del.closest('.panel.item');
            if (!confirm('இந்த உருப்படியை நீக்கவா? (Delete this item?)')) return;

            // Name the row we are deleting. The server deletes only what is
            // listed here - it never infers deletion from a missing row.
            const idInput = panel.querySelector('input[name$="[id]"]');
            const id = idInput ? idInput.value : '';
            const table = panel.closest('.list').dataset.table;
            if (id !== '') {
                const marker = document.createElement('input');
                marker.type = 'hidden';
                marker.name = 'deleted[' + table + '][]';
                marker.value = id;
                form.appendChild(marker);
            }

            panel.remove();
            renumber();
            return;
        }

        // ---- add ----
        const add = e.target.closest('.js-add');
        if (add) {
            const table = add.dataset.table;
            const tpl = form.querySelector('template[data-tpl="' + CSS.escape(table) + '"]');
            const list = form.querySelector('.list[data-table="' + CSS.escape(table) + '"]');
            const node = tpl.content.firstElementChild.cloneNode(true);
            list.querySelector('.empty')?.remove();
            list.appendChild(node);
            renumber();
            node.querySelector('input:not([type=hidden]), textarea, select')?.focus();
        }
    });

    // ---- drag to reorder ----
    let dragged = null;
    form.addEventListener('dragstart', function (e) {
        const panel = e.target.closest('.panel.item');
        if (!panel) return;
        dragged = panel;
        panel.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    form.addEventListener('dragend', function () {
        dragged?.classList.remove('dragging');
        dragged = null;
        renumber();
    });
    form.addEventListener('dragover', function (e) {
        if (!dragged) return;
        const over = e.target.closest('.panel.item');
        if (!over || over === dragged) return;
        if (over.parentElement !== dragged.parentElement) return;   // same list only
        e.preventDefault();
        const rect = over.getBoundingClientRect();
        const after = (e.clientY - rect.top) > rect.height / 2;
        over.parentElement.insertBefore(dragged, after ? over.nextSibling : over);
    });

    /**
     * Field names carry the row index, and the index decides sort order on the
     * server. After any add, delete or drag, rewrite them to match the DOM.
     */
    function renumber() {
        form.querySelectorAll('.list').forEach(function (list) {
            const table = list.dataset.table;
            list.querySelectorAll(':scope > .panel.item').forEach(function (panel, i) {
                panel.querySelector('.idx').textContent = i + 1;
                panel.querySelectorAll('[name]').forEach(function (input) {
                    input.name = input.name.replace(
                        new RegExp('^rows\\[' + table.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\]\\[[^\\]]*\\]'),
                        'rows[' + table + '][' + i + ']'
                    );
                });
            });
        });
    }

    // Warn before losing unsaved edits.
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
})();
</script>
<?php admin_foot();
