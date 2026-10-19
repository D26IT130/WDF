<?php
declare(strict_types=1);

/* =========================================================
   EVENTS LIST  -  SELECT with search, filters, pagination
   Search : title / location / description
   Filter : category, status
   Also contains the quick "change status" form per row.
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';

$q        = trim((string) ($_GET['q'] ?? ''));
$category = (string) ($_GET['category'] ?? '');
$status   = (string) ($_GET['status'] ?? '');
$page     = max(1, (int) ($_GET['page'] ?? 1));
if (!in_array($category, EVENT_CATEGORIES, true)) { $category = ''; }
if (!in_array($status, EVENT_STATUSES, true))     { $status = ''; }

$where = []; $types = ''; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(title LIKE ? OR location LIKE ? OR description LIKE ?)';
    $types  .= 'sss';
    array_push($params, $like, $like, $like);
}
if ($category !== '') { $where[] = 'category = ?'; $types .= 's'; $params[] = $category; }
if ($status !== '')   { $where[] = 'status = ?';   $types .= 's'; $params[] = $status; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$events = []; $total = 0; $pages = 1; $dbError = null;

try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM events $whereSql");
    if ($params) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $pages = max(1, (int) ceil($total / EVENTS_PER_PAGE));
    if ($page > $pages) { $page = $pages; }
    $limit  = EVENTS_PER_PAGE;
    $offset = ($page - 1) * EVENTS_PER_PAGE;

    $stmt = $conn->prepare(
        "SELECT id, title, event_date, event_time, location, category, status, poster
         FROM events $whereSql
         ORDER BY event_date DESC, event_time DESC
         LIMIT ? OFFSET ?"
    );
    $bind = [...$params, $limit, $offset];
    $stmt->bind_param($types . 'ii', ...$bind);
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('Event list failed: ' . $ex->getMessage());
    $dbError = 'Could not load events. Please try again.'
        . ($mysqliDebug ? ' (' . $ex->getMessage() . ')' : '');
}

function page_url(int $p): string
{
    global $q, $category, $status;
    return 'index.php?' . http_build_query(array_filter(
        ['q' => $q, 'category' => $category, 'status' => $status, 'page' => $p],
        fn($v) => $v !== '' && $v !== null
    ));
}

events_header('Manage Events');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="h3 mb-0">Event Management</h2>
        <div class="text-secondary"><?= $total ?> event<?= $total === 1 ? '' : 's' ?> found</div>
    </div>
    <a class="btn btn-primary" href="form.php">+ Add Event</a>
</div>

<?php if ($dbError): ?>
    <div class="alert alert-danger" role="alert"><?= e($dbError) ?></div>
<?php endif; ?>

<form class="card card-body shadow-sm mb-3" method="get" action="index.php">
    <div class="row g-3 align-items-end">
        <div class="col-lg-5">
            <label class="form-label" for="q">Search (title, location or description)</label>
            <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. workshop, Seminar Hall">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="category">Category</label>
            <select class="form-select" id="category" name="category">
                <option value="">All</option>
                <?php foreach (EVENT_CATEGORIES as $c): ?>
                    <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All</option>
                <?php foreach (EVENT_STATUSES as $s): ?>
                    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="index.php">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Poster</th><th>Event</th><th>Date &amp; time</th><th>Location</th>
                <th>Category</th><th style="min-width:210px">Status</th><th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$events): ?>
            <tr><td colspan="7" class="text-center text-secondary py-4">No events match your search or filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($events as $ev): ?>
            <tr>
                <td>
                    <?php if ($ev['poster']): ?>
                        <img class="poster-thumb" src="<?= e(POSTER_URL . $ev['poster']) ?>" alt="Poster for <?= e($ev['title']) ?>">
                    <?php else: ?>
                        <div class="poster-none">No poster</div>
                    <?php endif; ?>
                </td>
                <td class="fw-semibold"><?= e($ev['title']) ?></td>
                <td><?= e(date('d M Y', strtotime($ev['event_date']))) ?><br>
                    <small class="text-secondary"><?= e(date('g:i A', strtotime($ev['event_time']))) ?></small></td>
                <td><?= e($ev['location']) ?></td>
                <td><?= e($ev['category']) ?></td>
                <td>
                    <form class="d-flex gap-1 align-items-center" method="post" action="status.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
                        <input type="hidden" name="return" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
                        <select class="form-select form-select-sm" name="status" aria-label="Status">
                            <?php foreach (EVENT_STATUSES as $s): ?>
                                <option value="<?= e($s) ?>" <?= $ev['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" type="submit">Set</button>
                    </form>
                </td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary" href="form.php?id=<?= (int) $ev['id'] ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-danger" href="delete.php?id=<?= (int) $ev['id'] ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
  </div>
</div>

<?php if ($pages > 1): ?>
<nav class="mt-3" aria-label="Event pages">
  <ul class="pagination justify-content-center flex-wrap">
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(page_url(max(1, $page - 1))) ?>">&laquo; Prev</a></li>
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e(page_url($i)) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(page_url(min($pages, $page + 1))) ?>">Next &raquo;</a></li>
  </ul>
</nav>
<?php endif; ?>

<?php events_footer();
