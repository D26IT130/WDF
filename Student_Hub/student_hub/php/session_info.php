<?php
// Tells the navbar script who is logged in. Returns only non-sensitive data.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'loggedIn' => isset($_SESSION['user_id'], $_SESSION['role']),
    'role'     => $_SESSION['role'] ?? null,
    'name'     => $_SESSION['full_name'] ?? null,
]);
