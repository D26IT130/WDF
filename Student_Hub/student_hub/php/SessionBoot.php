<?php
declare(strict_types=1);

/* =========================================================
   SESSION BOOTSTRAP (Practical 10)
   - secure cookie settings
   - idle timeout
   - destroy_session() helper
   ========================================================= */

// Seconds of inactivity before the user is logged out.
// 900 = 15 minutes. Set it to 60 while taking your "timeout" screenshot.
const SESSION_IDLE_TIMEOUT = 900;

function destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

$GLOBALS['session_expired'] = false;

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');    // server refuses session IDs it did not create
    ini_set('session.use_only_cookies', '1');   // never accept the ID from the URL
    session_set_cookie_params([
        'lifetime' => 0,                        // ends when the browser closes
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,                     // JavaScript cannot read the cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Idle timeout: compare "now" with the last request of this session
if (isset($_SESSION['user_id'])) {
    $last = (int)($_SESSION['last_activity'] ?? time());
    if (time() - $last > SESSION_IDLE_TIMEOUT) {
        destroy_session();
        session_start();                        // fresh, empty session for the rest of this request
        $GLOBALS['session_expired'] = true;
    } else {
        $_SESSION['last_activity'] = time();
    }
}