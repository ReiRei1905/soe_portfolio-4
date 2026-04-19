<?php
ob_start();
session_start();
include 'connect.php';
require_once __DIR__ . '/notification_service.php';
require_once __DIR__ . '/user_access_common.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function redirect_with_alert(string $message, string $target = 'index.php'): void {
    $_SESSION['flash_message'] = $message;
    header('Location: ' . $target, true, 303);
    exit();
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php', true, 303);
    exit();
}

$mailerAvailable = false;
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    require_once __DIR__ . '/mail_settings.php';
    $mailerAvailable = true;
} else {
    error_log("PHPMailer autoload not found at: $autoloadPath. Run 'composer install' in project root.");
}

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
        redirect_with_alert('Only APC email addresses are allowed for registration!', 'index.php?showSignup=1');
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
    redirect_with_alert('Password does not meet the policy: Minimum 12 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character (except !).', 'index.php?showSignup=1');
}
    
    // Only check for program_id if the role is 'student' or 'faculty'
    $program_id = null;
    if ($role_type === 'student' || $role_type === 'faculty') {
        if (isset($_POST['program_id'])) {
            $program_id = sanitizeInput($conn, $_POST['program_id']);
        } else {
            redirect_with_alert('Please select a valid program.', 'index.php?showSignup=1');
        }
    }

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        redirect_with_alert('Please fill in all required fields!', 'index.php?showSignup=1');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with_alert('Invalid email format!', 'index.php?showSignup=1');
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        redirect_with_alert('Email already exists. Please use a different email!', 'index.php?showSignup=1');
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
        redirect_with_alert('Invalid role type!', 'index.php?showSignup=1');
    }

    if (!$stmt2->execute()) {
        redirect_with_alert('Error inserting into role-specific table: ' . $stmt2->error, 'index.php?showSignup=1');
    }

    // Generate OTP
    //hatdogggwaaaaanyaaaaharhar
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['mail'] = $email;

    // Send OTP email
    $mail = new PHPMailer(true);
    try {
        configureSmtpMailer($mail, 'OTP Verification');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Your verification code";
        $mail->Body = "<p>Dear user,</p><h3>Your OTP code is $otp</h3><br><p>With regards,<br>SOE Portfolio</p>";

        $mail->send();
        redirect_with_alert("Registration successful! OTP sent to {$email}", 'verification.php');
    } catch (Exception $e) {
        error_log("OTP mail send failed for {$email}: " . $mail->ErrorInfo);

        if (isLocalMailFallbackEnabled()) {
            redirect_with_alert("Registration successful. SMTP failed, so local fallback is active. Your OTP is: {$otp}", 'verification.php');
        } else {
            redirect_with_alert("Registration successful, but OTP email could not be sent. Mailer Error: {$mail->ErrorInfo}", 'index.php?showSignup=1');
        }
    }
    exit();
}

    
}

if (isset($_POST['signIn'])) {
    $email = sanitizeInput($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        redirect_with_alert('Email and password are required!', 'index.php');
    }

    // CHANGED: select user by email only so we can show clear verification message
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // check verification status explicitly
        if ((int)$row['status'] !== 1) {
            redirect_with_alert('Account not verified. Please check your email for the OTP and complete verification before signing in.', 'index.php');
        }

        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) ($row['user_id'] ?? 0);
            $_SESSION['email'] = $row['email'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['role_type'] = $row['role_type'];
            $_SESSION['is_verified'] = (int) ($row['is_verified'] ?? 0);

            // Remember the last local owner account on this browser for localhost owner-mode fallback.
            setcookie('local_owner_email', (string) $row['email'], [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            $fullName = trim((string) $row['first_name'] . ' ' . (string) $row['last_name']);
            if ((int) ($_SESSION['user_id'] ?? 0) > 0 && $fullName !== '') {
                add_system_notification(
                    $conn,
                    (int) $_SESSION['user_id'],
                    "Welcome {$fullName}, you have officially logged in the system."
                );
            }
            
            $targetPath = resolve_effective_route($row);
            header("Location: {$targetPath}", true, 303);
            exit();
        } else {
            redirect_with_alert('Invalid email or password!', 'index.php');
        }
    } else {
        redirect_with_alert('Invalid email or password!', 'index.php');
    }
}
?>