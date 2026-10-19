<?php
declare(strict_types=1);

/* =========================================================
   ROLE-BASED MENU  -  the single source of truth
   guest   : public pages + Login / Register
   student : public pages + Dashboard, Profile, Logout
   admin   : admin tools only (dashboard, manage pages, audit logs, logout)
   Used by PHP pages (menu_render) and by static .html pages
   (menu_json.php -> js/script.js builds the same menu).
   Paths are relative to the project root.
   ========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function menu_for_role(?string $role): array
{
    $public = [
        ['Home',     'pages/index.html'],
        ['Events',   'pages/events.html'],
    ];
    $info = [
        ['FAQ',      'pages/faq.html'],
        ['About',    'pages/about.html'],
        ['Feedback', 'pages/feedback.html'],
        ['Contact',  'pages/contact.html'],
    ];

    if ($role === 'admin') {
        return [
            ['Home',             'pages/index.html'],
            ['Admin Dashboard',  'pages/admin-dashboard.php'],
            ['Events',           'pages/events.html'],
            ['Manage Events',    'php/events/index.php'],
            ['Manage Users',     'php/users/index.php'],
            ['Manage Students',  'php/students/index.php'],
            ['API Manager',      'pages/api-manager.php'],
            ['Audit Logs',       'php/admin/audit_logs.php'],
            ['Logout',           'php/logout.php'],
        ];
    }
    if ($role === 'student') {
        return array_merge(
            [['Home', 'pages/index.html'], ['Dashboard', 'pages/dashboard.php'], ['Events', 'pages/events.html']],
            $info,
            [['Profile', 'pages/profile.html'], ['Logout', 'php/logout.php']]
        );
    }
    return array_merge($public, $info, [['Login', 'pages/login.html'], ['Register', 'pages/registration.html']]);
}

function menu_current_role(): ?string
{
    return isset($_SESSION['user_id'], $_SESSION['role']) ? (string) $_SESSION['role'] : null;
}

/**
 * <li> items for the current user's role.
 * $root    : path from the current page back to the project root ('../' or '../../')
 * $variant : 'site' (plain <li><a>) or 'bootstrap' (nav-item / nav-link)
 */
function menu_render(string $root, string $variant = 'site'): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $html = '';
    foreach (menu_for_role(menu_current_role()) as [$label, $path]) {
        $active = $script !== '' && str_ends_with($script, '/' . $path);
        $href = htmlspecialchars($root . $path, ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        if ($variant === 'bootstrap') {
            $html .= '<li class="nav-item"><a class="nav-link' . ($active ? ' active fw-semibold' : '') . '" href="' . $href . '"'
                   . ($active ? ' aria-current="page"' : '') . '>' . $text . '</a></li>' . "\n";
        } else {
            $html .= '<li><a href="' . $href . '"' . ($active ? ' aria-current="page"' : '') . '>' . $text . '</a></li>' . "\n";
        }
    }
    return $html;
}
