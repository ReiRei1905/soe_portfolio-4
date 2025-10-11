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
        <h1 class="form-title">SOE-Portfolio Reset Password</h1>
        <form action="#" method="POST">
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="text" name="password" placeholder="Enter Password" required>
            </div>
            <input type="submit" class="btn" value="Reset" name="resetButton">
        
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

if (isset($_POST["resetButton"])) {
    
    $psw = $_POST["password"];

    $token = $_SESSION['token'];
    $Email = $_SESSION['email'];

    $hash = password_hash( $psw , PASSWORD_DEFAULT );

    $sql = mysqli_query($conn, "SELECT * FROM users WHERE email='$Email'");
    $query = mysqli_num_rows($sql);
  	$fetch = mysqli_fetch_assoc($sql);

    if($Email){
            $new_pass = $hash;
            mysqli_query($conn, "UPDATE users SET password='$new_pass' WHERE email='$Email'");
            ?>
            <script>
                window.location.replace("index.php");
                alert("<?php echo "your password has been succesful reset"?>");
            </script>
            <?php
        }else{
            ?>
            <script>
                alert("<?php echo "Please try again"?>");
            </script>
            <?php
        }
    }
?>
<script>
    const toggle = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    toggle.addEventListener('click', function(){
        if(password.type === "password"){
            password.type = 'text';
        }else{
            password.type = 'password';
        }
        this.classList.toggle('bi-eye');
    });
</script>

