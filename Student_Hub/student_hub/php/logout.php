<?php
// End the current Student Hub session and return to the login page.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// remember who is leaving (for the audit log), then end the session
$leavingId   = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$leavingName = (string) ($_SESSION['full_name'] ?? '');
$leavingRole = isset($_SESSION['role']) ? (string) $_SESSION['role'] : null;

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $cookie['path'],
        $cookie['domain'],
        $cookie['secure'],
        $cookie['httponly']
    );
}

session_destroy();

if ($leavingId !== null) {
    require_once __DIR__ . '/db_mysqli.php';
    require_once __DIR__ . '/audit.php';
    audit_log($conn, 'LOGOUT', 'session', $leavingId, ucfirst((string) $leavingRole) . ' logged out', $leavingId, $leavingName, $leavingRole);
}

header('Location: ../pages/login.html');
exit;
