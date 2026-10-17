<?php
declare(strict_types=1);

/* =========================================================
   STUDENT HUB - MySQLi CONNECTION
   Creates the $conn object. Your PDO file (db.php) stays
   as it is for the other practicals.
   ========================================================= */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqliHost = 'localhost';
$mysqliUser = 'root';   // XAMPP default
$mysqliPass = '';       // XAMPP default is empty
$mysqliName = 'my_student_hub';

// true = show the real error while testing. Set to false before putting the site online.
$mysqliDebug = true;

try {
    $conn = new mysqli($mysqliHost, $mysqliUser, $mysqliPass, $mysqliName);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('MySQLi connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not connect to the database.' . ($mysqliDebug ? ' (' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ')' : ''));
}