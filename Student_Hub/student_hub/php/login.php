<?php
session_start();

require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.html');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$requestedRole = $_POST['role'] ?? '';

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $password === '' ||
    !in_array($requestedRole, ['student', 'admin'], true)
) {
    http_response_code(400);
    exit('Please enter valid login details.');
}

$stmt = $conn->prepare(
    'SELECT id, full_name, email, password_hash, role
     FROM users
     WHERE email = ?
     LIMIT 1'
);

$stmt->bind_param('s', $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (
    !$user ||
    !password_verify($password, $user['password_hash']) ||
    $user['role'] !== $requestedRole
) {
    audit_log($conn, 'LOGIN_FAILED', 'session', $user ? (int) $user['id'] : null,
        'Failed login for ' . $email . ' (as ' . $requestedRole . ')', null, $user['full_name'] ?? $email, null);
    http_response_code(401);
    exit('Incorrect email, password, or account type.');
}

// Create a fresh session ID after authentication.
session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

audit_log($conn, 'LOGIN', 'session', (int) $user['id'], ucfirst($user['role']) . ' logged in');

if ($user['role'] === 'admin') {
    header('Location: ../pages/admin-dashboard.php');
} else {
    header('Location: ../pages/dashboard.php');
}

exit;
