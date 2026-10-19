<?php
declare(strict_types=1);

/* =========================================================
   ADMIN DASHBOARD
   - admins only (requireRole('admin') -> 401/403 for everyone else)
   - every number comes from SQL aggregation (COUNT / GROUP BY / JOIN)
   - no JavaScript: bars are Bootstrap progress bars drawn on the server
   ========================================================= */

require_once __DIR__ . '/../php/admin/_bootstrap.php';

$w = [];   // warnings: tables that are missing, etc.

/* ---------- headline numbers ---------- */
$totalUsers    = admin_count($conn, 'SELECT COUNT(*) FROM users', $w, 'users');
$roleRows      = admin_rows($conn, 'SELECT role, COUNT(*) AS n FROM users GROUP BY role ORDER BY n DESC', $w, 'users by role');
$roles         = array_column($roleRows, 'n', 'role');
$studentRecs   = admin_count($conn, 'SELECT COUNT(*) FROM students', $w, 'students');
$totalEvents   = admin_count($conn, 'SELECT COUNT(*) FROM events', $w, 'events');
$totalRegs     = admin_count($conn,
    "SELECT COUNT(*) FROM registrations r
     INNER JOIN users u ON u.id = r.user_id
     INNER JOIN events e ON e.id = r.event_id
     WHERE r.status = 'registered'", $w, 'registrations');

/* ---------- breakdowns ---------- */
$eventsByStatus = admin_rows($conn, 'SELECT status, COUNT(*) AS n FROM events GROUP BY status ORDER BY n DESC', $w, 'events by status');

/* ---------- lists ---------- */
$latestRegs = admin_rows($conn,
    "SELECT u.full_name, u.email, e.title, r.registered_at
     FROM registrations r
     INNER JOIN users u  ON u.id = r.user_id
     INNER JOIN events e ON e.id = r.event_id
     WHERE r.status = 'registered'
     ORDER BY r.registered_at DESC, r.id DESC LIMIT 8", $w, 'latest registrations');

$newUsers = admin_rows($conn,
    'SELECT id, full_name, email, role FROM users ORDER BY id DESC LIMIT 5', $w, 'newest users');

$popular = admin_rows($conn,
    "SELECT e.title, COUNT(*) AS n
     FROM registrations r INNER JOIN events e ON e.id = r.event_id
     WHERE r.status = 'registered'
     GROUP BY e.id, e.title ORDER BY n DESC, e.title LIMIT 5", $w, 'popular events');

$activity = admin_rows($conn,
    'SELECT user_name, action, entity, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 8', $w, 'audit_logs');

function pct(int $part, int $whole): int
{
    return $whole > 0 ? (int) round($part * 100 / $whole) : 0;
}

/** A labelled Bootstrap progress bar. */
function bar(string $label, int $n, int $total, string $color = 'primary'): string
{
    $p = pct($n, $total);
    return '<div class="mb-3"><div class="d-flex justify-content-between small"><span>' . e($label) . '</span>'
         . '<span class="text-secondary">' . $n . ' (' . $p . '%)</span></div>'
         . '<div class="progress" role="progressbar" aria-valuenow="' . $p . '" aria-valuemin="0" aria-valuemax="100" aria-label="' . e($label) . '">'
         . '<div class="progress-bar bg-' . $color . '" style="width:' . $p . '%"></div></div></div>';
}

$statusColors = ['Upcoming' => 'primary', 'Ongoing' => 'success', 'Completed' => 'secondary', 'Cancelled' => 'danger'];
$upcoming = 0;
foreach ($eventsByStatus as $r) { if ($r['status'] === 'Upcoming') { $upcoming = (int) $r['n']; } }

admin_header('Admin Dashboard', '../');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Admin Dashboard</h1>
        <div class="text-secondary">Signed in as <?= e($_SESSION['full_name'] ?? 'Admin') ?> &middot; <?= e(date('d M Y, g:i A')) ?></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary btn-sm" href="../php/events/form.php">+ Event</a>
        <a class="btn btn-outline-primary btn-sm" href="../php/students/form.php">+ Student</a>
        <a class="btn btn-outline-primary btn-sm" href="../php/users/form.php">+ User</a>
    </div>
</div>

<?php if ($w): ?>
    <div class="alert alert-warning" role="alert">
        <strong>Some figures could not be loaded.</strong> The database reported:
        <ul class="mb-1 mt-2">
            <?php foreach ($w as $label => $message): ?>
                <li><strong><?= e($label) ?>:</strong> <code><?= e($message) ?></code></li>
            <?php endforeach; ?>
        </ul>
        A missing table or column usually means a SQL file has not been imported (see <code>php/*.sql</code>).
    </div>
<?php endif; ?>

<!-- ============ headline numbers ============ -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total users',       $totalUsers,  ($roles['student'] ?? 0) . ' students, ' . ($roles['admin'] ?? 0) . ' admins'],
        ['Student records',   $studentRecs, 'in the students table'],
        ['Events',            $totalEvents, $upcoming . ' upcoming'],
        ['Registrations',     $totalRegs,   'event sign-ups'],
    ];
    foreach ($cards as [$label, $value, $note]): ?>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary small"><?= e($label) ?></div>
                <div class="stat-value"><?= (int) $value ?></div>
                <div class="text-secondary small"><?= e($note) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ============ breakdowns ============ -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h2 class="h6 mb-3">Users by role</h2>
            <?php foreach ($roleRows as $r) { echo bar(ucfirst($r['role']), (int) $r['n'], $totalUsers, $r['role'] === 'admin' ? 'danger' : 'primary'); } ?>
            <?php if (!$roleRows): ?><div class="text-secondary small">No data.</div><?php endif; ?>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h2 class="h6 mb-3">Events by status</h2>
            <?php foreach ($eventsByStatus as $r) { echo bar($r['status'], (int) $r['n'], $totalEvents, $statusColors[$r['status']] ?? 'secondary'); } ?>
            <?php if (!$eventsByStatus): ?><div class="text-secondary small">No events yet.</div><?php endif; ?>
        </div></div>
    </div>
</div>

<!-- ============ latest registrations + newest users ============ -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Latest event registrations</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Event</th><th>Registered</th></tr></thead>
                    <tbody>
                    <?php if (!$latestRegs): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-3">No registrations yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($latestRegs as $r): ?>
                        <tr>
                            <td><?= e($r['full_name']) ?></td>
                            <td><?= e($r['email']) ?></td>
                            <td><?= e($r['title']) ?></td>
                            <td class="text-nowrap"><?= e(date('d M Y, g:i A', strtotime($r['registered_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Newest users</div>
            <ul class="list-group list-group-flush">
                <?php if (!$newUsers): ?><li class="list-group-item text-secondary">No users.</li><?php endif; ?>
                <?php foreach ($newUsers as $u): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div><div><?= e($u['full_name']) ?></div><div class="small text-secondary"><?= e($u['email']) ?></div></div>
                        <span class="badge text-bg-<?= $u['role'] === 'admin' ? 'danger' : 'primary' ?>"><?= e(ucfirst($u['role'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<!-- ============ popular events + audit activity ============ -->
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h2 class="h6 mb-3">Most popular events</h2>
            <?php $maxPop = $popular ? (int) $popular[0]['n'] : 0;
            foreach ($popular as $r) { echo bar($r['title'], (int) $r['n'], $maxPop, 'success'); } ?>
            <?php if (!$popular): ?><div class="text-secondary small">No registrations yet.</div><?php endif; ?>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Recent admin activity (audit log)</span>
                <a class="btn btn-sm btn-outline-secondary" href="../php/admin/audit_logs.php">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th></tr></thead>
                    <tbody>
                    <?php if (!$activity): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-3">No activity recorded yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($activity as $a): ?>
                        <tr>
                            <td class="text-nowrap small"><?= e(date('d M, g:i A', strtotime($a['created_at']))) ?></td>
                            <td><?= e($a['user_name']) ?></td>
                            <td><?= action_badge($a['action']) ?></td>
                            <td class="small"><?= e($a['details']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php admin_footer();