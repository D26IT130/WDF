<?php
session_start();

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: pages/admin.php');
        exit;
    }

    if ($_SESSION['role'] === 'student') {
        header('Location: pages/dashboard.php');
        exit;
    }
}

header('Location: pages/login.html');
exit;
