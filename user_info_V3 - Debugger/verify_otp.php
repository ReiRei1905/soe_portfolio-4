<?php
session_start();
include 'connect.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];
    $storedOtp = $_SESSION['otp'] ?? null;
    $otpExpiry = $_SESSION['otp_expiry'] ?? null;

    if ($storedOtp && $otp === $storedOtp && time() <= $otpExpiry) {
        // OTP is valid
        unset($_SESSION['otp'], $_SESSION['otp_expiry']); // Clear OTP after successful verification
        echo "<script>alert('OTP verified successfully!'); window.location.href = 'dashboard.php';</script>";
    } else {
        // OTP is invalid or expired
        unset($_SESSION['otp'], $_SESSION['otp_expiry']); // Clear expired OTP
        echo "<script>alert('Invalid or expired OTP. Please try again.'); window.location.href = 'index.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="style.css?v=2"> <!-- Corrected path -->
</head>
<body>
    <div class="otp-container">
        <h1 class="otp-title">Verify OTP</h1>
        <p class="otp-instructions">Please enter the OTP sent to your registered email address. This OTP will expire in <span class="otp-timer" id="timer">5:00</span>.</p>
        <form method="post" action="">
            <input type="text" class="otp-input" id="otp" name="otp" placeholder="Enter your OTP" required pattern="\d{6}">
            <input type="submit" class="otp-submit-btn" value="Verify OTP">
        </form>
        <button class="otp-back-btn" onclick="window.history.back();">Back</button>
    </div>
    <script>
        // Timer logic
        let timer = 300; // 5 minutes in seconds
        const timerElement = document.getElementById('timer');

        const countdown = setInterval(() => {
            const minutes = Math.floor(timer / 60);
            const seconds = timer % 60;
            timerElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            timer--;

            if (timer < 0) {
                clearInterval(countdown);
                alert('OTP expired. Please try again.');
                window.location.href = 'index.php'; // Redirect to restart the process
            }
        }, 1000);
    </script>
</body>
</html>
