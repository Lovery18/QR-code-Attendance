<?php
include '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $student_name = $conn->real_escape_string($_POST['student_name']);
  $student_id = $conn->real_escape_string($_POST['student_id']);

  $check = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'");
  if ($check->num_rows == 0) {
    $conn->query("INSERT INTO students (student_id, student_name) VALUES ('$student_id', '$student_name')");
    echo "✅ Registered successfully!";
  } else {
    echo "⚠️ ID already exists.";
  }

  $conn->close();
}
?>