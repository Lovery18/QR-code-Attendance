<?php
include('../database/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: instructor_reg.php");
        exit();
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM instructors WHERE instructor_name = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Email already registered";
        header("Location: instructor_reg.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert instructor data
    $stmt = $conn->prepare("INSERT INTO instructors (instructor_name, password, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ss", $email, $hashed_password);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! Please wait for admin approval.";
        header("Location: instructor_login.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed: " . $conn->error;
        header("Location: instructor_reg.php");
        exit();
    }
} else {
    header("Location: instructor_reg.php");
    exit();
}
?> 