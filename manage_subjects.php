<?php
include('../database/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    switch ($action) {
        case 'edit':
            if (isset($_POST['old_name']) && isset($_POST['new_name'])) {
                $old_name = $_POST['old_name'];
                $new_name = $_POST['new_name'];

                // Check if new name already exists
                $check = $conn->prepare("SELECT id FROM subjects WHERE subject = ? AND subject != ?");
                $check->bind_param("ss", $new_name, $old_name);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $response['message'] = 'Subject name already exists!';
                } else {
                    // Update subject name
                    $stmt = $conn->prepare("UPDATE subjects SET subject = ? WHERE subject = ?");
                    $stmt->bind_param("ss", $new_name, $old_name);
                    
                    // Also update instructor assignments
                    $stmt2 = $conn->prepare("UPDATE instructors SET subject = ? WHERE subject = ?");
                    $stmt2->bind_param("ss", $new_name, $old_name);
                    
                    if ($stmt->execute() && $stmt2->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Subject updated successfully!';
                    } else {
                        $response['message'] = 'Error updating subject: ' . $conn->error;
                    }
                }
            }
            break;

        case 'delete':
            if (isset($_POST['subject'])) {
                $subject = $_POST['subject'];
                
                // First update instructors to remove this subject
                $stmt = $conn->prepare("UPDATE instructors SET subject = NULL WHERE subject = ?");
                $stmt->bind_param("s", $subject);
                $stmt->execute();
                
                // Then delete the subject
                $stmt = $conn->prepare("DELETE FROM subjects WHERE subject = ?");
                $stmt->bind_param("s", $subject);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Subject deleted successfully!';
                } else {
                    $response['message'] = 'Error deleting subject: ' . $conn->error;
                }
            }
            break;

        default:
            $response['message'] = 'Invalid action';
    }

    echo json_encode($response);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request method']); 