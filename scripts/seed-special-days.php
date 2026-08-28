<?php
/**
 * One-off seed for the new "Special Day of the Month" section, added in
 * includes/collections.php. Run this once against the live/dev Atlas
 * database before using the admin editor at admin/edit.php?c=special_days —
 * admin/edit.php only ever updates an existing `sections` document, it does
 * not create one, so without this the section heading has nothing to load.
 *
 * Safe to re-run: the `sections` document is upserted by its natural key,
 * and the 12 placeholder rows are only inserted if special_day_items is
 * still empty (so it never duplicates rows after the admin starts editing).
 *
 * Usage:
 *   MONGODB_URI=... MONGODB_DB=temple php scripts/seed-special-days.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

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

$client = new Client(env('MONGODB_URI'));
$db     = $client->selectDatabase(env('MONGODB_DB', 'temple'));

$db->selectCollection('sections')->updateOne(
    ['_id' => 'special_days'],
    ['$setOnInsert' => [
        '_id'      => 'special_days',
        'title_ta' => 'மாத விசேஷ நாள்',
        'title_en' => 'Special Day of the Month',
    ]],
    ['upsert' => true]
);
echo "sections['special_days'] ready.\n";

$items = $db->selectCollection('special_day_items');
if ($items->countDocuments() === 0) {
    $months = [
        ['சித்திரை', 'Chithirai (Apr-May)'],
        ['வைகாசி', 'Vaikasi (May-Jun)'],
        ['ஆனி', 'Aani (Jun-Jul)'],
        ['ஆடி', 'Aadi (Jul-Aug)'],
        ['ஆவணி', 'Aavani (Aug-Sep)'],
        ['புரட்டாசி', 'Purattasi (Sep-Oct)'],
        ['ஐப்பசி', 'Aippasi (Oct-Nov)'],
        ['கார்த்திகை', 'Karthigai (Nov-Dec)'],
        ['மார்கழி', 'Margazhi (Dec-Jan)'],
        ['தை', 'Thai (Jan-Feb)'],
        ['மாசி', 'Masi (Feb-Mar)'],
        ['பங்குனி', 'Panguni (Mar-Apr)'],
    ];

    $rows = [];
    foreach ($months as $i => [$ta, $en]) {
        $rows[] = [
            'month_ta'   => $ta,
            'month_en'   => $en,
            'date_label' => '',
            'title_ta'   => '',
            'title_en'   => '',
            'body_ta'    => '',
            'body_en'    => '',
            'sort_order' => ($i + 1) * 10,
        ];
    }
    $items->insertMany($rows);
    echo 'Inserted ' . count($rows) . " placeholder month rows into special_day_items.\n";
} else {
    echo "special_day_items already has rows - left untouched.\n";
}
