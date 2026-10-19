<?php
declare(strict_types=1);

/* =========================================================
   DELETE STUDENT  -  two-step, confirmed before execution
   Step 1 (GET)  : show the student and ask "Are you sure?"
   Step 2 (POST) : run DELETE only after the admin confirms
   DELETE is never run from a plain link, so a crawler or a
   forged link cannot remove records.
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$student = $id > 0 ? fetch_student($conn, $id) : null;
if (!$student) {
    flash('error', 'Student not found. It may have been deleted already.');
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
        $stmt = $conn->prepare('DELETE FROM students WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 1) {
            audit_log($conn, 'DELETE', 'student', $id, 'Deleted student "' . $student['name'] . '" (' . $student['email'] . ')');
            flash('success', 'Student "' . $student['name'] . '" was deleted successfully.');
        } else {
            flash('error', 'No record was deleted.');
        }
    } catch (mysqli_sql_exception $ex) {
        error_log('Student delete failed: ' . $ex->getMessage());
        flash('error', 'Could not delete the student. Please try again.'
            . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : ''));
    }
    header('Location: index.php');
    exit;
}

render_header('Delete Student');
?>

<section class="crud-head">
    <h2>Delete Student</h2>
</section>

<div class="crud-confirm">
    <p class="crud-confirm-title">Are you sure you want to delete this student?</p>
    <dl>
        <dt>Name</dt>   <dd><?= e($student['name']) ?></dd>
        <dt>Email</dt>  <dd><?= e($student['email']) ?></dd>
        <dt>Mobile</dt> <dd><?= e($student['mobile']) ?></dd>
        <dt>Course</dt> <dd><?= e($student['course_name']) ?> (Year <?= (int) $student['year'] ?>)</dd>
    </dl>
    <p class="crud-warning">This action cannot be undone.</p>

    <form method="post" action="delete.php" class="crud-actions-row">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
        <button type="submit" class="btn crud-btn-danger">Yes, delete</button>
        <a class="btn btn-outline" href="index.php">No, cancel</a>
    </form>
</div>

<?php render_footer();
