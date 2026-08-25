<?php
/**
 * Single shared MongoDB connection.
 *
 * Every collection name used here matches the old MySQL table name 1:1 -
 * this schema never had foreign keys, so the port was a straight rename,
 * not a redesign.
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

function db(): \MongoDB\Database
{
    static $client = null;
    if ($client === null) {
        try {
            $client = new \MongoDB\Client(MONGODB_URI);
            // Force a round trip now, not on the first real query, so a bad
            // URI or unreachable cluster fails the same way the old PDO
            // connect-failure path did.
            $client->selectDatabase('admin')->command(['ping' => 1]);
        } catch (\Throwable $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(503);
            exit('The site is temporarily unavailable. Please try again shortly.');
        }
    }
    return $client->selectDatabase(MONGODB_DB);
}

/** Turn a Mongo document into the assoc array shape the rest of the app expects. */
function _doc(\MongoDB\Model\BSONDocument $doc): array
{
    // BSONDocument extends ArrayObject - (array) casting it does not copy
    // its stored fields, so this must go through getArrayCopy() instead.
    $row = $doc->getArrayCopy();
    $row['id'] = (string) $row['_id'];
    unset($row['_id']);
    return $row;
}

/** Fetch every row of a collection in display order. */
function all(string $collection, string $order = 'sort_order'): array
{
    $rows = [];
    foreach (db()->selectCollection($collection)->find([], ['sort' => [$order => 1]]) as $doc) {
        $rows[] = _doc($doc);
    }
    return $rows;
}

/** Number of documents in a collection. */
function row_count(string $collection): int
{
    return db()->selectCollection($collection)->countDocuments();
}

/** All settings as a flat key => value map. */
function settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->selectCollection('settings')->find() as $r) {
            $cache[$r['_id']] = $r['svalue'];
        }
    }
    return $cache;
}

/** One setting, with a fallback if the key is missing. */
function setting(string $key, string $default = ''): string
{
    return settings()[$key] ?? $default;
}

/** Section headings (about, history, ...) as key => ['title_ta','title_en']. */
function sections(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->selectCollection('sections')->find() as $r) {
            $cache[$r['_id']] = ['title_ta' => $r['title_ta'], 'title_en' => $r['title_en']];
        }
    }
    return $cache;
}
