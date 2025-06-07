<?php
include('../database/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = $_POST['subject'];
    
    // Check if subject already exists
    $check = $conn->prepare("SELECT subject FROM subjects WHERE subject = ?");
    $check->bind_param("s", $subject);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Subject already exists']);
    } else {
        $stmt = $conn->prepare("INSERT INTO subjects (subject) VALUES (?)");
        $stmt->bind_param("s", $subject);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Subject added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding subject']);
        }
    }
    exit();
}
?> 