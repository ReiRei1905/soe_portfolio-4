<?php
session_start();
include 'connect.php'; // Include database connection

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // Make sure this path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Generate a 6-digit OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300; // 5 minutes from now

    // Send OTP via PHPMailer SMTP
    $mail = new PHPMailer(true);
    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com'; // Outlook SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your_outlook_email@outlook.com'; // Your Outlook email
        $mail->Password = 'your_app_password_or_email_password'; // Your Outlook password or app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('your_outlook_email@outlook.com', 'SOE Portfolio');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Your One-Time Password (OTP)";
        $mail->Body = "Your OTP is: <b>$otp</b>. It will expire in 5 minutes.";

        $mail->send();
        echo "OTP sent successfully.";
    } catch (Exception $e) {
        echo "Failed to send OTP. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
