<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];

    $conn = new mysqli("localhost", "root", "", "attendance_db");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $stmt = $conn->prepare("INSERT INTO attendance (name, time) VALUES (?, NOW())");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo "Logged attendance for $name";
}
?>