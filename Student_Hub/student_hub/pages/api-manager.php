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
<title>Student Hub - API Manager</title>
<link rel="stylesheet" href="../css/style1.css">
<link rel="stylesheet" href="../css/students-crud.css">
<link rel="stylesheet" href="../css/api-manager.css">
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

<main class="crud-main">

    <section class="crud-head">
        <div>
            <h2>API Manager</h2>
            <p class="crud-sub">Events and students managed with the Fetch API. The page never reloads.</p>
        </div>
        <button type="button" class="btn" id="addBtn">+ Add</button>
    </section>

    <div class="api-tabs" role="tablist" aria-label="Choose data">
        <button type="button" role="tab" class="api-tab active" data-resource="events" aria-selected="true">Events</button>
        <button type="button" role="tab" class="api-tab" data-resource="students" aria-selected="false">Students</button>
    </div>

    <!-- success / error messages -->
    <div id="apiMessage" class="crud-alert" role="status" aria-live="polite" hidden></div>

    <!-- add / edit form (filled by JavaScript) -->
    <form id="apiForm" class="crud-form api-form" novalidate hidden>
        <h3 id="formTitle" class="api-form-title"></h3>
        <div id="formFields" class="api-form-fields"></div>
        <div class="crud-field crud-actions api-form-actions">
            <button type="submit" class="btn" id="saveBtn">Save</button>
            <button type="button" class="btn btn-outline" id="cancelBtn">Cancel</button>
        </div>
    </form>

    <!-- search + filters (filled by JavaScript) -->
    <form id="filterForm" class="crud-filters api-filters" role="search"></form>

    <div class="crud-table-wrap api-table-wrap">
        <div id="loadingBar" class="api-loading" hidden><span class="api-spinner" aria-hidden="true"></span> Loading...</div>
        <table class="crud-table" aria-live="polite">
            <thead id="tableHead"></thead>
            <tbody id="tableBody"></tbody>
        </table>
    </div>

    <nav id="pager" class="crud-pagination" aria-label="Pages"></nav>

    <details class="api-log">
        <summary>Last API call (same as the browser Network tab)</summary>
        <pre id="apiLog">No request yet.</pre>
    </details>

</main>

<footer>
    <p>&copy; 2026 Student Hub. All rights reserved.</p>
</footer>

<script src="../js/script.js"></script>
<script src="../js/api-manager.js"></script>
</body>
</html>
