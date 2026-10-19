<?php
declare(strict_types=1);

/* GET php/menu_json.php -> the menu for the current visitor's role (used by static .html pages) */

require_once __DIR__ . '/menu.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$role = menu_current_role();
session_write_close();

echo json_encode([
    'loggedIn' => $role !== null,
    'role'     => $role ?? 'guest',
    'menu'     => array_map(fn($m) => ['label' => $m[0], 'path' => $m[1]], menu_for_role($role)),
]);
