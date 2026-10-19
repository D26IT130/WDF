<?php
declare(strict_types=1);

/* =========================================================
   AUDIT LOG VIEWER (admins only)
   Filter by action / entity, search text, paginate. Read-only:
   there is deliberately no way to edit or delete log rows here.
   ========================================================= */

require_once __DIR__ . '/_bootstrap.php';

const AUDIT_PER_PAGE = 15;

$q      = trim((string) ($_GET['q'] ?? ''));
$action = (string) ($_GET['action'] ?? '');
$entity = (string) ($_GET['entity'] ?? '');
$page   = max(1, (int) ($_GET['page'] ?? 1));

$w = [];
// choices for the dropdowns come from the data itself
$actions  = array_column(admin_rows($conn, 'SELECT DISTINCT action FROM audit_logs ORDER BY action', $w, 'audit_logs'), 'action');
$entities = array_column(admin_rows($conn, 'SELECT DISTINCT entity FROM audit_logs ORDER BY entity', $w, 'audit_logs'), 'entity');
if (!in_array($action, $actions, true)) { $action = ''; }
if (!in_array($entity, $entities, true)) { $entity = ''; }

$where = []; $types = ''; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(user_name LIKE ? OR details LIKE ? OR ip_address LIKE ?)';
    $types  .= 'sss';
    array_push($params, $like, $like, $like);
}
if ($action !== '') { $where[] = 'action = ?'; $types .= 's'; $params[] = $action; }
if ($entity !== '') { $where[] = 'entity = ?'; $types .= 's'; $params[] = $entity; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = 0; $rows = []; $pages = 1;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM audit_logs $whereSql");
    if ($params) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $pages = max(1, (int) ceil($total / AUDIT_PER_PAGE));
    if ($page > $pages) { $page = $pages; }
    $limit  = AUDIT_PER_PAGE;
    $offset = ($page - 1) * AUDIT_PER_PAGE;

    $stmt = $conn->prepare(
        "SELECT id, user_name, user_role, action, entity, entity_id, details, ip_address, created_at
         FROM audit_logs $whereSql ORDER BY id DESC LIMIT ? OFFSET ?"
    );
    $bind = [...$params, $limit, $offset];
    $stmt->bind_param($types . 'ii', ...$bind);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('Audit log view failed: ' . $ex->getMessage());
    $w['audit_logs'] = $ex->getMessage();
}

function audit_url(int $p): string
{
    global $q, $action, $entity;
    return 'audit_logs.php?' . http_build_query(array_filter(
        ['q' => $q, 'action' => $action, 'entity' => $entity, 'page' => $p],
        fn($v) => $v !== '' && $v !== null
    ));
}

admin_header('Audit Logs', '../../');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Audit Logs</h1>
        <div class="text-secondary"><?= $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?> found</div>
    </div>
    <a class="btn btn-outline-secondary" href="../../pages/admin-dashboard.php">&laquo; Dashboard</a>
</div>

<?php if ($w): ?>
    <div class="alert alert-warning" role="alert">
        The audit_logs table could not be read. Import <code>php/admin_dashboard_schema.sql</code> in phpMyAdmin.
    </div>
<?php endif; ?>

<form class="card card-body shadow-sm mb-3" method="get" action="audit_logs.php">
    <div class="row g-3 align-items-end">
        <div class="col-lg-5">
            <label class="form-label" for="q">Search (user, details or IP)</label>
            <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="action">Action</label>
            <select class="form-select" id="action" name="action">
                <option value="">All</option>
                <?php foreach ($actions as $a): ?><option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="entity">Entity</label>
            <select class="form-select" id="entity" name="entity">
                <option value="">All</option>
                <?php foreach ($entities as $en): ?><option value="<?= e($en) ?>" <?= $entity === $en ? 'selected' : '' ?>><?= e($en) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn btn-outline-secondary" href="audit_logs.php">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>When</th><th>Who</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">No log entries match.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="text-secondary"><?= (int) $r['id'] ?></td>
                    <td class="text-nowrap"><?= e(date('d M Y, g:i:s A', strtotime($r['created_at']))) ?></td>
                    <td><?= e($r['user_name']) ?><?= $r['user_role'] ? ' <span class="text-secondary small">(' . e($r['user_role']) . ')</span>' : '' ?></td>
                    <td><?= action_badge($r['action']) ?></td>
                    <td><?= e($r['entity']) ?><?= $r['entity_id'] !== null ? ' #' . (int) $r['entity_id'] : '' ?></td>
                    <td><?= e($r['details']) ?></td>
                    <td class="small text-secondary"><?= e($r['ip_address'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pages > 1): ?>
<nav class="mt-3" aria-label="Audit pages">
  <ul class="pagination justify-content-center flex-wrap">
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(audit_url(max(1, $page - 1))) ?>">&laquo; Prev</a></li>
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e(audit_url($i)) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(audit_url(min($pages, $page + 1))) ?>">Next &raquo;</a></li>
  </ul>
</nav>
<?php endif; ?>

<?php admin_footer();
