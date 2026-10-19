<?php
declare(strict_types=1);

require_once __DIR__ . '/../menu.php';

function render_header(string $title): void
{
    $flash = pull_flash();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> - Student Hub</title>
<link rel="stylesheet" href="../../css/style1.css">
<link rel="stylesheet" href="../../css/students-crud.css">
</head>
<body>

<header>
    <div class="logo"><h1>Student Hub</h1></div>
    <button class="hamburger" id="hamburger" type="button" aria-label="Open navigation menu">
        <span></span><span></span><span></span>
    </button>
    <nav id="mainNav">
        <ul>
            <?= menu_render('../../') ?>
        </ul>
    </nav>
</header>

<main class="crud-main">
<?php if ($flash): ?>
    <div class="crud-alert crud-alert-<?= e($flash['type']) ?>" role="alert">
        <?= e($flash['text']) ?>
    </div>
<?php endif;
}

function render_footer(): void
{
    ?>
</main>

<footer>
    <p>&copy; 2026 Student Hub. All rights reserved.</p>
</footer>
<script src="../../js/script.js"></script>
</body>
</html>
<?php
}
