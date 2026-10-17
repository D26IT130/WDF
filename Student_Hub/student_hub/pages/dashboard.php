<?php
require_once __DIR__ . '/../php/auth.php';
requireRole('student');
?>

<!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Hub - Dashboard</title>

<link rel="stylesheet" href="../css/style1.css">

</head> <body>
<!-- ================= HEADER ================= -->

<header>

    <div class="logo">
        <h1>Student Hub</h1>
    </div>

    <button
        class="hamburger"
        id="hamburger"
        type="button"
        aria-label="Open navigation menu"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

     <nav id="mainNav">
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="events.html">Events</a></li>
                <li><a href="faq.html">FAQ</a></li>
                <li><a href="about.html">About</a></li>
                <li><a href="feedback.html">Feedback</a></li>
                <li><a href="contact.html" aria-current="page">Contact</a></li>
                <li><a href="profile.html">Profile</a></li>
                <li><a href="login.html">Login</a></li>
                <li><a href="registration.html">Register</a></li>
                <li><a href="../php/logout.php">Logout</a></li>
            </ul>
        </nav>

</header>


<!-- ================= MAIN CONTENT ================= -->

<main>

    <!-- ================= DASHBOARD HEADER ================= -->

    <section class="dashboard-header">

        <div>
            <span class="hero-badge">
                🎓 Student Dashboard
            </span>

            <h2>
                Welcome back, Student!
            </h2>

            <p>
                Manage your courses, events, resources,
                and student activities from one place.
            </p>
        </div>

    </section>


    <!-- ================= DASHBOARD STATS ================= -->

    <section class="dashboard-stats" aria-labelledby="stats-heading">

        <h2 id="stats-heading">My Overview</h2>

        <div class="feature-container">

            <article class="feature-card">

                <div class="feature-icon">
                    📚
                </div>

                <h3>Enrolled Courses</h3>

                <p class="dashboard-number">
                    5
                </p>

                <a href="courses.html">
                    View Courses →
                </a>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    📝
                </div>

                <h3>Study Notes</h3>

                <p class="dashboard-number">
                    18
                </p>

                <a href="notes.html">
                    View Notes →
                </a>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    📅
                </div>

                <h3>Upcoming Events</h3>

                <p class="dashboard-number">
                    3
                </p>

                <a href="events.html">
                    View Events →
                </a>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    🏆
                </div>

                <h3>Completed Courses</h3>

                <p class="dashboard-number">
                    2
                </p>

                <a href="profile.html">
                    View Profile →
                </a>

            </article>

        </div>

    </section>


    <!-- ================= RECENT COURSES ================= -->

    <section class="dashboard-section" aria-labelledby="courses-heading">

        <h2 id="courses-heading">
            My Courses
        </h2>

        <div class="feature-container">

            <article class="feature-card">

                <div class="feature-icon">
                    💻
                </div>

                <h3>Web Development</h3>

                <p>
                    Learn HTML, CSS, JavaScript and modern
                    web development techniques.
                </p>

                <p>
                    <strong>Progress:</strong> 75%
                </p>

                <a href="courses.html">
                    Continue Learning →
                </a>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    🐍
                </div>

                <h3>Python Programming</h3>

                <p>
                    Learn Python programming concepts,
                    functions, and applications.
                </p>

                <p>
                    <strong>Progress:</strong> 60%
                </p>

                <a href="courses.html">
                    Continue Learning →
                </a>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    🗄️
                </div>

                <h3>Database Management</h3>

                <p>
                    Understand databases, SQL queries,
                    tables, and relationships.
                </p>

                <p>
                    <strong>Progress:</strong> 45%
                </p>

                <a href="courses.html">
                    Continue Learning →
                </a>

            </article>

        </div>

    </section>


    <!-- ================= UPCOMING EVENTS ================= -->

    <section class="dashboard-section" aria-labelledby="events-heading">

        <h2 id="events-heading">
            Upcoming Events
        </h2>

        <div class="feature-container">

            <article class="feature-card">

                <div class="feature-icon">
                    🎤
                </div>

                <h3>Student Workshop</h3>

                <p>
                    Learn new skills and participate in
                    an interactive student workshop.
                </p>

                <p>
                    <strong>Date:</strong>
                    25 August 2026
                </p>

                <a href="events.html">
                    View Event →
                </a>

            </article>


            <article class="feature-card">

                <div class="feature-icon">
                    💡
                </div>

                <h3>Project Exhibition</h3>

                <p>
                    Explore innovative student projects
                    and creative ideas.
                </p>

                <p>
                    <strong>Date:</strong>
                    10 September 2026
                </p>

                <a href="events.html">
                    View Event →
                </a>

            </article>

        </div>

    </section>


    <!-- ================= QUICK ACTIONS ================= -->

    <section class="home-cta">

        <div>

            <h2>
                Quick Actions
            </h2>

            <p>
                Quickly access your Student Hub activities.
            </p>

        </div>

        <div class="hero-buttons">

            <a href="profile.html" class="btn">
                My Profile
            </a>

            <a href="events.html" class="btn btn-outline">
                Events
            </a>

        </div>

    </section>

</main>


<!-- ================= FOOTER ================= -->

<footer>

    <p>
        &copy; 2026 Student Hub. All rights reserved.
    </p>

</footer>


<script src="../js/script.js"></script>

</body> </html>