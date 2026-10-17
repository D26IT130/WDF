<?php
/* =========================================================
   STUDENT HUB - DATABASE CONNECTION
   Included by register.php. Creates the $pdo connection.
   ========================================================= */

// XAMPP defaults: user "root" with an empty password.
// Change these to match your MySQL setup.
$host   = "localhost";
$dbName = "my_student_hub";
$dbUser = "root";
$dbPass = "";

// While setting up, true shows the real database error on the form.
// Set it to false before putting the site online.
const DEBUG = true;

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Log the real reason on the server, show a safe message to the user
    error_log("Database connection failed: " . $e->getMessage());

    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "message" => "Could not connect to the database."
            . (DEBUG ? " (" . $e->getMessage() . ")" : "")
    ]);
    exit;
}