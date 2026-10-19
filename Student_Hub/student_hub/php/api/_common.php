<?php
declare(strict_types=1);

/* =========================================================
   JSON API - shared helpers
   Every endpoint answers with the same envelope:
     { "success": true|false, "message": "...", "data": ..., "meta": {...}, "errors": {...} }
   Status codes: 200 OK, 201 Created, 400 bad JSON, 401 not logged in,
   403 forbidden / bad CSRF, 404 not found, 405 wrong method,
   409 duplicate, 415 wrong content type, 422 validation, 500 server error.
   ========================================================= */

define('API_MODE', true);

// Never print PHP warnings into a JSON response
ini_set('display_errors', '0');
header_remove('X-Powered-By');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

/** Send the JSON response and stop. */
function api_send(int $status, bool $success, string $message, $data = null, array $extra = []): void
{
    http_response_code($status);
    $payload = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $payload['data'] = $data;
    }
    echo json_encode($payload + $extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_ok(string $message, $data = null, array $extra = [], int $status = 200): void
{
    api_send($status, true, $message, $data, $extra);
}

function api_error(int $status, string $message, array $errors = []): void
{
    api_send($status, false, $message, null, $errors ? ['errors' => $errors] : []);
}

// Any uncaught problem becomes a JSON 500 instead of an HTML error page
set_exception_handler(function (Throwable $e): void {
    error_log('API error: ' . $e->getMessage());
    if (http_response_code() < 400) {
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
});

require_once __DIR__ . '/../auth.php';        // starts the session
require_once __DIR__ . '/../db_mysqli.php';   // $conn (mysqli) + $mysqliDebug
require_once __DIR__ . '/../audit.php';       // audit_log()

/* ---------- request method ---------- */
function api_method(): string
{
    $m = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    // Some servers block PUT/DELETE; allow POST + X-HTTP-Method-Override instead
    if ($m === 'POST' && !empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
        $o = strtoupper((string) $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        if (in_array($o, ['PUT', 'DELETE'], true)) {
            $m = $o;
        }
    }
    return $m;
}

/** Reject methods an endpoint does not support (405 + Allow header). */
function api_allow(array $methods, string $method): void
{
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        api_error(405, "Method $method is not allowed here. Use: " . implode(', ', $methods) . '.');
    }
}

/* ---------- authentication / authorisation ---------- */
function api_require_admin(): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        api_error(401, 'You are not logged in. Please log in again.');
    }
    if ($_SESSION['role'] !== 'admin') {
        api_error(403, 'Only administrators can use this API.');
    }
}

/** Any logged-in user (student or admin). */
function api_require_login(): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        api_error(401, 'Please log in first.');
    }
}

function api_csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** State-changing requests must carry the X-CSRF-Token header. */
function api_require_csrf(): void
{
    $sent = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($sent === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        api_error(403, 'Security token missing or expired. Reload the page and try again.');
    }
}

/** Release the session lock so parallel requests are not queued. */
function api_release_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

/* ---------- request input ---------- */
function api_body(): array
{
    $ct = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (!str_starts_with($ct, 'application/json')) {
        api_error(415, 'Content-Type must be application/json.');
    }
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        api_error(400, 'Request body is empty.');
    }
    if (strlen($raw) > 65536) {
        api_error(413, 'Request body is too large.');
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
        api_error(400, 'Request body is not valid JSON.');
    }
    return $data;
}

/** Positive integer id from ?id= (or null if absent). Invalid -> 400. */
function api_id(): ?int
{
    if (!isset($_GET['id']) || $_GET['id'] === '') {
        return null;
    }
    if (!ctype_digit((string) $_GET['id']) || (int) $_GET['id'] <= 0) {
        api_error(400, 'The id must be a positive whole number.');
    }
    return (int) $_GET['id'];
}

/** Scalar JSON value -> trimmed string (arrays/objects become ''). */
function api_str($value): string
{
    return is_scalar($value) ? trim((string) $value) : '';
}

/** Whole number from JSON/GET input, or 0 when it is not a clean integer. */
function api_int($value): int
{
    if (is_int($value)) {
        return $value;
    }
    return (is_string($value) && preg_match('/^[0-9]+$/', trim($value))) ? (int) trim($value) : 0;
}

/** Pagination numbers from the query string. */
function api_paging(int $defaultPer = 8): array
{
    $page = max(1, api_int($_GET['page'] ?? 1));
    $per  = api_int($_GET['per_page'] ?? $defaultPer);
    $per  = ($per < 1 || $per > 50) ? $defaultPer : $per;
    return [$page, $per];
}

/** LIKE pattern that treats % and _ typed by the user literally. */
function api_like(string $q): string
{
    return '%' . addcslashes($q, '%_\\') . '%';
}

function api_db_error(mysqli_sql_exception $ex, string $what): void
{
    global $mysqliDebug;
    error_log("API $what failed: " . $ex->getMessage());
    api_error(500, "Could not $what. Please try again." . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : ''));
}
