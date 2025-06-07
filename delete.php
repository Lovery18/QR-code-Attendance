<?php
include '../database/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['student_id'];

$deleteAttendance = $conn->query("DELETE FROM attendance WHERE student_id = '$id'");

$deleteStudent = $conn->query("DELETE FROM students WHERE student_id = '$id'");

if ($deleteAttendance && $deleteStudent) {
    echo "Record deleted from both tables successfully.";
} else {
    echo "Error deleting record: " . $conn->error;
}

$conn->close();
?>