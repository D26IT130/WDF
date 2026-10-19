<?php
declare(strict_types=1);

/* =========================================================
   USERS LIST  -  SELECT on the users table
   Search : full name / email / student ID
   Filter : role
   Pagination via COUNT(*) + LIMIT/OFFSET
   password_hash is never selected or displayed.
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';

$q    = trim((string) ($_GET['q'] ?? ''));
$role = (string) ($_GET['role'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
if (!in_array($role, ROLES, true)) { $role = ''; }

$where = []; $types = ''; $params = [];

if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)';
    $types  .= 'sss';
    array_push($params, $like, $like, $like);
}
if ($role !== '') {
    $where[] = 'role = ?';
    $types  .= 's';
    $params[] = $role;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$users = []; $total = 0; $pages = 1; $dbError = null;

try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users $whereSql");
    if ($params) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $pages = max(1, (int) ceil($total / USERS_PER_PAGE));
    if ($page > $pages) { $page = $pages; }
    $offset = ($page - 1) * USERS_PER_PAGE;
    $limit  = USERS_PER_PAGE;

    $stmt = $conn->prepare(
        "SELECT id, full_name, email, student_id, role
         FROM users $whereSql
         ORDER BY id DESC
         LIMIT ? OFFSET ?"
    );
    $bindValues = [...$params, $limit, $offset];
    $stmt->bind_param($types . 'ii', ...$bindValues);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('User list failed: ' . $ex->getMessage());
    $dbError = 'Could not load users. Please try again.'
        . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : '');
}

function list_url(array $override = []): string
{
    global $q, $role, $page;
    $p = array_merge(['q' => $q, 'role' => $role, 'page' => $page], $override);
    return 'index.php?' . http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null));
}

$myId = (int) $_SESSION['user_id'];

users_header('Manage Users');
?>

<section class="crud-head">
    <div>
        <h2>User Accounts</h2>
        <p class="crud-sub"><?= $total ?> user<?= $total === 1 ? '' : 's' ?> found</p>
    </div>
    <a class="btn" href="form.php">+ Add User</a>
</section>

<?php if ($dbError): ?>
    <div class="crud-alert crud-alert-error" role="alert"><?= e($dbError) ?></div>
<?php endif; ?>

<form class="crud-filters" method="get" action="index.php">
    <div class="crud-field crud-field-wide">
        <label for="q">Search (name, email or student ID)</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. priya, @example.com, ST2026005">
    </div>
    <div class="crud-field">
        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="">All roles</option>
            <?php foreach (ROLES as $r): ?>
                <option value="<?= e($r) ?>" <?= $role === $r ? 'selected' : '' ?>><?= e(ucfirst($r)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="crud-field crud-actions">
        <button class="btn" type="submit">Search</button>
        <a class="btn btn-outline" href="index.php">Reset</a>
    </div>
</form>

<div class="crud-table-wrap">
    <table class="crud-table">
        <thead>
            <tr><th>ID</th><th>Full name</th><th>Email</th><th>Student ID</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$users): ?>
            <tr><td colspan="6" class="crud-empty">No users match your search or filter.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
            <tr>
                <td data-label="ID"><?= (int) $u['id'] ?></td>
                <td data-label="Full name"><?= e($u['full_name']) ?><?= (int) $u['id'] === $myId ? ' (you)' : '' ?></td>
                <td data-label="Email"><?= e($u['email']) ?></td>
                <td data-label="Student ID"><?= e($u['student_id'] ?? '-') ?></td>
                <td data-label="Role"><?= e(ucfirst($u['role'])) ?></td>
                <td data-label="Actions" class="crud-row-actions">
                    <a class="crud-link" href="form.php?id=<?= (int) $u['id'] ?>">Edit</a>
                    <?php if ((int) $u['id'] !== $myId): ?>
                        <a class="crud-link crud-link-danger" href="delete.php?id=<?= (int) $u['id'] ?>">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
<nav class="crud-pagination" aria-label="User pages">
    <?php if ($page > 1): ?><a href="<?= e(list_url(['page' => $page - 1])) ?>">&laquo; Prev</a><?php endif; ?>
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= e(list_url(['page' => $i])) ?>" class="<?= $i === $page ? 'active' : '' ?>"
           <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $pages): ?><a href="<?= e(list_url(['page' => $page + 1])) ?>">Next &raquo;</a><?php endif; ?>
</nav>
<?php endif; ?>

<?php users_footer();
