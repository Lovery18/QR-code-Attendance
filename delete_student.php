<?php
include '../database/db.php';

// Get the database ID from URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

if(empty($id)) {
    die("No ID provided");
}

// Get the student_id first
$result = $conn->query("SELECT student_id FROM students WHERE id = '$id'");
if($result && $row = $result->fetch_assoc()) {
    $student_id = $row['student_id'];
    
    // Delete from attendance first
    $deleteAttendance = $conn->query("DELETE FROM attendance WHERE student_id = '$student_id'");

    // Then delete from students
    $deleteStudent = $conn->query("DELETE FROM students WHERE id = '$id'");

    if($deleteAttendance && $deleteStudent) {
        header("Location: admindashboard.php");
        exit();
} else {
    echo "Error deleting record: " . $conn->error;
    }
} else {
    echo "Student not found";
}

$conn->close();
?>