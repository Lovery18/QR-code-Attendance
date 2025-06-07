<?php
session_start();
include '../database/db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$student_id = $conn->real_escape_string($data['student_id']);

// Get instructor's subject
$instructor_id = $_SESSION['instructor_id'];
$instructor_query = "SELECT subject FROM instructors WHERE id = ?";
$stmt = $conn->prepare($instructor_query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$instructor_result = $stmt->get_result();
$instructor = $instructor_result->fetch_assoc();
$instructor_subject = $instructor['subject'];

// Check if student exists and is in the instructor's subject
$student_query = "SELECT * FROM students WHERE student_id = ? AND subject = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("ss", $student_id, $instructor_subject);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    
    // Check if student already attended today
    $attendance_query = "SELECT * FROM attendance WHERE student_id = ? AND DATE(scan_time) = CURDATE()";
    $stmt = $conn->prepare($attendance_query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $attendance_result = $stmt->get_result();
  
    if ($attendance_result->num_rows == 0) {
        // Record attendance
        $insert_query = "INSERT INTO attendance (student_id, student_name) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ss", $student_id, $student['student_name']);
        if ($stmt->execute()) {
            echo "✅ Attendance recorded for " . $student['student_name'];
        } else {
            echo "⚠️ Error recording attendance";
        }
  } else {
        echo "⚠️ Already attended today";
  }
} else {
    echo "❌ Student not found or not enrolled in this subject";
}

$conn->close();
?>
