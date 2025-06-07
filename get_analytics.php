<?php
session_start();
include('../database/db.php');

if (!isset($_SESSION['instructor_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

// Get instructor's subject
$stmt = $conn->prepare("SELECT subject FROM instructors WHERE id = ?");
$stmt->bind_param("i", $_SESSION['instructor_id']);
$stmt->execute();
$instructor = $stmt->get_result()->fetch_assoc();
$subject = $instructor['subject'];

// Get last 7 days attendance
$labels = [];
$values = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime($date));
    
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT a.student_id) as count 
                           FROM attendance a 
                           JOIN students s ON a.student_id = s.student_id 
                           WHERE s.subject = ? AND DATE(a.scan_time) = ?");
    $stmt->bind_param("ss", $subject, $date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $values[] = $result['count'];
}

echo json_encode([
    'labels' => $labels,
    'values' => $values
]);

$conn->close();
?> 