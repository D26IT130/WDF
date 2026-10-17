<?php
/* =========================================================
   STUDENT HUB - REGISTRATION ENDPOINT
   Receives: { fullName, email, studentId, password }
   Replies with JSON: { message } or { field, message }
   ========================================================= */

header("Content-Type: application/json; charset=utf-8");

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}

require __DIR__ . "/db.php";


/* ---------------------------------------------------------
   Helpers
   --------------------------------------------------------- */

function text($value)
{
    return is_string($value) ? trim($value) : "";
}

function fail($status, $field, $message)
{
    http_response_code($status);
    echo json_encode(["field" => $field, "message" => $message]);
    exit;
}


/* ---------------------------------------------------------
   Read the data (JSON from script.js, or a normal form post)
   --------------------------------------------------------- */

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    $input = $_POST;
}

$fullName  = text($input["fullName"] ?? "");
$email     = strtolower(text($input["email"] ?? ""));
$studentId = text($input["studentId"] ?? "");
$password  = is_string($input["password"] ?? null) ? $input["password"] : "";   // not trimmed


/* ---------------------------------------------------------
   Validate
   --------------------------------------------------------- */

if ($fullName === "") {
    fail(400, "fullName", "Please enter your full name.");
}

if (mb_strlen($fullName) > 100) {
    fail(400, "fullName", "Full name is too long.");
}

if ($email === "") {
    fail(400, "email", "Please enter your email.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    fail(400, "email", "Please enter a valid email address.");
}

if ($studentId === "") {
    fail(400, "studentId", "Please enter your student ID.");
}

if (mb_strlen($studentId) > 50) {
    fail(400, "studentId", "Student ID is too long.");
}

if (mb_strlen($password) < 6) {
    fail(400, "password", "Password must be at least 6 characters.");
}

if (strlen($password) > 72) {   // bcrypt only uses the first 72 bytes
    fail(400, "password", "Password must be at most 72 characters.");
}


/* ---------------------------------------------------------
   Save the user
   --------------------------------------------------------- */

try {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Prepared statement: user input never becomes part of the SQL text
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, student_id, password_hash)
         VALUES (:full_name, :email, :student_id, :password_hash)"
    );

    $stmt->execute([
        ":full_name"     => $fullName,
        ":email"         => $email,
        ":student_id"    => $studentId,
        ":password_hash" => $passwordHash,
    ]);

    http_response_code(201);
    echo json_encode(["message" => "Registration successful."]);

} catch (PDOException $e) {

    // 1062 = duplicate entry (email or student ID already exists)
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
        if (strpos($e->getMessage(), "student_id") !== false) {
            fail(409, "studentId", "This student ID is already registered.");
        }

        fail(409, "email", "This email is already registered.");
    }

    error_log("Registration error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "message" => "Something went wrong on the server. Please try again."
            . (DEBUG ? " (" . $e->getMessage() . ")" : "")
    ]);
}