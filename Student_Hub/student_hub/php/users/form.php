<?php
declare(strict_types=1);

/* =========================================================
   ADD / EDIT USER  -  INSERT and UPDATE on the users table
   form.php        -> add
   form.php?id=5   -> edit user 5 (blank password = keep old one)
   Passwords are stored with password_hash(), never as plain text.
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';

$id     = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;
$myId   = (int) $_SESSION['user_id'];
$isSelf = $isEdit && $id === $myId;

$data   = ['full_name' => '', 'email' => '', 'student_id' => '', 'role' => 'student', 'password' => ''];
$errors = [];

if ($isEdit) {
    $existing = fetch_user($conn, $id);
    if (!$existing) {
        flash('error', 'User not found. It may have been deleted already.');
        header('Location: index.php');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $data = array_merge($data, array_map(fn($v) => (string) ($v ?? ''), array_intersect_key($existing, $data)));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }

    $input = $_POST;
    if ($isSelf) {
        $input['role'] = $existing['role'];   // an admin cannot change their own role
    }

    [$data, $errors] = validate_user($input, !$isEdit);

    if (!$errors) {
        $studentId = $data['student_id'] === '' ? null : $data['student_id'];
        try {
            if ($isEdit) {
                /* ---------- UPDATE ---------- */
                if ($data['password'] !== '') {
                    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare(
                        'UPDATE users SET full_name = ?, email = ?, student_id = ?, role = ?, password_hash = ?
                         WHERE id = ?'
                    );
                    $stmt->bind_param('sssssi', $data['full_name'], $data['email'], $studentId, $data['role'], $hash, $id);
                } else {
                    $stmt = $conn->prepare(
                        'UPDATE users SET full_name = ?, email = ?, student_id = ?, role = ?
                         WHERE id = ?'
                    );
                    $stmt->bind_param('ssssi', $data['full_name'], $data['email'], $studentId, $data['role'], $id);
                }
                $stmt->execute();
                $stmt->close();
                audit_log($conn, 'UPDATE', 'user', $id, 'Updated user "' . $data['full_name'] . '" (role: ' . $data['role'] . ')' . ($data['password'] !== '' ? ', password changed' : ''));
                flash('success', 'User "' . $data['full_name'] . '" was updated successfully.');
            } else {
                /* ---------- INSERT ---------- */
                $hash = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    'INSERT INTO users (full_name, email, student_id, password_hash, role)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('sssss', $data['full_name'], $data['email'], $studentId, $hash, $data['role']);
                $stmt->execute();
                $stmt->close();
                audit_log($conn, 'CREATE', 'user', (int) $conn->insert_id, 'Added user "' . $data['full_name'] . '" (role: ' . $data['role'] . ')');
                flash('success', 'User "' . $data['full_name'] . '" was added successfully.');
            }
            header('Location: index.php');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ((int) $ex->getCode() === 1062) {          // duplicate on a UNIQUE key
                if (stripos($ex->getMessage(), 'student') !== false) {
                    $errors['student_id'] = 'This student ID is already in use.';
                } else {
                    $errors['email'] = 'This email is already registered.';
                }
            } else {
                error_log('User save failed: ' . $ex->getMessage());
                $errors['_form'] = 'Could not save the user. Please try again.'
                    . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : '');
            }
        }
    }
}

$title = $isEdit ? 'Edit User' : 'Add User';
users_header($title);

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
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" maxlength="100" required value="<?= e($data['full_name']) ?>">
        <?= field_error($errors, 'full_name') ?>
    </div>

    <div class="crud-field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" maxlength="255" required value="<?= e($data['email']) ?>">
        <?= field_error($errors, 'email') ?>
    </div>

    <div class="crud-field">
        <label for="student_id">Student ID <?= '(required for students)' ?></label>
        <input type="text" id="student_id" name="student_id" maxlength="50" value="<?= e($data['student_id']) ?>">
        <?= field_error($errors, 'student_id') ?>
    </div>

    <div class="crud-field">
        <label for="role">Role</label>
        <select id="role" name="role" <?= $isSelf ? 'disabled' : '' ?>>
            <?php foreach (ROLES as $r): ?>
                <option value="<?= e($r) ?>" <?= $data['role'] === $r ? 'selected' : '' ?>><?= e(ucfirst($r)) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($isSelf): ?><small class="crud-sub">You cannot change your own role.</small><?php endif; ?>
        <?= field_error($errors, 'role') ?>
    </div>

    <div class="crud-field">
        <label for="password"><?= $isEdit ? 'New password (leave blank to keep current)' : 'Password (min 6 characters)' ?></label>
        <input type="password" id="password" name="password" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
        <?= field_error($errors, 'password') ?>
    </div>

    <div class="crud-field crud-actions">
        <button class="btn" type="submit"><?= $isEdit ? 'Update User' : 'Add User' ?></button>
        <a class="btn btn-outline" href="index.php">Cancel</a>
    </div>
</form>

<?php users_footer();
