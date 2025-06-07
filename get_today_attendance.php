<?php
include '../database/db.php';

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

$sql = "SELECT COUNT(*) AS total FROM attendance WHERE DATE(scan_time) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo $data['total'];
?>
