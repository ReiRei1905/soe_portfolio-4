<?php
session_start();
include 'connect.php';

function get_reset_token_record(mysqli $conn, string $rawToken): ?array
{
    $stmt = $conn->prepare('SELECT id, email, token_hash, expires_at, used_at FROM password_reset_tokens WHERE used_at IS NULL ORDER BY id DESC');
    if (!$stmt) {
        return null;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $match = null;

    while ($result && ($row = $result->fetch_assoc())) {
        if (!empty($row['token_hash']) && password_verify($rawToken, (string) $row['token_hash'])) {
            $match = $row;
            break;
        }
    }

    $stmt->close();
    return $match;
}

function alert_and_redirect(string $message, string $target): void
{
    echo "<script>alert('" . addslashes($message) . "'); window.location.replace('" . addslashes($target) . "');</script>";
}

$token = trim((string) ($_GET['token'] ?? ''));
$tokenRecord = null;

if ($token !== '') {
    $tokenRecord = get_reset_token_record($conn, $token);
}

$tokenIsValid = false;
$tokenEmail = '';
$tokenId = 0;

if ($tokenRecord) {
    $expiresAt = strtotime((string) ($tokenRecord['expires_at'] ?? ''));
    $isUsed = !empty($tokenRecord['used_at']);

    if (!$isUsed && $expiresAt !== false && $expiresAt >= time()) {
        $tokenIsValid = true;
        $tokenEmail = trim((string) ($tokenRecord['email'] ?? ''));
        $tokenId = (int) ($tokenRecord['id'] ?? 0);
    }
}

if (isset($_POST['resetButton'])) {
    if (!$tokenIsValid || $tokenEmail === '' || $tokenId <= 0) {
        alert_and_redirect('This reset link is invalid or expired. Please request a new one.', 'recover_psw.php');
        exit;
    }

    $psw = (string) ($_POST['password'] ?? '');
    if (trim($psw) === '') {
        echo "<script>alert('Please enter a new password.');</script>";
    } else {
        $hash = password_hash($psw, PASSWORD_DEFAULT);

        $updateUserStmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ? LIMIT 1');
        if (!$updateUserStmt) {
            echo "<script>alert('Unable to reset password right now.');</script>";
        } else {
            $updateUserStmt->bind_param('ss', $hash, $tokenEmail);
            $updated = $updateUserStmt->execute();
            $affected = $updateUserStmt->affected_rows;
            $updateUserStmt->close();

            if (!$updated || $affected < 0) {
                echo "<script>alert('Unable to reset password right now.');</script>";
            } else {
                $markUsedStmt = $conn->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ? LIMIT 1');
                if ($markUsedStmt) {
                    $markUsedStmt->bind_param('i', $tokenId);
                    $markUsedStmt->execute();
                    $markUsedStmt->close();
                }

                alert_and_redirect('Your password has been successfully reset.', 'index.php');
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOE Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" id="recoveryContainer">
        <h1 class="form-title">SOE-Portfolio Reset Password</h1>
        <?php if (!$tokenIsValid): ?>
            <p style="color:#b91c1c;text-align:center;margin-bottom:1rem;">This reset link is invalid or expired. Please request a new one.</p>
            <a href="recover_psw.php" class="btn" style="display:block;text-align:center;text-decoration:none;">Back to Recover</a>
        <?php else: ?>
            <form action="#" method="POST">
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" name="password" placeholder="Enter New Password" required>
                </div>
                <input type="submit" class="btn" value="Reset" name="resetButton">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

