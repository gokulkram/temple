<?php
/**
 * The generic 7-section editor, as JSON. Mirrors admin/edit.php's save
 * semantics exactly: transaction per request, `lists` marks which tables
 * were actually submitted (an omitted table is left untouched), blank rows
 * are skipped, sort_order is recomputed by 10s from array order, and
 * `deleted` is the only deletion path (explicit validated ObjectIds, never
 * delete-by-absence).
 */
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/collections.php';
require_once __DIR__ . '/../includes/sanitize.php';

use MongoDB\BSON\ObjectId;

api_require_auth();

$key = (string) ($_GET['c'] ?? '');
$col = collection($key);
if (!$col) {
    api_fail('unknown section', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $section = !empty($col['section']) ? (sections()[$col['section']] ?? null) : null;
    $lists = [];
    foreach (array_keys($col['lists']) as $table) {
        $lists[$table] = all($table);
    }
    api_ok(['section' => $section, 'lists' => $lists]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail('method not allowed', 405);
}

$body = api_json_body();
$mongo = db();
$session = null;
$saved = false;

try {
    $session = $mongo->getManager()->startSession();
    $session->startTransaction();

    // Section heading: only touch it when both fields arrived and are
    // non-empty - a heading is never legitimately blank, and a partial
    // request must not erase it.
    if (!empty($col['section'])
        && isset($body['section_title_ta'], $body['section_title_en'])
        && is_string($body['section_title_ta']) && is_string($body['section_title_en'])) {
        $ta = trim($body['section_title_ta']);
        $en = trim($body['section_title_en']);
        if ($ta !== '' && $en !== '') {
            $mongo->selectCollection('sections')->updateOne(
                ['_id' => $col['section']],
                ['$set' => ['title_ta' => $ta, 'title_en' => $en]],
                ['session' => $session]
            );
        }
    }

    // Each list the client is editing must name itself here. A table not
    // named is left untouched - never inferred as "delete everything".
    $present = array_flip(is_array($body['lists'] ?? null) ? $body['lists'] : []);

    foreach ($col['lists'] as $table => $def) {
        if (!isset($present[$table])) {
            continue;
        }

        $submitted = $body['rows'][$table] ?? [];
        if (!is_array($submitted)) {
            $submitted = [];
        }
        $names = array_column($def['fields'], 'name');
        $types = array_column($def['fields'], 'type', 'name');
        $order = 0;
        $coll = $mongo->selectCollection($table);

        foreach ($submitted as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rawId = $row['id'] ?? '';
            if (!is_scalar($rawId)) {
                continue;
            }
            $rawId = trim((string) $rawId);
            if ($rawId !== '' && !preg_match('/^[a-f0-9]{24}$/i', $rawId)) {
                continue;
            }

            $values = [];
            $badType = false;
            foreach ($names as $n) {
                $raw = $row[$n] ?? '';
                if (!is_string($raw)) {
                    $badType = true;
                    break;
                }
                $raw = trim($raw);
                $values[$n] = (($types[$n] ?? 'text') === 'rich') ? clean_html($raw) : $raw;
            }
            if ($badType) {
                continue;
            }

            if (implode('', $values) === '') {
                continue;
            }

            $order += 10;
            $values['sort_order'] = $order;

            if ($rawId !== '') {
                $coll->updateOne(['_id' => new ObjectId($rawId)], ['$set' => $values], ['session' => $session]);
            } else {
                $coll->insertOne($values, ['session' => $session]);
            }
        }

        $toDelete = [];
        foreach ((array) ($body['deleted'][$table] ?? []) as $d) {
            if (is_scalar($d) && preg_match('/^[a-f0-9]{24}$/i', trim((string) $d))) {
                $toDelete[] = new ObjectId(trim((string) $d));
            }
        }
        if ($toDelete) {
            $coll->deleteMany(['_id' => ['$in' => $toDelete]], ['session' => $session]);
        }
    }

    $session->commitTransaction();
    $saved = true;
} catch (Throwable $e) {
    if ($session && $session->isInTransaction()) {
        try {
            $session->abortTransaction();
        } catch (Throwable $ignored) {
        }
    }
    error_log('api/rows.php save failed: ' . $e->getMessage());
} finally {
    // api_ok()/api_fail() below call exit() - PHP skips a pending finally
    // when exit() fires inside try/catch, so endSession() must happen here,
    // not from a call made inside the try/catch above.
    $session?->endSession();
}

if ($saved) {
    api_ok();
}
api_fail('could not save - nothing was changed', 500);
