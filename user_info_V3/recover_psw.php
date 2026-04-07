<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOE Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style.css"> <!-- Link to external CSS file for styling -->
</head>
<body>
    <div class="container" id="recoveryContainer">
        <h1 class="form-title">SOE-Portfolio Recover Password</h1>
        <form action="#" method="POST">
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="text" name="email" placeholder="Enter Email" required>
            </div>
            <input type="submit" class="btn" value="Recover" name="recoverButton">
        
        </form>
    </div>
</body>
</html>


<?php

    require 'vendor/autoload.php';
    require_once 'mail_settings.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

session_start();
include 'connect.php';

if (isset($_POST["recoverButton"])) {
    
    $email = trim($_POST["email"] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address.');</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT status FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $fetch = $result->fetch_assoc();

    if ($result->num_rows <= 0) {
        echo "<script>alert('Sorry, no email exists');</script>";
    } else if ((int)$fetch["status"] === 0) {
        echo "<script>
            alert('Sorry, your account must be verified before you can recover your password!');
            window.location.replace('index.php');
        </script>";
    } else {
        // generate token
        $token = bin2hex(random_bytes(50));
        $_SESSION['token'] = $token;
        $_SESSION['email'] = $email;

        $mail = new PHPMailer(true);
        try {
            configureSmtpMailer($mail, 'Password Reset');
            $mail->addAddress($email);

            $resetUrl = buildAppUrl("reset_psw.php?token=$token");

            $mail->isHTML(true);
            $mail->Subject = "Recover your password";
            $mail->Body = "<b>Dear User</b>
                <h3>We received a request to reset your password.</h3>
                <p>Kindly click the below link to reset your password</p>
                <a href='$resetUrl'>Reset Password</a>
                <br><br>
                <p>With regards,</p>
                <b>SOE Portfolio</b>";

            $mail->send();
            echo "<script>window.location.replace('notification.html');</script>";
        } catch (Exception $e) {
            error_log("Password recovery mail failed for {$email}: " . $mail->ErrorInfo);

            if (isLocalMailFallbackEnabled()) {
                echo "<script>alert('SMTP failed, so local fallback is active. You will be redirected to reset password directly.'); window.location.replace('reset_psw.php?token=$token');</script>";
            } else {
                echo "<script>alert('Email service is temporarily unavailable. Please try again later.');</script>";
            }
        }
    }
}
?>