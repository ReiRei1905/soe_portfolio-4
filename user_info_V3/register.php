<?php
ob_start();
session_start();
include 'connect.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sanitizeInput($conn, $input) {
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($input))));
}

if (isset($_POST['signUp'])) {
    $first_name = sanitizeInput($conn, $_POST['fName']);
    $last_name = sanitizeInput($conn, $_POST['lName']);
    $middle_name = sanitizeInput($conn, $_POST['mName']);
    $suffix = sanitizeInput($conn, $_POST['suffix']);
    $year_of_enrollment = isset($_POST['year_of_enrollment']) ? sanitizeInput($conn, $_POST['year_of_enrollment']) : null;
    $id_number = sanitizeInput($conn, $_POST['id_number']);

    $email = sanitizeInput($conn, $_POST['email']);
    if (
    !preg_match('/@student\.apc\.edu\.ph$/', $email) &&
    !preg_match('/@apc\.edu\.ph$/', $email) &&
    !preg_match('/@gmail\.com$/', $email) // Allow Gmail for testing purposes
    ) {
        echo "Only APC email addresses are allowed for registration!";
        exit();
    }

    //aaaaaaaaaaaa

    $role_type = sanitizeInput($conn, $_POST['role_type']);
    $faculty_role = isset($_POST['faculty_role']) ? sanitizeInput($conn, $_POST['faculty_role']) : null;
    $password = $_POST['password'];

    // Add this block after getting $password
function isPasswordStrong($password) {
    if (strlen($password) < 12) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?`~]/', $password)) return false;
    if (strpos($password, '!') !== false) return false; // No exclamation point
    return true;
}

if (!isPasswordStrong($password)) {
    echo "<script>alert('Password does not meet the policy: Minimum 12 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character (except !).'); window.history.back();</script>";
    exit();
}
    
    // Only check for program_id if the role is 'student' or 'faculty'
    $program_id = null;
    if ($role_type === 'student' || $role_type === 'faculty') {
        if (isset($_POST['program_id'])) {
            $program_id = sanitizeInput($conn, $_POST['program_id']);
        } else {
            echo "Please select a valid program.";
            exit();
        }
    }

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        echo "Please fill in all required fields!";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format!";
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo " Email already exists. Please use a different email!";
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashedPassword, $role_type);

    if ($stmt->execute()) {
    $user_id = $conn->insert_id;

    // Insert into role-specific table BEFORE sending OTP and redirecting
    if ($role_type === 'student') {
        $stmt2 = $conn->prepare("INSERT INTO students (user_id, first_name, middle_name, last_name, suffix, year_of_enrollment, id_number, program_id, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("isssssiiss", $user_id, $first_name, $middle_name, $last_name, $suffix, $year_of_enrollment, $id_number, $program_id, $email, $hashedPassword);
    } elseif ($role_type === 'faculty') {
        $stmt2 = $conn->prepare("INSERT INTO faculty (user_id, first_name, middle_name, last_name, suffix, id_number, program_id, faculty_role, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("issssissss", $user_id, $first_name, $middle_name, $last_name, $suffix, $id_number, $program_id, $faculty_role, $email, $hashedPassword);
    } elseif ($role_type === 'admin') {
        $stmt2 = $conn->prepare("INSERT INTO admins (user_id, first_name, middle_name, last_name, suffix, id_number, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("issssiss", $user_id, $first_name, $middle_name, $last_name, $suffix, $id_number, $email, $hashedPassword);
    } else {
        echo "Invalid role type!";
        exit();
    }

    if (!$stmt2->execute()) {
        echo "Error inserting into role-specific table: " . $stmt2->error;
        exit();
    }

    // Generate OTP
    //hatdogggwaaaaanyaaaaharhar
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['mail'] = $email;

    // Send OTP email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'neloangelo4123@gmail.com'; // your Gmail
        $mail->Password = 'pflg rocs kbhp jtzl';    // your Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('neloangelo4123@gmail.com', 'OTP Verification');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Your verification code";
        $mail->Body = "<p>Dear user,</p><h3>Your OTP code is $otp</h3><br><p>With regards,<br>SOE Portfolio</p>";

        $mail->send();
        echo "<script>alert('Registration successful! OTP sent to $email'); window.location.replace('verification.php');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Registration successful, but OTP email could not be sent. Mailer Error: {$mail->ErrorInfo}');</script>";
    }
    exit();
}

    
}

if (isset($_POST['signIn'])) {
    $email = sanitizeInput($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo "Email and password are required!";
        exit();
    }

    // Only select users who are verified (status = 1)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['email'] = $row['email'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['role_type'] = $row['role_type'];
            
            // Redirect based on role or email domain
            if ($row['role_type'] === 'student' || 
                preg_match('/@student\.apc\.edu\.ph$/', $row['email'])) {
                header("Location: ../Student%20side%20portfolio%20management/student%20hompage/student_Homepage.html");
                exit();
            } elseif ($row['role_type'] === 'faculty' ||
                preg_match('/@apc\.edu\.ph$/', $row['email'])) {
                header("Location: review_user.php");
                exit();
            } else {
                if (preg_match('/@gmail\.com$/', $row['email'])) { // Allow Gmail for testing purposes
                    header("Location: review_user.php"); // Default or admin
                    exit();
                }
            }
        } else {
            echo " Invalid email or password!";
        }
    } else {
        echo " Invalid email or password or account not verified!";
    }
}
?>