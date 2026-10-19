<?php
declare(strict_types=1);

/* =========================================================
   CHANGE EVENT STATUS (Upcoming / Ongoing / Completed / Cancelled)
   POST only, CSRF protected.
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
if (!csrf_valid()) {
    http_response_code(400);
    exit('Invalid or expired form token. Go back, refresh the page and try again.');
}

$id     = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
$back   = 'index.php' . (($_POST['return'] ?? '') !== '' ? '?' . preg_replace('/[^A-Za-z0-9_=&%+.\-]/', '', (string) $_POST['return']) : '');

if ($id <= 0 || !in_array($status, EVENT_STATUSES, true)) {
    flash('error', 'Invalid status change request.');
    header('Location: ' . $back);
    exit;
}

try {
    $old = fetch_event($conn, $id);
    $before = $old['status'] ?? '?';
    $stmt = $conn->prepare('UPDATE events SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $stmt->close();

    $ev = fetch_event($conn, $id);
    if ($ev) {
        audit_log($conn, 'STATUS_CHANGE', 'event', $id, 'Status of "' . $ev['title'] . '" changed from ' . $before . ' to ' . $status);
        flash('success', 'Status of "' . $ev['title'] . '" is now ' . $status . '.');
    } else {
        flash('error', 'Event not found.');
    }
} catch (mysqli_sql_exception $ex) {
    error_log('Event status failed: ' . $ex->getMessage());
    flash('error', 'Could not change the status. Please try again.'
        . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : ''));
}

header('Location: ' . $back);
exit;
