<?php
declare(strict_types=1);

/* Admin-only area: requireRole('admin'), MySQLi $conn, e(), audit_log(), role menu */

require_once __DIR__ . '/../students/_bootstrap.php';   // admin check + $conn + e() + flash + CSRF
require_once __DIR__ . '/../menu.php';

/** One prepared query -> all rows. A missing table is reported, not fatal. */
function admin_rows(mysqli $conn, string $sql, ?array &$warnings = null, string $label = ''): array
{
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    } catch (mysqli_sql_exception $ex) {
        error_log("Admin dashboard query ($label) failed: " . $ex->getMessage());
        if ($warnings !== null && $label !== '') {
            $warnings[$label] = $ex->getMessage();
        }
        return [];
    }
}

function admin_count(mysqli $conn, string $sql, ?array &$warnings = null, string $label = ''): int
{
    $rows = admin_rows($conn, $sql, $warnings, $label);
    return $rows ? (int) array_values($rows[0])[0] : 0;
}

/** Bootstrap page shell (no JavaScript is loaded: the menu is plain HTML + CSS). */
function admin_header(string $title, string $root): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> - Student Hub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script>
(function () {
  const saved = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-bs-theme', saved);
  document.documentElement.classList.toggle('dark-theme', saved === 'dark');
})();
</script>
<style>
    .brand { color: #4f46e5 !important; font-weight: 700; }
    .stat-value { font-size: 2rem; font-weight: 700; line-height: 1.1; }
    .stat-card { border-left: 4px solid var(--bs-primary); }
    body { transition: background-color .15s ease, color .15s ease; }
    body.dark-theme { background: #0f172a !important; color: #e2e8f0; }
    body.dark-theme .navbar { background: #111827 !important; border-color: #334155 !important; }
    body.dark-theme .navbar-brand, body.dark-theme .nav-link { color: #e2e8f0 !important; }
    body.dark-theme .card, body.dark-theme .list-group-item { background: #1e293b; color: #e2e8f0; border-color: #334155; }
    body.dark-theme .text-secondary { color: #aab8ca !important; }
    body.dark-theme .table { --bs-table-color: #e2e8f0; --bs-table-bg: #1e293b; --bs-table-border-color: #334155; --bs-table-striped-bg: #243247; --bs-table-hover-bg: #29384d; --bs-table-hover-color: #fff; }
    body.dark-theme .table-light { --bs-table-color: #e2e8f0; --bs-table-bg: #273449; --bs-table-border-color: #3b4a60; }
    body.dark-theme .form-control, body.dark-theme .form-select { background-color: #111827; color: #e2e8f0; border-color: #475569; }
    body.dark-theme .form-control::placeholder { color: #94a3b8; }
    body.dark-theme .form-select option { background: #111827; color: #e2e8f0; }
    body.dark-theme .page-link { background: #1e293b; color: #c7d2fe; border-color: #475569; }
    body.dark-theme .page-item.active .page-link { background: #4f46e5; color: #fff; }
    body.dark-theme .progress { background: #334155; }
    body.dark-theme .btn-outline-secondary { color: #cbd5e1; border-color: #64748b; }
    body.dark-theme code { color: #f9a8d4; }
</style>
</head>
<body class="bg-body-tertiary">

<nav class="navbar navbar-expand flex-wrap bg-white border-bottom shadow-sm">
  <div class="container">
    <a class="navbar-brand brand" href="<?= e($root) ?>pages/index.html">Student Hub</a>
    <ul class="navbar-nav ms-auto flex-wrap align-items-center">
      <?= menu_render($root, 'bootstrap') ?>
      <li class="nav-item ms-lg-2"><button type="button" class="btn btn-sm btn-outline-secondary" id="adminThemeToggle" aria-label="Toggle dark theme">Dark theme</button></li>
    </ul>
  </div>
</nav>

<main class="container py-4">
<?php
}

function admin_footer(): void
{
    ?>
</main>
<footer class="text-center text-secondary small py-4">&copy; 2026 Student Hub. All rights reserved.</footer>
<script>
(function () {
  const button = document.getElementById('adminThemeToggle');
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.body.classList.toggle('dark-theme', theme === 'dark');
    if (button) button.textContent = theme === 'dark' ? 'Light theme' : 'Dark theme';
  }
  applyTheme(localStorage.getItem('theme') || 'light');
  if (button) button.addEventListener('click', function () {
    const next = document.body.classList.contains('dark-theme') ? 'light' : 'dark';
    localStorage.setItem('theme', next);
    applyTheme(next);
  });
})();
</script>
</body>
</html>
<?php
}

function action_badge(string $action): string
{
    $map = ['CREATE' => 'success', 'UPDATE' => 'primary', 'DELETE' => 'danger', 'STATUS_CHANGE' => 'warning',
            'LOGIN' => 'info', 'LOGOUT' => 'secondary', 'LOGIN_FAILED' => 'danger', 'REGISTER' => 'success'];
    return '<span class="badge text-bg-' . ($map[$action] ?? 'secondary') . '">' . e($action) . '</span>';
}
