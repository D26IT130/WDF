<?php
declare(strict_types=1);

/* GET php/api/csrf.php  ->  { success, data: { csrf } }   (any logged-in user) */

require_once __DIR__ . '/_common.php';

api_allow(['GET'], api_method());
api_require_login();
$token = api_csrf_token();
api_release_session();
api_ok('Token issued.', ['csrf' => $token]);
