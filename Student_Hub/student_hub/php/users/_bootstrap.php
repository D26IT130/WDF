<?php
declare(strict_types=1);

require_once __DIR__ . '/../menu.php';

/* =========================================================
   USERS MODULE - shared helpers
   Reuses the admin check, MySQLi $conn, flash, CSRF and e()
   from the students module bootstrap.
   ========================================================= */

require_once __DIR__ . '/../students/_bootstrap.php';

const ROLES         = ['student', 'admin'];
const USERS_PER_PAGE = 8;

function fetch_user(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, full_name, email, student_id, role
         FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Validate user form input.
 * $passwordRequired = true when adding, false when editing (blank = keep old password).
 * Returns [cleanData, errors[]].
 */
function validate_user(array $in, bool $passwordRequired): array
{
    $d = [
        'full_name'  => trim((string) ($in['full_name'] ?? '')),
        'email'      => strtolower(trim((string) ($in['email'] ?? ''))),
        'student_id' => trim((string) ($in['student_id'] ?? '')),
        'role'       => trim((string) ($in['role'] ?? '')),
        'password'   => (string) ($in['password'] ?? ''),   // not trimmed
    ];
    $err = [];

    if ($d['full_name'] === '') {
        $err['full_name'] = 'Full name is required.';
    } elseif (mb_strlen($d['full_name']) > 100) {
        $err['full_name'] = 'Full name must be at most 100 characters.';
    }

    if ($d['email'] === '') {
        $err['email'] = 'Email is required.';
    } elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['email']) > 255) {
        $err['email'] = 'Enter a valid email address.';
    }

    if (!in_array($d['role'], ROLES, true)) {
        $err['role'] = 'Select a valid role.';
    }

    if ($d['student_id'] === '' && $d['role'] === 'student') {
        $err['student_id'] = 'Student ID is required for students.';
    } elseif (mb_strlen($d['student_id']) > 50) {
        $err['student_id'] = 'Student ID must be at most 50 characters.';
    }

    if ($d['password'] !== '' || $passwordRequired) {
        if (mb_strlen($d['password']) < 6) {
            $err['password'] = 'Password must be at least 6 characters.';
        } elseif (strlen($d['password']) > 72) {   // bcrypt only uses the first 72 bytes
            $err['password'] = 'Password must be at most 72 characters.';
        }
    }

    return [$d, $err];
}

function users_header(string $title): void
{
    $flash = pull_flash();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> - Student Hub</title>
<link rel="stylesheet" href="../../css/style1.css">
<link rel="stylesheet" href="../../css/students-crud.css">
</head>
<body>

<header>
    <div class="logo"><h1>Student Hub</h1></div>
    <button class="hamburger" id="hamburger" type="button" aria-label="Open navigation menu">
        <span></span><span></span><span></span>
    </button>
    <nav id="mainNav">
        <ul>
            <?= menu_render('../../') ?>
        </ul>
    </nav>
</header>

<main class="crud-main">
<?php if ($flash): ?>
    <div class="crud-alert crud-alert-<?= e($flash['type']) ?>" role="alert">
        <?= e($flash['text']) ?>
    </div>
<?php endif;
}

function users_footer(): void
{
    ?>
</main>

<footer>
    <p>&copy; 2026 Student Hub. All rights reserved.</p>
</footer>
<script src="../../js/script.js"></script>
</body>
</html>
<?php
}
