<?php
declare(strict_types=1);

/* =========================================================
   STUDENTS API   (php/api/students.php)   - admin only
   GET    ?id=5                                  one student
   GET    ?q=&course=&year=&gender=&page=&per_page=   list
   POST   JSON body                              create
   PUT    ?id=5 + JSON body                      update
   DELETE ?id=5                                  delete
   ========================================================= */

require_once __DIR__ . '/_common.php';

const ST_GENDERS = ['Male', 'Female', 'Other'];
const ST_YEARS   = [1, 2, 3, 4];

function st_out(array $r): array
{
    return [
        'id'          => (int) $r['id'],
        'name'        => $r['name'],
        'email'       => $r['email'],
        'mobile'      => $r['mobile'],
        'course_id'   => (int) $r['course_id'],
        'course_name' => $r['course_name'],
        'year'        => (int) $r['year'],
        'gender'      => $r['gender'],
    ];
}

function st_fetch(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        'SELECT s.id, s.name, s.email, s.mobile, s.course_id, c.course_name, s.year, s.gender
         FROM students s INNER JOIN courses c ON c.course_id = s.course_id
         WHERE s.id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function st_course_ids(mysqli $conn): array
{
    $stmt = $conn->prepare('SELECT course_id FROM courses');
    $stmt->execute();
    $ids = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'course_id'));
    $stmt->close();
    return $ids;
}

/** Validate before any database write. Returns [clean, errors]. */
function st_validate(array $in, array $courseIds): array
{
    $d = [
        'name'      => api_str($in['name'] ?? ''),
        'email'     => strtolower(api_str($in['email'] ?? '')),
        'mobile'    => api_str($in['mobile'] ?? ''),
        'course_id' => api_int($in['course_id'] ?? 0),
        'year'      => api_int($in['year'] ?? 0),
        'gender'    => api_str($in['gender'] ?? ''),
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
    if (!in_array($d['course_id'], $courseIds, true)) {
        $err['course_id'] = 'Select a valid course.';
    }
    if (!in_array($d['year'], ST_YEARS, true)) {
        $err['year'] = 'Select a valid year (1-4).';
    }
    if (!in_array($d['gender'], ST_GENDERS, true)) {
        $err['gender'] = 'Select a valid gender.';
    }
    return [$d, $err];
}

$method = api_method();
api_allow(['GET', 'POST', 'PUT', 'DELETE'], $method);

api_require_admin();                     // student data is private: every method needs admin
if ($method !== 'GET') {
    api_require_csrf();
}
api_release_session();

try {
    /* ---------- GET ---------- */
    if ($method === 'GET') {
        $id = api_id();
        if ($id !== null) {
            $row = st_fetch($conn, $id);
            if (!$row) {
                api_error(404, 'Student not found.');
            }
            api_ok('Student loaded.', st_out($row));
        }

        $q      = api_str($_GET['q'] ?? '');
        $course = api_int($_GET['course'] ?? 0);
        $year   = api_int($_GET['year'] ?? 0);
        $gender = api_str($_GET['gender'] ?? '');
        [$page, $per] = api_paging(8);

        $where = []; $types = ''; $params = [];
        if ($q !== '') {
            $like = api_like($q);
            $where[] = '(s.name LIKE ? OR s.email LIKE ? OR c.course_name LIKE ?)';
            $types  .= 'sss';
            array_push($params, $like, $like, $like);
        }
        if ($course > 0) { $where[] = 's.course_id = ?'; $types .= 'i'; $params[] = $course; }
        if ($year > 0) {
            if (!in_array($year, ST_YEARS, true)) { api_error(422, 'Unknown year filter.'); }
            $where[] = 's.year = ?'; $types .= 'i'; $params[] = $year;
        }
        if ($gender !== '') {
            if (!in_array($gender, ST_GENDERS, true)) { api_error(422, 'Unknown gender filter.'); }
            $where[] = 's.gender = ?'; $types .= 's'; $params[] = $gender;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total FROM students s
             INNER JOIN courses c ON c.course_id = s.course_id $whereSql"
        );
        if ($params) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $total = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $pages = max(1, (int) ceil($total / $per));
        if ($page > $pages) { $page = $pages; }
        $offset = ($page - 1) * $per;

        $stmt = $conn->prepare(
            "SELECT s.id, s.name, s.email, s.mobile, s.course_id, c.course_name, s.year, s.gender
             FROM students s INNER JOIN courses c ON c.course_id = s.course_id
             $whereSql ORDER BY s.id DESC LIMIT ? OFFSET ?"
        );
        $bind = [...$params, $per, $offset];
        $stmt->bind_param($types . 'ii', ...$bind);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        api_ok('Students loaded.', array_map('st_out', $rows), [
            'meta' => ['page' => $page, 'per_page' => $per, 'total' => $total, 'pages' => $pages],
        ]);
    }

    /* ---------- POST: create ---------- */
    if ($method === 'POST') {
        [$d, $errors] = st_validate(api_body(), st_course_ids($conn));
        if ($errors) {
            api_error(422, 'Please correct the highlighted fields.', $errors);
        }

        $stmt = $conn->prepare(
            'INSERT INTO students (name, email, mobile, course_id, year, gender) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssiis', $d['name'], $d['email'], $d['mobile'], $d['course_id'], $d['year'], $d['gender']);
        $stmt->execute();
        $newId = (int) $conn->insert_id;
        $stmt->close();

        audit_log($conn, 'CREATE', 'student', $newId, 'Added student "' . $d['name'] . '" (API)');
        api_ok('Student "' . $d['name'] . '" was added successfully.', st_out(st_fetch($conn, $newId)), [], 201);
    }

    /* ---------- PUT / DELETE need an id ---------- */
    $id = api_id();
    if ($id === null) {
        api_error(400, 'The id parameter is required (e.g. ?id=5).');
    }
    $existing = st_fetch($conn, $id);
    if (!$existing) {
        api_error(404, 'Student not found. It may have been deleted already.');
    }

    /* ---------- PUT: update ---------- */
    if ($method === 'PUT') {
        [$d, $errors] = st_validate(api_body(), st_course_ids($conn));
        if ($errors) {
            api_error(422, 'Please correct the highlighted fields.', $errors);
        }

        $stmt = $conn->prepare(
            'UPDATE students SET name = ?, email = ?, mobile = ?, course_id = ?, year = ?, gender = ? WHERE id = ?'
        );
        $stmt->bind_param('sssiisi', $d['name'], $d['email'], $d['mobile'], $d['course_id'], $d['year'], $d['gender'], $id);
        $stmt->execute();
        $stmt->close();

        audit_log($conn, 'UPDATE', 'student', $id, 'Updated student "' . $d['name'] . '" (API)');
        api_ok('Student "' . $d['name'] . '" was updated successfully.', st_out(st_fetch($conn, $id)));
    }

    /* ---------- DELETE ---------- */
    $stmt = $conn->prepare('DELETE FROM students WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    if ($deleted !== 1) {
        api_error(404, 'No student was deleted.');
    }
    audit_log($conn, 'DELETE', 'student', $id, 'Deleted student "' . $existing['name'] . '" (API)');
    api_ok('Student "' . $existing['name'] . '" was deleted successfully.', ['id' => $id]);
} catch (mysqli_sql_exception $ex) {
    if ((int) $ex->getCode() === 1062) {
        api_error(409, 'This email is already used by another student.', ['email' => 'Email already in use.']);
    }
    api_db_error($ex, 'save the student');
}
