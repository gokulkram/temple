<?php
/**
 * Single shared PDO connection.
 *
 * Every query in this project goes through prepared statements. There is no
 * place where a value is concatenated into SQL.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST, DB_PORT, DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Real prepared statements, not client-side interpolation.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Never leak credentials or SQL to the visitor.
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        exit('The site is temporarily unavailable. Please try again shortly.');
    }

    return $pdo;
}

/** Fetch every row of a table in display order. */
function all(string $table, string $order = 'sort_order'): array
{
    // $table and $order are never user input - they are literals in this codebase.
    return db()->query("SELECT * FROM `$table` ORDER BY `$order` ASC")->fetchAll();
}

/** All settings as a flat key => value map. */
function settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT skey, svalue FROM settings') as $r) {
            $cache[$r['skey']] = $r['svalue'];
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
        foreach (db()->query('SELECT skey, title_ta, title_en FROM sections') as $r) {
            $cache[$r['skey']] = $r;
        }
    }
    return $cache;
}
