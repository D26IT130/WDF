<?php
declare(strict_types=1);

/* =========================================================
   AUDIT LOGGING
   audit_log($conn, 'DELETE', 'student', 12, 'Deleted student "X"');
   Records WHO (user id, name, role), WHAT (action, entity, id, details),
   WHEN and FROM WHERE (IP). It never throws: a logging problem must
   not break the admin action that was just completed.
   Never put passwords or tokens in $details.
   ========================================================= */

function audit_log(
    mysqli $conn,
    string $action,
    string $entity,
    ?int $entityId = null,
    string $details = '',
    ?int $userId = null,
    ?string $userName = null,
    ?string $userRole = null
): void {
    try {
        $userId   ??= isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $userName ??= (string) ($_SESSION['full_name'] ?? 'Guest');
        $userRole ??= isset($_SESSION['role']) ? (string) $_SESSION['role'] : null;
        $ip        = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $userName  = mb_substr($userName, 0, 100);
        $details   = mb_substr($details, 0, 255);

        $stmt = $conn->prepare(
            'INSERT INTO audit_logs (user_id, user_name, user_role, action, entity, entity_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issssiss', $userId, $userName, $userRole, $action, $entity, $entityId, $details, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('audit_log failed: ' . $e->getMessage());
    }
}
