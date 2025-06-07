<?php
include '../database/db.php';

$res = $conn->query("SELECT student_id FROM students ORDER BY student_id DESC LIMIT 1");
$next_id = "2025-0000";
if ($res && $res->num_rows > 0) {
  $row = $res->fetch_assoc();
  $last = intval(substr($row['student_id'], 5));
  $next_id = '2025-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
}
echo json_encode(["next_id" => $next_id]);
$conn->close();
?>
