<?php
/**
 * JSON request/response helpers for the api/ layer.
 * Deliberately not shared with admin/ - those pages emit HTML.
 */
declare(strict_types=1);

/** Parse the raw JSON request body. Never returns null - callers get [] on any parse failure. */
function api_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = $raw === false ? null : json_decode($raw, true);
    return is_array($data) ? $data : [];
}

const API_JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

function api_ok(mixed $data = null, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data === null ? ['ok' => true] : ['ok' => true, 'data' => $data], API_JSON_FLAGS);
    exit;
}

function api_fail(string $error, int $status = 400): never
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $error], API_JSON_FLAGS);
    exit;
}
