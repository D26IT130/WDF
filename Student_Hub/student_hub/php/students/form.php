<?php
declare(strict_types=1);

/* =========================================================
   ADD / EDIT STUDENT  -  INSERT and UPDATE (MySQLi prepared)
   form.php        -> add
   form.php?id=5   -> edit student 5
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';

$id     = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;

$courses   = fetch_courses($conn);
$courseIds = array_map(fn($c) => (int) $c['course_id'], $courses);

$data   = ['name' => '', 'email' => '', 'mobile' => '', 'course_id' => 0, 'year' => 0, 'gender' => ''];
$errors = [];

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $existing = fetch_student($conn, $id);
    if (!$existing) {
        flash('error', 'Student not found. It may have been deleted already.');
        header('Location: index.php');
        exit;
    }
    $data = array_intersect_key($existing, $data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }

    [$data, $errors] = validate_student($_POST, $courseIds);

    if (!$errors) {
        try {
            if ($isEdit) {
                /* ---------- UPDATE ---------- */
                $stmt = $conn->prepare(
                    'UPDATE students
                     SET name = ?, email = ?, mobile = ?, course_id = ?, year = ?, gender = ?
                     WHERE id = ?'
                );
                $stmt->bind_param(
                    'sssiisi',
                    $data['name'], $data['email'], $data['mobile'],
                    $data['course_id'], $data['year'], $data['gender'], $id
                );
                $stmt->execute();
                $stmt->close();
                audit_log($conn, 'UPDATE', 'student', $id, 'Updated student "' . $data['name'] . '" (' . $data['email'] . ')');
                flash('success', 'Student "' . $data['name'] . '" was updated successfully.');
            } else {
                /* ---------- INSERT ---------- */
                $stmt = $conn->prepare(
                    'INSERT INTO students (name, email, mobile, course_id, year, gender)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'sssiis',
                    $data['name'], $data['email'], $data['mobile'],
                    $data['course_id'], $data['year'], $data['gender']
                );
                $stmt->execute();
                $stmt->close();
                audit_log($conn, 'CREATE', 'student', (int) $conn->insert_id, 'Added student "' . $data['name'] . '" (' . $data['email'] . ')');
                flash('success', 'Student "' . $data['name'] . '" was added successfully.');
            }
            header('Location: index.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ((int) $ex->getCode() === 1062) {          // duplicate email (UNIQUE key)
                $errors['email'] = 'This email is already used by another student.';
            } else {
                error_log('Student save failed: ' . $ex->getMessage());
                $errors['_form'] = 'Could not save the record. Please try again.'
                    . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : '');
            }
        }
    }
}

$title = $isEdit ? 'Edit Student' : 'Add Student';
render_header($title);

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<small class="crud-error">' . e($errors[$key]) . '</small>' : '';
}
?>

<section class="crud-head">
    <h2><?= e($title) ?></h2>
    <a class="btn btn-outline" href="index.php">&laquo; Back to list</a>
</section>

<?php if (isset($errors['_form'])): ?>
    <div class="crud-alert crud-alert-error" role="alert"><?= e($errors['_form']) ?></div>
<?php elseif ($errors): ?>
    <div class="crud-alert crud-alert-error" role="alert">Please fix the highlighted fields and submit again.</div>
<?php endif; ?>

<form class="crud-form" method="post" action="form.php<?= $isEdit ? '?id=' . $id : '' ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <div class="crud-field">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" maxlength="100" required value="<?= e($data['name']) ?>">
        <?= field_error($errors, 'name') ?>
    </div>

    <div class="crud-field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" maxlength="150" required value="<?= e($data['email']) ?>">
        <?= field_error($errors, 'email') ?>
    </div>

    <div class="crud-field">
        <label for="mobile">Mobile (10 digits)</label>
        <input type="text" id="mobile" name="mobile" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" required value="<?= e($data['mobile']) ?>">
        <?= field_error($errors, 'mobile') ?>
    </div>

    <div class="crud-field">
        <label for="course_id">Course</label>
        <select id="course_id" name="course_id" required>
            <option value="">Select course</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= (int) $c['course_id'] ?>" <?= (int) $data['course_id'] === (int) $c['course_id'] ? 'selected' : '' ?>>
                    <?= e($c['course_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'course_id') ?>
    </div>

    <div class="crud-field">
        <label for="year">Year</label>
        <select id="year" name="year" required>
            <option value="">Select year</option>
            <?php foreach (YEARS as $y): ?>
                <option value="<?= $y ?>" <?= (int) $data['year'] === $y ? 'selected' : '' ?>>Year <?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'year') ?>
    </div>

    <div class="crud-field">
        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
            <option value="">Select gender</option>
            <?php foreach (GENDERS as $g): ?>
                <option value="<?= e($g) ?>" <?= $data['gender'] === $g ? 'selected' : '' ?>><?= e($g) ?></option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'gender') ?>
    </div>

    <div class="crud-field crud-actions">
        <button class="btn" type="submit"><?= $isEdit ? 'Update Student' : 'Add Student' ?></button>
        <a class="btn btn-outline" href="index.php">Cancel</a>
    </div>
</form>

<?php render_footer();
