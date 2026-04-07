<?php
session_start();
include ("connect.php");
require_once __DIR__ . '/user_access_common.php';

if (!empty($_SESSION['email'])) {
    $email = trim((string) $_SESSION['email']);
    $stmt = $conn->prepare('SELECT user_id, first_name, last_name, email, role_type, status, is_verified FROM users WHERE email = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $userRow = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($userRow) {
            $_SESSION['user_id'] = (int) ($userRow['user_id'] ?? 0);
            $_SESSION['role_type'] = (string) ($userRow['role_type'] ?? '');
            $_SESSION['is_verified'] = (int) ($userRow['is_verified'] ?? 0);

            if (can_access_dashboard($userRow)) {
                $targetPath = resolve_dashboard_path($userRow);
                header('Location: ' . $targetPath);
                exit();
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
    <script>
        async function pollApprovalStatus() {
            try {
                const response = await fetch('get_session_user.php', { credentials: 'same-origin' });
                const payload = await response.json();

                if (!response.ok || !payload.success || !payload.user) {
                    return;
                }

                if (payload.user.canAccessDashboard && payload.user.nextPath) {
                    window.location.replace(payload.user.nextPath);
                }
            } catch (error) {
                // Keep polling silently while user is on pending page.
            }
        }

        setInterval(pollApprovalStatus, 5000);
    </script>
</html>