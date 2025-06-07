<?php
session_start();
include('../database/db.php');

if (!isset($_SESSION['instructor_id'])) {
    die("Not authorized");
}

// Get instructor's details including schedule
$stmt = $conn->prepare("SELECT instructor_name, subject, day, time FROM instructors WHERE id = ?");
$stmt->bind_param("i", $_SESSION['instructor_id']);
$stmt->execute();
$result = $stmt->get_result();
$instructor = $result->fetch_assoc();

// Verify that we have all the required data
if (!$instructor || !$instructor['time'] || !$instructor['day']) {
    error_log("Missing instructor schedule data: " . print_r($instructor, true));
}

$subject = $instructor['subject'];

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_' . $subject . '_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add School Header with complete details
fputcsv($output, array('SAN ISIDRO NATIONAL HIGH SCHOOL'));
fputcsv($output, array('Instructor: ' . $instructor['instructor_name']));
fputcsv($output, array('Subject: ' . $subject));
fputcsv($output, array('Schedule Day: ' . $instructor['day']));
fputcsv($output, array('Schedule Time: ' . $instructor['time']));
fputcsv($output, array('')); // Empty line for spacing

// Weekly Data
fputcsv($output, array('WEEKLY ATTENDANCE REPORT'));
fputcsv($output, array('Week', 'Total Students Present'));

$stmt = $conn->prepare("SELECT WEEK(a.scan_time) AS week, COUNT(DISTINCT a.student_id) AS count
                       FROM attendance a
                       INNER JOIN students s ON a.student_id = s.student_id
                       WHERE YEAR(a.scan_time) = YEAR(CURDATE())
                       AND s.subject = ?
                GROUP BY week
                       ORDER BY week");
$stmt->bind_param("s", $subject);
$stmt->execute();
$weeklyResult = $stmt->get_result();

while ($row = $weeklyResult->fetch_assoc()) {
    fputcsv($output, array('Week ' . $row['week'], $row['count']));
}

// Add spacing between sections
fputcsv($output, array(''));
fputcsv($output, array(''));

// Monthly Data
fputcsv($output, array('MONTHLY ATTENDANCE REPORT'));
fputcsv($output, array('Month', 'Total Students Present'));

$stmt = $conn->prepare("SELECT DATE_FORMAT(a.scan_time, '%M %Y') AS month, 
                              COUNT(DISTINCT a.student_id) AS count
                       FROM attendance a
                       INNER JOIN students s ON a.student_id = s.student_id
                       WHERE YEAR(a.scan_time) = YEAR(CURDATE())
                       AND s.subject = ?
                       GROUP BY MONTH(a.scan_time), YEAR(a.scan_time)
                       ORDER BY YEAR(a.scan_time), MONTH(a.scan_time)");
$stmt->bind_param("s", $subject);
$stmt->execute();
$monthlyResult = $stmt->get_result();

while ($row = $monthlyResult->fetch_assoc()) {
    fputcsv($output, array($row['month'], $row['count']));
}

// Add spacing between sections
fputcsv($output, array(''));
fputcsv($output, array(''));

// Yearly Data
fputcsv($output, array('YEARLY ATTENDANCE REPORT'));
fputcsv($output, array('Year', 'Total Students Present'));

$stmt = $conn->prepare("SELECT YEAR(a.scan_time) AS year, 
                              COUNT(DISTINCT a.student_id) AS count
                       FROM attendance a
                       INNER JOIN students s ON a.student_id = s.student_id
                       WHERE s.subject = ?
                GROUP BY year
                       ORDER BY year");
$stmt->bind_param("s", $subject);
$stmt->execute();
$yearlyResult = $stmt->get_result();

while ($row = $yearlyResult->fetch_assoc()) {
    fputcsv($output, array($row['year'], $row['count']));
}

// Add spacing before detailed records
fputcsv($output, array(''));
fputcsv($output, array(''));

// Detailed Attendance Records
fputcsv($output, array('DETAILED ATTENDANCE RECORDS'));
fputcsv($output, array('Student ID', 'Student Name', 'Date & Time', 'Status'));

$stmt = $conn->prepare("SELECT a.student_id, s.student_name, a.scan_time, a.status 
                       FROM attendance a
                       INNER JOIN students s ON a.student_id = s.student_id
                       WHERE s.subject = ?
                       ORDER BY a.scan_time DESC");
$stmt->bind_param("s", $subject);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        $row['student_id'],
        $row['student_name'],
        $row['scan_time'],
        $row['status']
    ));
}

fclose($output);
$conn->close();
?>