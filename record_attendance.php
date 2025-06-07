<?php
session_start();
include('../database/db.php');

if (!isset($_SESSION['instructor_id'])) {
    die("Unauthorized");
}

$input = json_decode(file_get_contents("php://input"), true);
$student_id = $conn->real_escape_string($input['student_id']);
$student_name = $conn->real_escape_string($input['student_name']);

// Get instructor's subject
$stmt = $conn->prepare("SELECT subject FROM instructors WHERE id = ?");
$stmt->bind_param("i", $_SESSION['instructor_id']);
$stmt->execute();
$instructor = $stmt->get_result()->fetch_assoc();
$subject = $instructor['subject'];

// Check if student exists and belongs to this subject
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ? AND subject = ?");
$stmt->bind_param("ss", $student_id, $subject);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Check if already scanned today
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? AND DATE(scan_time) = ?");
    $stmt->bind_param("ss", $student_id, $today);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        echo "Already scanned today!";
    } else {
        // Get current time
        $currentTime = date('H:i:s');
        $cutoffTime = '08:00:00'; // 8:00 AM cutoff
        
        // Determine status based on time
        $status = strtotime($currentTime) <= strtotime($cutoffTime) ? 'present' : 'late';
        
        // Record attendance
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, student_name, scan_time, status) VALUES (?, ?, NOW(), ?)");
        $stmt->bind_param("sss", $student_id, $student_name, $status);
        
        if ($stmt->execute()) {
            echo "Attendance recorded successfully!";
        } else {
            echo "Error recording attendance!";
        }
    }
} else {
    echo "Student not found or not enrolled in this subject!";
}

$conn->close();
?> 