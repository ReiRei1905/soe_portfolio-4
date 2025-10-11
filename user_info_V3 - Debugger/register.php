<?php
ob_start();
session_start();
include 'connect.php';

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
    $role_type = sanitizeInput($conn, $_POST['role_type']);
    $faculty_role = isset($_POST['faculty_role']) ? sanitizeInput($conn, $_POST['faculty_role']) : null;
    $password = $_POST['password'];

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
        echo "Email already exists. Please use a different email!";
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashedPassword, $role_type);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id;

        if ($role_type === 'student') {
            $stmt = $conn->prepare("INSERT INTO students (user_id, first_name, middle_name, last_name, suffix, year_of_enrollment, id_number, program_id, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssiiss", $user_id, $first_name, $middle_name, $last_name, $suffix, $year_of_enrollment, $id_number, $program_id, $email, $hashedPassword);
        } elseif ($role_type === 'faculty') {
            $stmt = $conn->prepare("INSERT INTO faculty (user_id, first_name, middle_name, last_name, suffix, id_number, program_id, faculty_role, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssissss", $user_id, $first_name, $middle_name, $last_name, $suffix, $id_number, $program_id, $faculty_role, $email, $hashedPassword);
        } elseif ($role_type === 'admin') {
            $stmt = $conn->prepare("INSERT INTO admins (user_id, first_name, middle_name, last_name, suffix, id_number, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssiss", $user_id, $first_name, $middle_name, $last_name, $suffix, $id_number, $email, $hashedPassword);
        } else {
            echo "Invalid role type!";
            exit();
        }

        if ($stmt->execute()) {
            echo "Registration successful!";
            header("Location: index.php");
            exit();
        } else {
            echo "Error inserting into role-specific table: " . $stmt->error;
        }

    } else {
        echo "Error inserting into users table: " . $stmt->error;
    }
}

if (isset($_POST['signIn'])) {
    $email = sanitizeInput($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo "<script>alert('Email and password are required!'); window.location.href='index.php';</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['email'] = $row['email'];
            // Redirect to OTP page (if you want OTP verification)
            header("Location: verify_otp.php");
            exit();
        } else {
            echo "<script>alert('Invalid email or password!'); window.location.href='index.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid email or password!'); window.location.href='index.php';</script>";
        exit();
    }
}
?>