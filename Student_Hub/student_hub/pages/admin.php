<?php
require_once __DIR__ . '/../php/menu.php';
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
            <?= menu_render('../') ?>
        </ul>
    </nav>
</header>

<main>

    <section class="dashboard-header">
        <h2>Student Records</h2>
        <p>Search, filter, sort and browse student data loaded from JSON.</p>
    
        <p><a class="btn" href="../php/users/index.php">Manage Users (users table)</a> <a class="btn btn-outline" href="../php/students/index.php">Manage Students</a> <a class="btn" href="../php/events/index.php">Manage Events</a> <a class="btn btn-outline" href="api-manager.php">API Manager (Fetch)</a></p>
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