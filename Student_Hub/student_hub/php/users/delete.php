<?php
declare(strict_types=1);

/* =========================================================
   DELETE USER  -  confirmed before execution
   GET  : shows who will be deleted and asks "Are you sure?"
   POST : runs DELETE only after the admin presses "Yes"
   Guards: you cannot delete your own account.
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';

$id   = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$myId = (int) $_SESSION['user_id'];

$user = $id > 0 ? fetch_user($conn, $id) : null;
if (!$user) {
    flash('error', 'User not found. It may have been deleted already.');
    header('Location: index.php');
    exit;
}
if ($id === $myId) {
    flash('error', 'You cannot delete the account you are logged in with.');
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
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 1) {
            audit_log($conn, 'DELETE', 'user', $id, 'Deleted user "' . $user['full_name'] . '" (' . $user['email'] . ', role: ' . $user['role'] . ')');
            flash('success', 'User "' . $user['full_name'] . '" was deleted successfully.');
        } else {
            flash('error', 'No record was deleted.');
        }
    } catch (mysqli_sql_exception $ex) {
        error_log('User delete failed: ' . $ex->getMessage());
        flash('error', 'Could not delete the user. Please try again.'
            . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : ''));
    }
    header('Location: index.php');
    exit;
}

users_header('Delete User');
?>

<section class="crud-head"><h2>Delete User</h2></section>

<div class="crud-confirm">
    <p class="crud-confirm-title">Are you sure you want to delete this user?</p>
    <dl>
        <dt>Name</dt>       <dd><?= e($user['full_name']) ?></dd>
        <dt>Email</dt>      <dd><?= e($user['email']) ?></dd>
        <dt>Student ID</dt> <dd><?= e($user['student_id'] ?? '-') ?></dd>
        <dt>Role</dt>       <dd><?= e(ucfirst($user['role'])) ?></dd>
    </dl>
    <p class="crud-warning">This action cannot be undone.</p>

    <form method="post" action="delete.php" class="crud-actions-row">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
        <button type="submit" class="btn crud-btn-danger">Yes, delete</button>
        <a class="btn btn-outline" href="index.php">No, cancel</a>
    </form>
</div>

<?php users_footer();
