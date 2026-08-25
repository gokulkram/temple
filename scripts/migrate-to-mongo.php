<?php
/**
 * One-off migration: copy live data from the Railway MySQL database into
 * MongoDB Atlas. Not part of the deployed app - run manually, once, from a
 * machine that can reach both databases.
 *
 * Usage:
 *   MYSQLHOST=... MYSQLPORT=... MYSQLDATABASE=... MYSQLUSER=... MYSQLPASSWORD=... \
 *   MONGODB_URI=... MONGODB_DB=temple \
 *   php scripts/migrate-to-mongo.php
 *
 * Re-running it is safe for the fixed-id collections (settings, sections,
 * users - upserted by their natural key) but NOT for the list collections:
 * those are appended via insertMany, so a second run duplicates every row.
 * Drop the target database in Atlas first if you need to re-run from scratch.
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;

function env(string $key, ?string $default = null): string
{
    $v = getenv($key);
    if ($v === false || $v === '') {
        if ($default !== null) {
            return $default;
        }
        fwrite(STDERR, "Missing required env var: $key\n");
        exit(1);
    }
    return $v;
}

// ---- source: MySQL ----
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    env('MYSQLHOST'), env('MYSQLPORT', '3306'), env('MYSQLDATABASE')
);
$pdo = new PDO($dsn, env('MYSQLUSER'), env('MYSQLPASSWORD'), [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// ---- destination: MongoDB ----
$client = new Client(env('MONGODB_URI'));
$db     = $client->selectDatabase(env('MONGODB_DB', 'temple'));

function to_utc(?string $mysqlDatetime): ?UTCDateTime
{
    if ($mysqlDatetime === null) {
        return null;
    }
    // MySQL stores these naive (no offset), but Railway/PHP write them in
    // UTC - strtotime() would instead interpret the string in this
    // machine's local timezone and silently shift it.
    $dt = new DateTimeImmutable($mysqlDatetime, new DateTimeZone('UTC'));
    return new UTCDateTime($dt->getTimestamp() * 1000);
}

$counts = [];

// ---- settings: PK skey -> _id ----
$settings = $db->selectCollection('settings');
$n = 0;
foreach ($pdo->query('SELECT skey, svalue, notes FROM settings') as $r) {
    $settings->replaceOne(
        ['_id' => $r['skey']],
        ['_id' => $r['skey'], 'svalue' => $r['svalue'], 'notes' => $r['notes']],
        ['upsert' => true]
    );
    $n++;
}
$counts['settings'] = $n;

// ---- sections: PK skey -> _id ----
$sections = $db->selectCollection('sections');
$n = 0;
foreach ($pdo->query('SELECT skey, title_ta, title_en FROM sections') as $r) {
    $sections->replaceOne(
        ['_id' => $r['skey']],
        ['_id' => $r['skey'], 'title_ta' => $r['title_ta'], 'title_en' => $r['title_en']],
        ['upsert' => true]
    );
    $n++;
}
$counts['sections'] = $n;

// ---- users ----
$users = $db->selectCollection('users');
$n = 0;
foreach ($pdo->query('SELECT * FROM users') as $r) {
    $users->replaceOne(
        ['username' => $r['username']],
        [
            'username'        => $r['username'],
            'password_hash'   => $r['password_hash'],
            'failed_attempts' => (int) $r['failed_attempts'],
            'locked_until'    => to_utc($r['locked_until']),
            'last_login'      => to_utc($r['last_login']),
            'created_at'      => to_utc($r['created_at']) ?? new UTCDateTime(),
        ],
        ['upsert' => true]
    );
    $n++;
}
$counts['users'] = $n;

// ---- the flat "list" tables: same shape, straight copy ----
$listTables = [
    'nav_links', 'about_paragraphs', 'about_rows', 'history_entries',
    'deity_cards', 'festival_items', 'location_items', 'visit_cards',
];
foreach ($listTables as $table) {
    $coll = $db->selectCollection($table);
    $rows = $pdo->query("SELECT * FROM `$table` ORDER BY sort_order ASC")->fetchAll();
    foreach ($rows as &$row) {
        unset($row['id']);
        // PDO can return numeric columns as strings depending on driver
        // settings; BSON sorts by type before value, so a stray string
        // sort_order would scramble display order until the row is re-saved.
        $row['sort_order'] = (int) $row['sort_order'];
    }
    unset($row);
    if ($rows) {
        $coll->insertMany($rows);
    }
    $counts[$table] = count($rows);
}

// ---- gallery_photos: same shape + uploaded_at needs converting ----
$gallery = $db->selectCollection('gallery_photos');
$rows = $pdo->query('SELECT * FROM gallery_photos ORDER BY sort_order ASC')->fetchAll();
foreach ($rows as &$row) {
    unset($row['id']);
    $row['sort_order']  = (int) $row['sort_order'];
    $row['uploaded_at'] = to_utc($row['uploaded_at']) ?? new UTCDateTime();
}
unset($row);
if ($rows) {
    $gallery->insertMany($rows);
}
$counts['gallery_photos'] = count($rows);

echo "Migration complete. Document counts:\n";
foreach ($counts as $collection => $count) {
    printf("  %-20s %d\n", $collection, $count);
}
