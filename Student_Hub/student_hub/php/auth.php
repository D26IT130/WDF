<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/** Web path of pages/login.html that works from any folder (php/, php/students/, pages/ ...). */
function login_url(): string
{
    $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
    $project = str_replace('\\', '/', dirname(__DIR__));
    if ($docRoot !== '' && stripos($project, $docRoot) === 0) {
        return substr($project, strlen($docRoot)) . '/pages/login.html';
    }
    return '../pages/login.html';   // fallback
}

function requireRole(string|array $requiredRole): void
{
    if (
        !isset($_SESSION['user_id'], $_SESSION['role'])
    ) {
        header('Location: ' . login_url());
        exit;
    }

    $allowed = (array) $requiredRole;
    // admins may open every page; other users need one of the allowed roles
    if ($_SESSION['role'] !== 'admin' && !in_array($_SESSION['role'], $allowed, true)) {
        http_response_code(403);
        exit('Access denied. You do not have permission to view this page.');
    }
}
