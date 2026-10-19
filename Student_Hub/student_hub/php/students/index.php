<?php
declare(strict_types=1);

/* =========================================================
   STUDENT LIST  -  SELECT with search, filters, pagination
   Search : name / email / course   (LIKE, one box)
   Filter : course, year, gender
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';

/* ---------- read GET inputs ---------- */
$q        = trim((string) ($_GET['q'] ?? ''));
$courseId = (int) ($_GET['course'] ?? 0);
$year     = (int) ($_GET['year'] ?? 0);
$gender   = (string) ($_GET['gender'] ?? '');
$page     = max(1, (int) ($_GET['page'] ?? 1));

if (!in_array($year, YEARS, true))     { $year = 0; }
if (!in_array($gender, GENDERS, true)) { $gender = ''; }

/* ---------- build WHERE (values are always bound, never concatenated) ---------- */
$where  = [];
$types  = '';
$params = [];

if ($q !== '') {
    // Escape LIKE wildcards typed by the user so "%" and "_" are searched literally
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[]  = '(s.name LIKE ? OR s.email LIKE ? OR c.course_name LIKE ?)';
    $types   .= 'sss';
    array_push($params, $like, $like, $like);
}
if ($courseId > 0) {
    $where[]  = 's.course_id = ?';
    $types   .= 'i';
    $params[] = $courseId;
}
if ($year > 0) {
    $where[]  = 's.year = ?';
    $types   .= 'i';
    $params[] = $year;
}
if ($gender !== '') {
    $where[]  = 's.gender = ?';
    $types   .= 's';
    $params[] = $gender;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$courses = fetch_courses($conn);
$students = [];
$total = 0;
$dbError = null;

try {
    /* ---------- total rows for pagination ---------- */
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM students s
         INNER JOIN courses c ON c.course_id = s.course_id
         $whereSql"
    );
    if ($params) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $pages = max(1, (int) ceil($total / PER_PAGE));
    if ($page > $pages) { $page = $pages; }
    $offset = ($page - 1) * PER_PAGE;

    /* ---------- the current page of rows ---------- */
    $stmt = $conn->prepare(
        "SELECT s.id, s.name, s.email, s.mobile, c.course_name, s.year, s.gender
         FROM students s
         INNER JOIN courses c ON c.course_id = s.course_id
         $whereSql
         ORDER BY s.id DESC
         LIMIT ? OFFSET ?"
    );
    $limit = PER_PAGE;
    $bindValues = [...$params, $limit, $offset];
    $stmt->bind_param($types . 'ii', ...$bindValues);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('Student list failed: ' . $ex->getMessage());
    $dbError = 'Could not load student records. Please try again.'
        . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : '');
    $pages = 1;
}

/** Build a URL that keeps the current search/filters. */
function list_url(array $override = []): string
{
    global $q, $courseId, $year, $gender, $page;
    $p = array_merge(
        ['q' => $q, 'course' => $courseId ?: '', 'year' => $year ?: '', 'gender' => $gender, 'page' => $page],
        $override
    );
    return 'index.php?' . http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null));
}

render_header('Manage Students');
?>

<section class="crud-head">
    <div>
        <h2>Student Records</h2>
        <p class="crud-sub"><?= $total ?> record<?= $total === 1 ? '' : 's' ?> found</p>
    </div>
    <a class="btn" href="form.php">+ Add Student</a>
</section>

<?php if ($dbError): ?>
    <div class="crud-alert crud-alert-error" role="alert"><?= e($dbError) ?></div>
<?php endif; ?>

<form class="crud-filters" method="get" action="index.php">
    <div class="crud-field crud-field-wide">
        <label for="q">Search (name, email or course)</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. priya, @example.com, Computer Science">
    </div>
    <div class="crud-field">
        <label for="course">Course</label>
        <select id="course" name="course">
            <option value="0">All courses</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= (int) $c['course_id'] ?>" <?= $courseId === (int) $c['course_id'] ? 'selected' : '' ?>>
                    <?= e($c['course_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="crud-field">
        <label for="year">Year</label>
        <select id="year" name="year">
            <option value="0">All years</option>
            <?php foreach (YEARS as $y): ?>
                <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>>Year <?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="crud-field">
        <label for="gender">Gender</label>
        <select id="gender" name="gender">
            <option value="">All</option>
            <?php foreach (GENDERS as $g): ?>
                <option value="<?= e($g) ?>" <?= $gender === $g ? 'selected' : '' ?>><?= e($g) ?></option>
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
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Mobile</th>
                <th>Course</th><th>Year</th><th>Gender</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$students): ?>
            <tr><td colspan="8" class="crud-empty">
                No students match your search or filters.
            </td></tr>
        <?php endif; ?>
        <?php foreach ($students as $s): ?>
            <tr>
                <td data-label="ID"><?= (int) $s['id'] ?></td>
                <td data-label="Name"><?= e($s['name']) ?></td>
                <td data-label="Email"><?= e($s['email']) ?></td>
                <td data-label="Mobile"><?= e($s['mobile']) ?></td>
                <td data-label="Course"><?= e($s['course_name']) ?></td>
                <td data-label="Year"><?= (int) $s['year'] ?></td>
                <td data-label="Gender"><?= e($s['gender']) ?></td>
                <td data-label="Actions" class="crud-row-actions">
                    <a class="crud-link" href="form.php?id=<?= (int) $s['id'] ?>">Edit</a>
                    <a class="crud-link crud-link-danger" href="delete.php?id=<?= (int) $s['id'] ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (($pages ?? 1) > 1): ?>
<nav class="crud-pagination" aria-label="Student pages">
    <?php if ($page > 1): ?>
        <a href="<?= e(list_url(['page' => $page - 1])) ?>">&laquo; Prev</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= e(list_url(['page' => $i])) ?>" class="<?= $i === $page ? 'active' : '' ?>"
           <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $pages): ?>
        <a href="<?= e(list_url(['page' => $page + 1])) ?>">Next &raquo;</a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?php render_footer();
