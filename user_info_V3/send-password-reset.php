<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['recoverButton'] = '1';
    require __DIR__ . '/recover_psw.php';
    exit;
}

header('Location: recover_psw.php');
exit;
?>