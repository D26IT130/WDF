<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/auth.php';          // MUST be the first thing on the page (starts the session)
require_once __DIR__ . '/../php/db_mysqli.php';     // gives $conn and $mysqliDebug

// Logged-in users do not need to register
if (isset($_SESSION['user_id'])) {
    redirect_to(home_for((string)$_SESSION['role']));
}

$errors = [];
$formError = '';
$old = ['fullname' => '', 'email' => '', 'studentId' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['fullname']  = trim((string)($_POST['fullname'] ?? ''));
    $old['email']     = strtolower(trim((string)($_POST['email'] ?? '')));
    $old['studentId'] = trim((string)($_POST['studentId'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirmPassword'] ?? '');
    $terms    = isset($_POST['terms']);

    // Server-side validation (the browser's HTML5 checks are only a convenience)
    if (strlen($old['fullname']) < 3 || strlen($old['fullname']) > 50) {
        $errors['fullname'] = 'Enter your full name (3 to 50 characters).';
    }
    if ($old['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (strlen($old['email']) > 100 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address, like name@example.com.';
    }
    if (!preg_match('/^[1-9][0-9]{0,8}$/', $old['studentId'])) {
        $errors['studentId'] = 'Enter your numeric Student ID (up to 9 digits, no leading zero).';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    }
    if ($confirm === '' || $confirm !== $password) {
        $errors['confirmPassword'] = 'The passwords do not match.';
    }
    if (!$terms) {
        $errors['terms'] = 'You need to accept the Terms & Conditions.';
    }

    if (!$errors) {
        try {
            // Duplicate check
            $stmt = $conn->prepare('SELECT email, student_id FROM users WHERE email = ? OR student_id = ?');
            $stmt->bind_param('ss', $old['email'], $old['studentId']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                if (strcasecmp((string)$row['email'], $old['email']) === 0) {
                    $errors['email'] = 'An account with this email already exists. Try logging in instead.';
                }
                if ((string)$row['student_id'] === $old['studentId']) {
                    $errors['studentId'] = 'This Student ID is already registered.';
                }
            }
            $stmt->close();

            if (!$errors) {
                // password_hash() runs BEFORE the value reaches the database.
                // role defaults to 'student', so nobody can register as admin.
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare('INSERT INTO users (full_name, email, student_id, password_hash) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $old['fullname'], $old['email'], $old['studentId'], $hash);
                $stmt->execute();
                $stmt->close();

                redirect_to('login.php?msg=registered');
            }
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {
                $formError = 'That email or Student ID is already registered.';
            } else {
                error_log('Registration failed: ' . $ex->getMessage());
                $formError = 'We could not create your account.' . (!empty($mysqliDebug) ? ' [Debug: ' . $ex->getMessage() . ']' : '');
            }
        }
    }
    if ($errors && !$formError) {
        $formError = 'Please fix the highlighted fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Registration</title>

    <link rel="stylesheet" href="../css/style1.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>

<body>

<?php require __DIR__ . '/../php/header.php'; ?>

<main>
    <section class="login-section">
        <div class="login-box">

            <h2>Create Account</h2>
            <p>Register to become a member of Student Hub.</p>

            <?php if ($formError): ?>
                <div class="alert error" role="alert"><?= e($formError) ?></div>
            <?php endif; ?>

            <!-- HTML5 attributes give instant browser validation; PHP re-checks everything -->
            <form id="registrationForm" method="post" action="registration.php">

                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name"
                           value="<?= e($old['fullname']) ?>" minlength="3" maxlength="50" autocomplete="name" required<?= invalid_attr($errors, 'fullname') ?>>
                    <?= field_error($errors, 'fullname') ?>
                </div>

                <div class="form-group">
                    <label for="registerEmail">Email</label>
                    <input type="email" id="registerEmail" name="email" placeholder="Enter your email"
                           value="<?= e($old['email']) ?>" maxlength="100" autocomplete="email"
                           pattern="[^\s@]+@[^\s@]+\.[^\s@]+" title="Enter an email like name@example.com" required<?= invalid_attr($errors, 'email') ?>>
                    <?= field_error($errors, 'email') ?>
                </div>

                <div class="form-group">
                    <label for="studentId">Student ID</label>
                    <input type="text" id="studentId" name="studentId" placeholder="Enter your student ID"
                           value="<?= e($old['studentId']) ?>" inputmode="numeric" maxlength="9"
                           pattern="[1-9][0-9]{0,8}" title="Up to 9 digits, no leading zero" required<?= invalid_attr($errors, 'studentId') ?>>
                    <?= field_error($errors, 'studentId') ?>
                </div>

                <div class="form-group">
                    <label for="registerPassword">Password</label>
                    <input type="password" id="registerPassword" name="password" placeholder="Create a password (min 8 characters)"
                           minlength="8" maxlength="64" autocomplete="new-password" required<?= invalid_attr($errors, 'password') ?>>
                    <?= field_error($errors, 'password') ?>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password"
                           minlength="8" maxlength="64" autocomplete="new-password" required<?= invalid_attr($errors, 'confirmPassword') ?>>
                    <?= field_error($errors, 'confirmPassword') ?>
                </div>

                <div class="form-options">
                    <label class="terms-label">
                        <input type="checkbox" id="terms" name="terms" value="1" required<?= invalid_attr($errors, 'terms') ?>>
                        <span>I agree to the <a href="#">Terms &amp; Conditions</a></span>
                    </label>
                    <?= field_error($errors, 'terms') ?>
                </div>

                <button type="submit" class="btn">Create Account</button>
            </form>

            <p class="register-text">
                Already have an account?
                <a href="login.php">Login</a>
            </p>

        </div>
    </section>
</main>

<footer>
    <p>&copy; 2026 Student Hub. All rights reserved.</p>
</footer>

<script src="../js/script.js"></script>
</body>
</html>