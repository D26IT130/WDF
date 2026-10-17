
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireRole(string $requiredRole): void
{
    if (
        !isset($_SESSION['user_id'], $_SESSION['role'])
    ) {
        header('Location: ../pages/login.html');
        exit;
    }

    if ($_SESSION['role'] !== $requiredRole) {
        http_response_code(403);
        exit('Access denied. You do not have permission to view this page.');
    }
}
