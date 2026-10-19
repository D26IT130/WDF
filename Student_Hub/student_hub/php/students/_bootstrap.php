<?php
declare(strict_types=1);

/* =========================================================
   STUDENT MODULE - shared bootstrap
   - admin-only access
   - MySQLi connection ($conn)
   - flash messages, CSRF token, escaping, validation
   ========================================================= */

require_once __DIR__ . '/../auth.php';
requireRole('admin');                      // only a logged-in admin may use this module
require_once __DIR__ . '/../db_mysqli.php'; // creates $conn (mysqli)
require_once __DIR__ . '/../audit.php';     // audit_log()

const GENDERS   = ['Male', 'Female', 'Other'];
const YEARS     = [1, 2, 3, 4];
const PER_PAGE  = 8;

/** Escape for HTML output (prevents XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* ---------- flash messages (success / error shown once) ---------- */
function flash(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function pull_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF protection for every state-changing POST ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/** Load all courses for dropdowns (prepared statement, no user input). */
function fetch_courses(mysqli $conn): array
{
    $stmt = $conn->prepare('SELECT course_id, course_name FROM courses ORDER BY course_name');
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Load one student by id, or null. */
function fetch_student(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        'SELECT s.id, s.name, s.email, s.mobile, s.course_id, c.course_name, s.year, s.gender
         FROM students s
         INNER JOIN courses c ON c.course_id = s.course_id
         WHERE s.id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Validate student form input.
 * Returns [cleanData, errors[]].
 */
function validate_student(array $in, array $validCourseIds): array
{
    $d = [
        'name'      => trim((string) ($in['name'] ?? '')),
        'email'     => strtolower(trim((string) ($in['email'] ?? ''))),
        'mobile'    => trim((string) ($in['mobile'] ?? '')),
        'course_id' => (int) ($in['course_id'] ?? 0),
        'year'      => (int) ($in['year'] ?? 0),
        'gender'    => trim((string) ($in['gender'] ?? '')),
    ];
    $err = [];

    if ($d['name'] === '') {
        $err['name'] = 'Name is required.';
    } elseif (mb_strlen($d['name']) > 100) {
        $err['name'] = 'Name must be at most 100 characters.';
    } elseif (!preg_match("/^[\p{L}][\p{L} .'-]*$/u", $d['name'])) {
        $err['name'] = 'Name may contain only letters, spaces, dots, hyphens and apostrophes.';
    }

    if ($d['email'] === '') {
        $err['email'] = 'Email is required.';
    } elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['email']) > 150) {
        $err['email'] = 'Enter a valid email address.';
    }

    if (!preg_match('/^[0-9]{10}$/', $d['mobile'])) {
        $err['mobile'] = 'Mobile must be exactly 10 digits.';
    }

    if (!in_array($d['course_id'], $validCourseIds, true)) {
        $err['course_id'] = 'Select a valid course.';
    }

    if (!in_array($d['year'], YEARS, true)) {
        $err['year'] = 'Select a valid year.';
    }

    if (!in_array($d['gender'], GENDERS, true)) {
        $err['gender'] = 'Select a valid gender.';
    }

    return [$d, $err];
}
