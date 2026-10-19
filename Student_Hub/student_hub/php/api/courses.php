<?php
declare(strict_types=1);

/* GET php/api/courses.php  ->  list of courses for the student form (admins only) */

require_once __DIR__ . '/_common.php';

api_allow(['GET'], api_method());
api_require_admin();
api_release_session();

try {
    $stmt = $conn->prepare('SELECT course_id, course_name FROM courses ORDER BY course_name');
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    api_ok('Courses loaded.', $rows);
} catch (mysqli_sql_exception $ex) {
    api_db_error($ex, 'load courses');
}
