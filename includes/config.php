<?php
/**
 * Database credentials and site-wide constants.
 *
 * SECURITY: this file must never be reachable over HTTP.
 * .htaccess in this folder denies all direct access; on a host without
 * .htaccess support, move this file above the web root instead.
 */
declare(strict_types=1);

// ---- database ----
// On Railway these come from the MySQL plugin's reference variables
// (MYSQLHOST, MYSQLPORT, ...), wired up as env vars on this service - see
// README's Railway section. Locally, with no env vars set, this falls back
// to XAMPP's MySQL on port 3307.
define("DB_HOST", getenv("MYSQLHOST") ?: "127.0.0.1");
define("DB_PORT", getenv("MYSQLPORT") ?: "3307");
define("DB_NAME", getenv("MYSQLDATABASE") ?: "aa");
define("DB_USER", getenv("MYSQLUSER") ?: "root");
define("DB_PASS", getenv("MYSQLPASSWORD") ?: "changeme-set-your-local-mysql-password");

// ---- paths ----
define("IMAGE_DIR",  __DIR__ . "/../images");
define("THUMB_DIR",  IMAGE_DIR . "/thumbs");
define("IMAGE_URL",  "images");

// ---- uploads ----
define("MAX_UPLOAD_BYTES", 5 * 1024 * 1024);   // 5 MB
define("MAX_IMAGE_EDGE",   1600);              // px, longest side
define("THUMB_EDGE",       400);

// ---- sessions / login ----
define("SESSION_NAME",     "temple_admin");
define("SESSION_IDLE_MAX", 30 * 60);           // seconds before auto-logout
define("LOGIN_MAX_TRIES",  5);
define("LOGIN_LOCK_MINS",  15);