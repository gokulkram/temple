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
// MongoDB Atlas. MONGODB_URI is set as an environment variable on this
// service - see README's Render section. Locally, export it yourself
// (there is no local fallback database - point it at a scratch Atlas
// database instead of production, e.g. one named "temple_dev").
define("MONGODB_URI", getenv("MONGODB_URI") ?: "");
define("MONGODB_DB",  getenv("MONGODB_DB") ?: "temple");

// ---- paths ----
define("IMAGE_DIR",  __DIR__ . "/../images");
define("THUMB_DIR",  IMAGE_DIR . "/thumbs");
define("IMAGE_URL",  "images");

// ---- uploads ----
define("MAX_UPLOAD_BYTES", 5 * 1024 * 1024);   // 5 MB
define("MAX_IMAGE_EDGE",   1600);              // px, longest side
define("THUMB_EDGE",       400);

// ---- sessions / login ----
// Renamed from "temple_admin" on the MongoDB cutover: $_SESSION['user_id']
// changed shape from an int to an ObjectId hex string, so any cookie still
// alive from the MySQL era must be abandoned rather than reused.
define("SESSION_NAME",     "temple_admin_v2");
define("SESSION_IDLE_MAX", 30 * 60);           // seconds before auto-logout
define("LOGIN_MAX_TRIES",  5);
define("LOGIN_LOCK_MINS",  15);