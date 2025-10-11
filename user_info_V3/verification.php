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
    <div class="container" id="verificationContainer">
        <h1 class="form-title">SOE-Portfolio Verification</h1>
        <form action="#" method="POST">
            <div class="input-group">
                <i class="fas fa-paper-plane"></i>
                <input type="text" name="verify" placeholder="Enter Code" required>
            </div>
            <input type="submit" class="btn" value="Verify" name="verifyButton">
        
        </form>
    </div>
</body>
</html>


<?php
session_start();
include 'connect.php';

if (isset($_POST["verifyButton"])) {
    $otp = $_SESSION['otp'] ?? '';
    $email = $_SESSION['mail'] ?? '';
    $otp_code = htmlspecialchars(trim($_POST['verify'] ?? ''));

    if ($otp != $otp_code) {
        echo "<script>alert('Invalid OTP code');</script>";
    } else {
        // Update the user's status to verified (assuming you have a 'status' column in 'users')
        $stmt = $conn->prepare("UPDATE users SET status = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            echo "<script>alert('Verify account done, you may sign in now'); window.location.replace('index.php');</script>";
        } else {
            echo "<script>alert('Database error. Please try again.');</script>";
        }
    }
}
?>