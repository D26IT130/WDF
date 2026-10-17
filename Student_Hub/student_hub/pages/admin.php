<?php
require_once __DIR__ . '/../php/auth.php';
requireRole('admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Hub - Admin</title>
<link rel="stylesheet" href="../css/style1.css">
<link rel="stylesheet" href="../css/admin-students.css">
</head>
<body>

<header>
    <div class="logo"><h1>Student Hub</h1></div>

    <button class="hamburger" id="hamburger" type="button" aria-label="Open navigation menu">
        <span></span><span></span><span></span>
    </button>

    <nav id="mainNav">
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="admin.php" aria-current="page">Admin</a></li>
            <li><a href="events.html">Events</a></li>
            <li><a href="faq.html">FAQ</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="feedback.html">Feedback</a></li>
            <li><a href="contact.html">Contact</a></li>
            <li><a href="profile.html">Profile</a></li>
            <li><a href="login.html">Login</a></li>
            <li><a href="registration.html">Register</a></li>
                <li><a href="../php/logout.php">Logout</a></li>
            </ul>
    </nav>
</header>

<main>

    <section class="dashboard-header">
        <h2>Student Records</h2>
        <p>Search, filter, sort and browse student data loaded from JSON.</p>
    </section>

    <section class="stu-section">

        <div class="stu-filters">
            <div class="stu-field stu-field-wide">
                <label for="studentSearch">Search Students</label>
                <input type="search" id="studentSearch" placeholder="Search by name, email or city...">
            </div>

            <div class="stu-field">
                <label for="courseFilter">Course</label>
                <select id="courseFilter">
                    <option value="all">All Courses</option>
                </select>
            </div>

            <div class="stu-field">
                <label for="statusFilter">Status</label>
                <select id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="stu-field">
                <label for="sortSelect">Sort</label>
                <select id="sortSelect">
                    <option value="default">Default</option>
                    <option value="name-asc">Name (A to Z)</option>
                    <option value="name-desc">Name (Z to A)</option>
                    <option value="cgpa-desc">CGPA (High to Low)</option>
                    <option value="cgpa-asc">CGPA (Low to High)</option>
                    <option value="year-asc">Year (1 to 3)</option>
                </select>
            </div>
        </div>

        <p id="studentMessage" class="stu-message" role="status" aria-live="polite">Loading students...</p>

        <div id="studentGrid" class="stu-grid"></div>

        <nav id="studentPagination" class="stu-pagination" aria-label="Student pages"></nav>

    </section>

</main>

<footer>
    <p>&copy; 2026 Student Hub. All rights reserved.</p>
</footer>

<script src="../js/script.js"></script>
<script src="../js/admin.js"></script>
</body>
</html>