<?php
declare(strict_types=1);

/* =========================================================
   DELETE EVENT  -  confirmed before execution
   GET  : shows the event and asks "Are you sure?"
   POST : DELETE row, then remove its poster file
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$ev = $id > 0 ? fetch_event($conn, $id) : null;

if (!$ev) {
    flash('error', 'Event not found. It may have been deleted already.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }

    try {
        /* ---------- DELETE ---------- */
        $stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 1) {
            delete_poster_file($ev['poster']);
            audit_log($conn, 'DELETE', 'event', $id, 'Deleted event "' . $ev['title'] . '"');
            flash('success', 'Event "' . $ev['title'] . '" was deleted successfully.');
        } else {
            flash('error', 'No event was deleted.');
        }
    } catch (mysqli_sql_exception $ex) {
        error_log('Event delete failed: ' . $ex->getMessage());
        flash('error', 'Could not delete the event. Please try again.'
            . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : ''));
    }
    header('Location: index.php');
    exit;
}

events_header('Delete Event');
?>

<h2 class="h3 mb-3">Delete Event</h2>

<div class="card shadow-sm" style="max-width:640px">
  <div class="card-body">
    <p class="fw-semibold fs-5">Are you sure you want to delete this event?</p>
    <div class="d-flex gap-3 mb-3">
        <?php if ($ev['poster']): ?>
            <img class="poster-thumb" style="width:90px;height:90px" src="<?= e(POSTER_URL . $ev['poster']) ?>" alt="Poster">
        <?php endif; ?>
        <div>
            <div class="fw-semibold"><?= e($ev['title']) ?></div>
            <div><?= e(date('d M Y', strtotime($ev['event_date']))) ?>, <?= e(date('g:i A', strtotime($ev['event_time']))) ?></div>
            <div><?= e($ev['location']) ?> &middot; <?= e($ev['category']) ?> &middot; <?= status_badge($ev['status']) ?></div>
        </div>
    </div>
    <p class="text-danger fw-semibold">This action cannot be undone. The poster file will be deleted too.</p>

    <form method="post" action="delete.php" class="d-flex gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
        <button type="submit" class="btn btn-danger">Yes, delete</button>
        <a class="btn btn-outline-secondary" href="index.php">No, cancel</a>
    </form>
  </div>
</div>

<?php events_footer();
