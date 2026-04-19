<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

$logoutEmail = trim((string) ($_SESSION['email'] ?? ''));
if ($logoutEmail !== '') {
	setcookie('local_owner_email', $logoutEmail, [
		'expires' => time() + (86400 * 30),
		'path' => '/',
		'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
		'httponly' => true,
		'samesite' => 'Lax'
	]);
}

// Clear all session values first.
$_SESSION = [];

// Invalidate the session cookie so the browser cannot reuse it.
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(
		session_name(),
		'',
		time() - 42000,
		$params['path'] ?? '/',
		$params['domain'] ?? '',
		(bool) ($params['secure'] ?? false),
		(bool) ($params['httponly'] ?? true)
	);
}

session_destroy();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: index.php');
exit;

?>