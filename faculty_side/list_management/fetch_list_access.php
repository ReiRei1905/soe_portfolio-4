<?php

declare(strict_types=1);

require_once __DIR__ . '/list_access_common.php';

$sessionUser = list_require_access($conn);
$creatablePrograms = list_get_creatable_programs($conn, $sessionUser);

faculty_send_json([
    'success' => true,
    'canCreate' => !empty($creatablePrograms),
    'programs' => $creatablePrograms
]);