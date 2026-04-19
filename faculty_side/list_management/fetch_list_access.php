<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$programs = list_get_manageable_programs($conn, $sessionUser);

faculty_send_json([
    'success' => true,
    'canCreate' => !empty($programs),
    'programs' => $programs
]);
