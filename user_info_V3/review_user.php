<?php
session_start();
include ("connect.php");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Under Review</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 10%;
                background-color: #f9f9f9;
            }
            .container {
                background-color: #fff;
                border: 1px solid #ffa500;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                display: inline-block;
                text-align: center;
            }
            .container p {
                font-size: 20px;
                margin: 10px 0;
            }
            .container h1 {
                font-size: 30px;
                font-weight: bold;
                margin-bottom: 20px;
            }
            a {
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                font-size: 16px;
                color: #fff;
                background-color: #007bff;
                text-decoration: none;
                border-radius: 5px;
            }
            a:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>
                Hello <?php
                if (isset($_SESSION['email'])) {
                    $email = $_SESSION['email'];
                    $sql = "SELECT * FROM users WHERE email='$email'";
                    $query = $conn->query($sql);

                    if ($query && $query->num_rows > 0) {
                        while ($row = $query->fetch_assoc()) {
                            echo htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['last_name']);
                        }
                    } else {
                        echo "User not found.";
                    }
                } else {
                    echo "No session found. Please log in.";
                }
                ?>
            </h1>
            <p>Your account is pending approval by the admin of this system. You will receive an email once your account is approved.</p>
            <a href="logout.php">Logout</a>
        </div>
    </body>
</html>