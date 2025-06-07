<?php
include '../database/db.php';

$input = json_decode(file_get_contents("php://input"), true);
$id = $conn->real_escape_string($input['student_id']);
$name = $conn->real_escape_string($input['student_name']);

$conn->query("UPDATE attendance SET student_name = '$name' WHERE student_id = '$id'");
$conn->query("UPDATE students SET student_name = '$name' WHERE student_id = '$id'");
echo "Name updated successfully!";
$conn->close();
?>

