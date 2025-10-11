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
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

session_start();
include 'connect.php';

if (isset($_POST["recoverButton"])) {
    
    $email = $_POST["email"];

    $sql = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $fetch = mysqli_fetch_assoc($sql);

    if (mysqli_num_rows($sql) <= 0) {
        echo "<script>alert('Sorry, no email exists');</script>";
    } else if ($fetch["status"] == 0) {
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

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';

        $mail->Username = 'neloangelo4123@gmail.com'; // your Gmail
        $mail->Password = 'pflg rocs kbhp jtzl';    // your Gmail App Password

        $mail->setFrom('neloangelo4123@gmail.com', 'Password Reset');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Recover your password";
        $mail->Body = "<b>Dear User</b>
            <h3>We received a request to reset your password.</h3>
            <p>Kindly click the below link to reset your password</p>
            <a href='http://localhost/soe_portfolio/user_info_V3/reset_psw.php?token=$token'>Reset Password</a>
            <br><br>
            <p>With regards,</p>
            <b>SOE Portfolio</b>";

        if (!$mail->send()) {
            echo "<script>alert('Invalid Email');</script>";
        } else {
            echo "<script>window.location.replace('notification.html');</script>";
        }
    }
}
?>