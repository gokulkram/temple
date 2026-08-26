<?php
/**
 * Shared entrypoint for every api/*.php file: JSON in, JSON out, no
 * sessions/cookies (see includes/api_auth.php for why). Every api/ script
 * starts with require_once __DIR__ . '/_bootstrap.php'.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/api_response.php';
require_once __DIR__ . '/../includes/api_auth.php';

header('Content-Type: application/json; charset=utf-8');
