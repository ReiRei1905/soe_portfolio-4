<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
    'name' => ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')
]);
?>