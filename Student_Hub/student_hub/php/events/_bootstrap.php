<?php
declare(strict_types=1);

/* =========================================================
   EVENTS MODULE - shared helpers
   Reuses admin check, MySQLi $conn, flash, CSRF and e()
   from the students module bootstrap.
   ========================================================= */

require_once __DIR__ . '/../students/_bootstrap.php';
require_once __DIR__ . '/../menu.php';

const EVENT_CATEGORIES = ['Workshop', 'Competition', 'Seminar', 'Exhibition'];
const EVENT_STATUSES   = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'];
const EVENTS_PER_PAGE  = 6;

/* ---------- poster upload rules ---------- */
const POSTER_MAX_BYTES = 2 * 1024 * 1024;                       // 2 MB
const POSTER_TYPES     = [                                      // real MIME type => extension
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
const POSTER_DIR = __DIR__ . '/../../uploads/events/';          // on disk
const POSTER_URL = '../../uploads/events/';                     // from php/events/*.php

function fetch_event(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, title, description, event_date, event_time, location, category, status, poster
         FROM events WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Validate the text fields. Returns [cleanData, errors[]]. */
function validate_event(array $in): array
{
    $d = [
        'title'       => trim((string) ($in['title'] ?? '')),
        'description' => trim((string) ($in['description'] ?? '')),
        'event_date'  => trim((string) ($in['event_date'] ?? '')),
        'event_time'  => trim((string) ($in['event_time'] ?? '')),
        'location'    => trim((string) ($in['location'] ?? '')),
        'category'    => trim((string) ($in['category'] ?? '')),
        'status'      => trim((string) ($in['status'] ?? '')),
    ];
    $err = [];

    if ($d['title'] === '') {
        $err['title'] = 'Title is required.';
    } elseif (mb_strlen($d['title']) > 150) {
        $err['title'] = 'Title must be at most 150 characters.';
    }

    if ($d['description'] === '') {
        $err['description'] = 'Description is required.';
    } elseif (mb_strlen($d['description']) > 2000) {
        $err['description'] = 'Description must be at most 2000 characters.';
    }

    $dt = DateTime::createFromFormat('!Y-m-d', $d['event_date']);
    if (!$dt || $dt->format('Y-m-d') !== $d['event_date']) {
        $err['event_date'] = 'Enter a valid date.';
    }

    $tm = DateTime::createFromFormat('!H:i', substr($d['event_time'], 0, 5));
    if (!$tm || $tm->format('H:i') !== substr($d['event_time'], 0, 5)) {
        $err['event_time'] = 'Enter a valid time.';
    } else {
        $d['event_time'] = $tm->format('H:i:s');
    }

    if ($d['location'] === '') {
        $err['location'] = 'Location is required.';
    } elseif (mb_strlen($d['location']) > 150) {
        $err['location'] = 'Location must be at most 150 characters.';
    }

    if (!in_array($d['category'], EVENT_CATEGORIES, true)) {
        $err['category'] = 'Select a valid category.';
    }
    if (!in_array($d['status'], EVENT_STATUSES, true)) {
        $err['status'] = 'Select a valid status.';
    }

    return [$d, $err];
}

/**
 * Check the uploaded poster WITHOUT saving it.
 * Returns [tmpPath|null, extension|null, error|null].
 *   - no file chosen      -> [null, null, null]   (poster is optional)
 *   - problem with file   -> [null, null, 'message']
 */
function check_poster(?array $file): array
{
    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null, null];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return [null, null, 'Poster is too large. Maximum size is 2 MB.'];
        default:
            return [null, null, 'The poster could not be uploaded. Please try again.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, null, 'Invalid upload.'];
    }

    // 1) size (checked again on the server, never trust the browser)
    if ($file['size'] > POSTER_MAX_BYTES) {
        return [null, null, 'Poster is too large. Maximum size is 2 MB.'];
    }
    if ($file['size'] === 0) {
        return [null, null, 'The poster file is empty.'];
    }

    // 2) file extension typed by the user
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return [null, null, 'Only JPG, PNG or WEBP images are allowed.'];
    }

    // 3) the REAL type, read from the file contents (not from the file name)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset(POSTER_TYPES[$mime])) {
        return [null, null, 'Only JPG, PNG or WEBP images are allowed.'];
    }

    // 4) it must really be a decodable image
    if (@getimagesize($file['tmp_name']) === false) {
        return [null, null, 'The file is not a valid image.'];
    }

    return [$file['tmp_name'], POSTER_TYPES[$mime], null];
}

/** Move a checked upload into uploads/events/ under a random name. Returns file name or null. */
function save_poster(string $tmpPath, string $ext): ?string
{
    if (!is_dir(POSTER_DIR) && !mkdir(POSTER_DIR, 0755, true)) {
        return null;
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;     // user-supplied name is never used
    return move_uploaded_file($tmpPath, POSTER_DIR . $name) ? $name : null;
}

/** Delete a stored poster file (only names we generated ourselves are accepted). */
function delete_poster_file(?string $name): void
{
    if ($name && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name)) {
        $path = POSTER_DIR . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function status_badge(string $status): string
{
    $map = ['Upcoming' => 'primary', 'Ongoing' => 'success', 'Completed' => 'secondary', 'Cancelled' => 'danger'];
    return '<span class="badge text-bg-' . ($map[$status] ?? 'secondary') . '">' . e($status) . '</span>';
}

function events_header(string $title): void
{
    $flash = pull_flash();
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
    body.dark-theme .btn-outline-secondary { color: #cbd5e1; border-color: #64748b; }
    body.dark-theme code { color: #f9a8d4; }
    .poster-thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; }
    .poster-preview { max-width: 260px; max-height: 260px; border-radius: 8px; }
    .poster-none { width: 56px; height: 56px; border-radius: 6px; background: #e2e8f0; color: #64748b;
                   display: flex; align-items: center; justify-content: center; font-size: .7rem; }
</style>
</head>
<body class="bg-body-tertiary">

<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
  <div class="container">
    <a class="navbar-brand brand" href="../../pages/index.html">Student Hub</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?= menu_render('../../', 'bootstrap') ?>
        <li class="nav-item ms-lg-2"><button type="button" class="btn btn-sm btn-outline-secondary" id="adminThemeToggle" aria-label="Toggle dark theme">Dark theme</button></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">
<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= e($flash['text']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif;
}

function events_footer(): void
{
    ?>
</main>
<footer class="text-center text-secondary small py-4">&copy; 2026 Student Hub. All rights reserved.</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
