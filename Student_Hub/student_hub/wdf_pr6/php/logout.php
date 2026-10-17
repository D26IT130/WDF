<?php
// End the current Student Hub session and return to the login page.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

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
header('Location: ../pages/login.html');
exit;
