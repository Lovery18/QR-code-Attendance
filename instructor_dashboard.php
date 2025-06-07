<?php
session_start();
$conn = new mysqli("localhost", "root", "", "attendance_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables with default values
$subject = "Not Set";
$schedule_day = "Not Set";
$schedule_time = "Not Set";
$instructor_name = "Not Set";

// Check if instructor is logged in and get their details
if (isset($_SESSION['instructor_id'])) {
    $instructor_id = $_SESSION['instructor_id'];
    $instructorQuery = "SELECT * FROM instructors WHERE id = ?";
    $stmt = $conn->prepare($instructorQuery);
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($instructor = $result->fetch_assoc()) {
        $subject = $instructor['subject'];
        $schedule_day = $instructor['day'];
        $schedule_time = $instructor['time'];
        $instructor_name = $instructor['instructor_name'];
    } else {
        // Redirect if instructor not found in database
        header("Location: instructor_login.php");
        exit();
    }
} else {
    // Redirect if not logged in
    header("Location: instructor_login.php");
    exit();
}

/// Total Students
$stmt = $conn->prepare("SELECT COUNT(*) AS total_student FROM students WHERE subject = ?");
$stmt->bind_param("s", $subject);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$totalStudents = $userData['total_student'];

// Attendance Today
$stmt = $conn->prepare("SELECT COUNT(DISTINCT a.student_id) AS attendance_today 
                       FROM attendance a 
                       INNER JOIN students s ON a.student_id = s.student_id 
                       WHERE DATE(a.scan_time) = CURDATE() 
                       AND s.subject = ?");
$stmt->bind_param("s", $subject);
$stmt->execute();
$todayData = $stmt->get_result()->fetch_assoc();
$attendanceToday = $todayData['attendance_today'];

// Absent Today
$absentToday = $totalStudents - $attendanceToday;


// Weekly Attendance (by Week Number)
$weeklyLabels = [];
$weeklyData = [];

$weeklyQuery = "SELECT WEEK(a.scan_time) AS week, COUNT(DISTINCT a.student_id) AS count
                FROM attendance a
                INNER JOIN students s ON a.student_id = s.student_id
                WHERE YEAR(a.scan_time) = YEAR(CURDATE())
                AND s.subject = ?
                GROUP BY week
                ORDER BY week";
$stmt = $conn->prepare($weeklyQuery);
$stmt->bind_param("s", $subject);
$stmt->execute();
$weeklyResult = $stmt->get_result();
while ($row = $weeklyResult->fetch_assoc()) {
    $weeklyLabels[] = "Week " . $row['week'];
    $weeklyData[] = $row['count'];
}


// Monthly Attendance
$monthlyLabels = [];
$monthlyData = [];

$monthlyQuery = "SELECT DATE_FORMAT(a.scan_time, '%b') AS month, COUNT(DISTINCT a.student_id) AS count
                 FROM attendance a
                 INNER JOIN students s ON a.student_id = s.student_id
                 WHERE YEAR(a.scan_time) = YEAR(CURDATE())
                 AND s.subject = ?
                 GROUP BY MONTH(a.scan_time)
                 ORDER BY MONTH(a.scan_time)";
$stmt = $conn->prepare($monthlyQuery);
$stmt->bind_param("s", $subject);
$stmt->execute();
$monthlyResult = $stmt->get_result();
while ($row = $monthlyResult->fetch_assoc()) {
    $monthlyLabels[] = $row['month'];
    $monthlyData[] = $row['count'];
}


// Yearly Attendance
$yearlyLabels = [];
$yearlyData = [];

$yearlyQuery = "SELECT YEAR(a.scan_time) AS year, COUNT(DISTINCT a.student_id) AS count
                FROM attendance a
                INNER JOIN students s ON a.student_id = s.student_id
                WHERE s.subject = ?
                GROUP BY year
                ORDER BY year";
$stmt = $conn->prepare($yearlyQuery);
$stmt->bind_param("s", $subject);
$stmt->execute();
$yearlyResult = $stmt->get_result();
while ($row = $yearlyResult->fetch_assoc()) {
    $yearlyLabels[] = $row['year'];
    $yearlyData[] = $row['count'];
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QR Attendance System</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

  <style>
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }
   
    body {
      display: flex;
      height: auto;
      flex-shrink: 0;
      color: #ffffff;
      background-color: #0B0C10;
      background-repeat: no-repeat;
      background-position: center center;
      background-size: cover;
      justify-content: center;
      align-items: center;
    }
    
    .table-container {
      background: rgba(0, 0, 40, 0.7);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      padding: 20px;
      margin: 20px 0;
      box-shadow: 0 0 15px rgba(255, 0, 204, 0.2);
    }

    .table-container h2 {
      color: #66FCF1;
      font-size: 1.5em;
      margin-bottom: 20px;
      text-shadow: 0 0 5px rgba(102, 252, 241, 0.5);
    }

    .table-container .table-wrapper {
      margin: 0;
    }

    .table-container table {
      margin: 0;
      width: 100%;
    }
    
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }


    .card-header h2 {
      font-size: 18px;
      color: #ecf0f1;
    }


    .card-badge {
      background: rgba(52, 152, 219, 0.2);
      color: #3498db;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
    }


    .card-footer {
      margin-top: 15px;
      padding-top: 15px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    canvas {
      display: block;
      width: 100% !important;
      height: 100% !important;
    }
        
    .content {
      flex: 1;
      padding: 30px;
      overflow-y: auto;
    }
    .chart-container {
      position: relative;
      height: 380px;
      width: 100%;
    }
    
    .export-btn {
      padding: 8px 15px;
      border-radius: 6px;
      background:rgb(69, 4, 106);
      color: white;
      border: none;
      cursor: pointer;
      transition: background 0.3s;
    }

    .export-btn:hover {
      background:rgb(2, 35, 57);
    }

    .filter-select {
      padding: 8px 15px;
      border-radius: 6px;
      background: rgba(255, 255, 255, 0.1);
      color: white;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
      
    .hidden {
      display: none;
    }

    #homeTab {
      padding: 30px;
      font-family: 'Segoe UI', sans-serif;
      color: #fff;
    }
       
    iframe, #reader {
      width: 100%;
      border: none;
      margin: 20px 0;
      background-color: rgba(11, 12, 16, 0.95);
      border-radius: 12px;
      text-align: center;
      justify-content: center;
    }

    input[type="text"] {
      padding: 8px;
      margin: 10px 0;
      width: 100%;
    }

    .layout {
      display: flex;
      align-items: stretch;
      width: 100%;
      min-height: 100vh;
    }

    .modal {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background-color: rgba(0, 0, 0, 0.75);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 999;
    }

    .modal-content {
      background: #0b0c10;
      padding: 20px;
      border-radius: 10px;
      width: 1000px;
      max-width: 1000px;
      max-height: 90vh;
      overflow: auto;
      box-shadow: 0 0 15px #45A29E;
      color: #fff;
    }

    .close {
      float: right;
      font-size: 28px;
      cursor: pointer;
      color: #fff;
    }

    #searchInput {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border: none;
      border-radius: 5px;
    }

    #downloadButtons {
      margin-bottom: 10px;
    }
       
    #qr-code {
      margin: 20px 0;
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      text-align: center;
      justify-content: center;
    }

    .reports-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .report-controls {
      display: flex;
      gap: 15px;
    }
   
    .report-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 25px;
    }

    .report-card {
      background: rgba(30, 40, 50, 0.7);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      transition: transform 0.3s;
    }

    .report-card:hover {
      transform: translateY(-5px);
    }
    
    #scanResult {
      font-weight: bold;
      color: #00ffcc;
      margin-top: 10px;
      text-align: center;
    }
    
    .section {
      margin-top: 20px;
    }

    .section {
      display: flex;
      flex-wrap: wrap;
      gap: 60px;
      margin-bottom: 30px;
    }

    .sidebar {
      min-height: 110vh;
      width: 60px;
      background-color: #2c3e50;
      color: #ecf0f1;
      padding-top: 10px;
      transition: width 0.3s ease;
      overflow: hidden;
    }

    .sidebar.expanded {
      width: 170px;
    }

    .sidebar .toggle-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      padding: 10px;
      background-color: #1a252f;
      border: none;
      color: #fff;
      cursor: pointer;
      font-size: 18px;
    }

    .sidebar:not(.expanded) ul li span {
      display: none;
    }

    .sidebar ul {
      list-style: none;
    }

    .sidebar ul li {
      padding: 15px;
      cursor: pointer;
      display: flex;
      align-items: center;
      transition: 0.3s;
      position: relative;
    }

    .sidebar ul li:hover {
      background: #34495e;
    }

    .sidebar ul li i {
      width: 30px;
      text-align: center;
      margin-right: 10px;
      font-size: 18px;
    }

    .sidebar ul li span {
      white-space: nowrap;
    }

    .sidebar:not(.expanded) ul li:hover::after {
      content: attr(data-tooltip);
      position: absolute;
      left: 65px;
      background: #000;
      color: #fff;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      white-space: nowrap;
      z-index: 1000;
    }

    .stat-container {
      justify-content: space-between;
    }

    .stat-box {
      flex: 1 1 200px;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      color: white;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      display: inline-block;
      width: 200px;
      margin: 10px;
      background: grey;
    }

    .stat-box h3 {
      margin-bottom: 10px;
      font-size: 18px;
    }

    .stat-box p {
      font-size: 24px;
      font-weight: bold;
    }

    .stats {
      display: flex;
      justify-content: space-between;
      font-size: 14px;
      color: #bdc3c7;
    }

    .stats i {
      margin-right: 5px;
    }

    .tab {
      display: none;
    }

    .tab.active {
      display: block;
    }

    .title {
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 25px;
      color: #ecf0f1;
    }
   
    table {
      width: 90%;
      background-color: white;
      color: black;
      border-collapse: collapse;
    }
   
    th {
      background-color: #2980b9;
      color: white;
      padding: 10px;
    }
   
    td {
      padding: 10px;
      border: 1px solid #ddd;
    }

    iframe, #reader {
      width: 100%;
      border: none;
      margin: 20px 0;
      background-color: rgba(11, 12, 16, 0.95);
      border-radius: 12px;
      text-align: center;
      justify-content: center;
    }

    input[type="text"], button {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border-radius: 10px;
      border: none;
      font-size: 16px;
      outline: none;
      color: black;
    }

    input[type="text"] {
      background-color:rgb(29, 27, 73);
      color: black;
      border: 1px solid #444;
    }

    button {
      background: #0f0c29;
      color: white;
      font-weight: bold;
      transition: 0.3s ease;
      cursor: pointer;
    }

    button:hover {
      transform: scale(1.03);
      box-shadow: 0 0 10px #ff00cc;
    }

    .modal-content h2 {
      margin-top: 0;
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover {
      color: white;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    #scanResult {
      font-weight: bold;
      color: #00ffcc;
      margin-top: 10px;
      text-align: center;
    }

    .section {
      display: flex;
      flex-wrap: wrap;
      gap: 60px;
      margin-bottom: 30px;
    }
    
    .section {
      margin-top: 20px;
    }
    input[type="text"] {
      padding: 8px;
      margin: 10px 0;
      width: 100%;
    }

    .sidebar {
      width: 60px;
      background-color: #2c3e50;
      color: #ecf0f1;
      padding-top: 10px;
      transition: width 0.3s ease;
      overflow: hidden;
    }

    .sidebar.expanded {
      width: 170px;
    }

    .sidebar .toggle-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      padding: 10px;
      background-color: #1a252f;
      border: none;
      color: #fff;
      cursor: pointer;
      font-size: 18px;
    }

    .sidebar:not(.expanded) ul li span {
      display: none;
    }

    .sidebar ul {
      list-style: none;
    }

    .sidebar ul li {
      padding: 15px;
      cursor: pointer;
      display: flex;
      align-items: center;
      transition: 0.3s;
      position: relative;
    }

    .sidebar ul li:hover {
      background: #34495e;
    }

    .sidebar ul li i {
      width: 30px;
      text-align: center;
      margin-right: 10px;
      font-size: 18px;
    }

    .sidebar ul li span {
      white-space: nowrap;
    }

    .sidebar:not(.expanded) ul li:hover::after {
      content: attr(data-tooltip);
      position: absolute;
      left: 65px;
      background: #000;
      color: #fff;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      white-space: nowrap;
      z-index: 1000;
    }

    .stat-container {
      justify-content: space-between;
    }

    .stat-box {
      flex: 1 1 200px;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      color: white;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .stat-box h3 {
      margin-bottom: 10px;
      font-size: 18px;
    }

    .stat-box p {
      font-size: 24px;
      font-weight: bold;
    }

    .stat-box {
      display: inline-block;
      width: 200px;
      padding: 20px;
      margin: 10px;
      background: grey;
      color: #fff;
      text-align: center;
      border-radius: 8px;
    }

    .tab {
      display: none;
    }

    .tab.active {
      display: block;
    }

    .title {
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 25px;
      color: #ecf0f1;
    }

    #usersTable tbody tr.selected {
      background-color:rgba(0, 0, 40, 0.7);
      color: #fff;
    }

    .table-wrapper {
      width: 100%;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: rgba(0, 0, 40, 0.7);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 0 15px rgba(255, 0, 204, 0.2);
      color: #fff;
    }

    th, td {
      font-family: Verdana;
      padding: 12px 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      color: white;
      text-align: center;
      white-space: nowrap;
    }

    th {
      background:rgb(25, 77, 73);
      color: #fff;
      font-size: 16px;
    }

    td[contenteditable='true'] {
      background-color: rgba(255, 255, 255, 0.07);
      cursor: text;
      border-radius: 5px;
    }

    tr:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }

    .download-btn {
      background: rgb(69, 4, 106);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background 0.3s ease;
    }

    .download-btn:hover {
      background: rgb(89, 10, 136);
    }

    .download-btn i {
      font-size: 16px;
    }

    /* Add these styles for the schedule box */
    .schedule-box {
        background: rgba(25, 77, 73, 0.3);
        border: 2px solid #45A29E;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        text-align: center;
    }

    .schedule-box h3 {
        color: #66FCF1;
        margin-bottom: 15px;
        font-size: 1.2em;
    }

    .schedule-info {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 10px;
    }

    .schedule-item {
        background: rgba(69, 162, 158, 0.2);
        padding: 10px 20px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .schedule-item i {
        color: #66FCF1;
    }

    /* Add styles for instructor name display */
    .welcome-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px;
        background: rgba(25, 77, 73, 0.3);
        border-radius: 10px;
        border: 1px solid #45A29E;
    }

    .welcome-text {
        color: #66FCF1;
        font-size: 1.2em;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .welcome-text i {
        color: #45A29E;
        font-size: 1.5em;
    }

    .instructor-info {
        display: flex;
        align-items: center;
        gap: 15px;
        color: #C5C6C7;
    }

    .instructor-info i {
        color: #45A29E;
    }
  </style>
</head>
<body>
  <div class="layout">
    <div class="sidebar" id="sidebar">
      <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
      <hr style="margin-bottom: 20px; border: none; border-top: 1px solid #ccc;">
      <ul>
        <li onclick="showTab('homeTab')" data-tooltip="Home"><i class="fas fa-home"></i> <span>Home</span></li>
        <li onclick="showTab('studentTab')" data-tooltip="Student"><i class="fas fa-users"></i> <span>Student</span></li>
        <li onclick="showTab('attendanceTab')" data-tooltip="Attendance"><i class="fas fa-exclamation-triangle"></i> <span>Attendance</span></li>
        <li onclick="showTab('reportsTab')" data-tooltip="Reports"><i class="fas fa-chart-pie"></i> <span>Reports</span></li>
        <li onclick="window.location.href='../homepage/homepage.html'" data-tooltip="Logout"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></li>
      </ul>
    </div>
 
    <div class="content">
      <!-- Home Tab -->
      <div id="homeTab" class="tab active">
        <div class="welcome-header">
          <div class="welcome-text">
            <i class="fas fa-user-circle"></i>
            Welcome back!
          </div>
          <div class="instructor-info">
            <i class="fas fa-chalkboard-teacher"></i>
            <span id="instructor_name"><?php echo htmlspecialchars($instructor_name); ?></span>
          </div>
        </div>

        <h1 class="title">Dashboard Overview</h1>
        <hr>
        <br>
        
        <!-- Add Schedule Box -->
        <div class="schedule-box">
            <h3><i class="fas fa-chalkboard-teacher"></i> Current Schedule</h3>
            <div class="schedule-info">
                <div class="schedule-item">
                    <i class="fas fa-book"></i>
                    <span id="subject_name"><?php echo htmlspecialchars($subject); ?></span>
                </div>
                <div class="schedule-item">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo htmlspecialchars($schedule_day); ?></span>
                </div>
                <div class="schedule-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo htmlspecialchars($schedule_time); ?></span>
                </div>
            </div>
        </div>

        <div class="section stat-container">
          <div class="stat-box bg-blue">
            <h3><i class="fas fa-user-graduate"></i> Total Students</h3>
            <p><?php echo $totalStudents; ?></p>
          </div>
          <div class="stat-box bg-green">
            <h3><i class="fas fa-user-check"></i> Today's Attendance</h3>
            <p><?php echo $attendanceToday; ?></p>
          </div>
          <div class="stat-box bg-red">
            <h3><i class="fas fa-user-times"></i> Today's Absent</h3>
            <p><?php echo $absentToday; ?></p>
          </div>
        </div>
       
        <form action="register.php" method="POST">
          <input type="text" name="student_id" id="student_id" readonly placeholder="Student ID">
          <input type="text" name="student_name" id="student_name" placeholder="Student Name" required>
          <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
          <button type="submit"><i class="fa-solid fa-plus-circle"></i> Register</button>
        </form>
      <div id="qr-code"></div>
      <button id="downloadBtn" class="hidden"><i class="fa-solid fa-download"></i> Download QR Code</button>

    </div>


      <!-- Student Tab -->
      <div id="studentTab" class="tab">
        <h1 class="title">Manage Users</h1>

        <!-- Search + Action Icons Container -->
        <div style="display: flex; align-items: center; justify-content: space-between; width: 90%; margin-bottom: 10px;">
          <input type="text" id="searchUser" onkeyup="filterUsers()" placeholder="Search users..."
            style="padding: 10px; width: 30%; border-radius: 8px; border: 1px solid #ccc;">


          <!-- Icons -->
           <input type="hidden" id="selectedStudentId">
           <input type="hidden" id="selectedDbId">

          <div style="display: flex; gap: 50px;">
            <button title="Deactivate" onclick="handleDeactivate()" style="background: none; border: none; cursor: pointer;">
              <i class="fas fa-user-slash" style="color: white; font-size: 20px;"></i>
            </button>
            <button title="Delete" onclick="handleDelete()" style="background: none; border: none; cursor: pointer;">
              <i class="fas fa-user-times" style="color: white; font-size: 20px;"></i>
            </button>
            <button title="View Profile" onclick="handleViewProfile()" style="background: none; border: none; cursor: pointer;">
              <i class="fas fa-id-badge" style="color: white; font-size: 20px;"></i>
            </button>
          </div>
        </div>

        <!-- Profile Modal -->
        <div id="profileModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Student Profile</h2>
            <p><strong>Student ID:</strong> <span id="modalStudentId"></span></p>
            <p><strong>Name:</strong> <span id="modalStudentName"></span></p>
            <!-- Add more fields as needed -->
          </div>
        </div>
        
        <div id="userList">
          <table id="usersTable" border="1" cellpadding="10" cellspacing="0">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Name</th>
              </tr>
            </thead>
            <tbody>
            <?php
              $conn = new mysqli("localhost", "root", "", "attendance_db");
              if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
              }

              $userQuery = "SELECT id, student_id, student_name FROM students WHERE subject = ? ORDER BY id DESC";
              $stmt = $conn->prepare($userQuery);
              $stmt->bind_param("s", $subject);
              $stmt->execute();
              $result = $stmt->get_result();

              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  $data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                  echo "<tr onclick='selectStudent($data)'>";
                  echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                  echo "</tr>";
                }
              } else {
                echo "<tr><td colspan='2'>No users found.</td></tr>";
              }

              $conn->close();
            ?>
            </tbody>
          </table>
        </div>
      </div>


      <!-- Attendance Tab -->
      <div id="attendanceTab" class="tab">
        <h1 class="title"><i class="fa-solid fa-list-check"></i> Attendance Management</h1>
        <br>
          <br>
          <div id="reader" style="width: 100%; max-width: 300px; margin: auto;"></div>
          <button onclick="startScan()"><i class="fa-solid fa-camera"></i> Start QR</button>
          <p id="scanResult"></p>

          <div class="section stat-container">
            <div class="stat-box bg-blue" onclick="openModal('all')">
            <h3><i class="fas fa-user-graduate"></i> Total Students</h3>
            <p><?php echo $totalStudents; ?></p>
          </div>
          <div class="stat-box bg-green" onclick="openModal('present')">
            <h3><i class="fas fa-user-check"></i> Today's Attendance</h3>
            <p><?php echo $attendanceToday; ?></p>
          </div>
          <div class="stat-box bg-red" onclick="openModal('absent')">
            <h3><i class="fas fa-user-times"></i> Today's Absent</h3>
            <p><?php echo $absentToday; ?></p>
          </div>
        </div>

        <!-- Attendance Records Table -->
        <div class="table-container" style="margin-top: 30px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2><i class="fas fa-clipboard-list"></i> Attendance Records</h2>
            <button onclick="downloadAttendancePDF()" class="download-btn">
              <i class="fas fa-download"></i> Download Attendance
            </button>
          </div>
          <?php include 'view_attendance.php'; ?>
        </div>

        <!-- Modal HTML -->
        <div id="attendanceModal" class="modal" style="display:none;">
          <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Filtered Attendance</h2>
            <input type="text" id="searchInput" placeholder="Search student..." onkeyup="filterTable()" />
            <div style="margin-bottom: 12px; display: flex; gap: 12px;">
              <button onclick="exportPDF()" style="padding: 8px 16px;">📄 Export PDF</button>
              <button onclick="exportExcel()" style="padding: 8px 16px;">📊 Export Excel</button>
            </div>
            <div id="modalTableContainer"></div>
        </div>
        </div>
      </div>


      <!-- Reports Tab -->
      <div id="reportsTab" class="tab">
        <div class="reports-header">
          <h1 class="title"><i class="fas fa-chart-line"></i> Attendance Analytics for <?php echo htmlspecialchars($subject); ?></h1>
          <div class="report-controls">
            <form method="post" action="export_attendance.php">
              <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
              <button type="submit" class="export-btn"><i class="fa-solid fa-download"></i> Export CSV</button>
            </form>
          </div>
        </div>


        <div class="report-grid">
          <!-- Weekly Report Card -->
          <div class="report-card">
            <div class="card-header">
              <h2><i class="fas fa-calendar-week"></i> Weekly Attendance</h2>
              <span class="card-badge">This Year</span>
            </div>
            <div class="chart-container">
              <canvas id="weeklyPie"></canvas>
            </div>
            <div class="card-footer">
              <div class="stats">
                <span><i class="fas fa-user-check"></i> <?php echo array_sum($weeklyData); ?> total</span>
                <span><i class="fas fa-chart-line"></i> <?php echo count($weeklyData) > 0 ? round(array_sum($weeklyData)/count($weeklyData)) : 0; ?> avg/week</span>
              </div>
            </div>
          </div>


          <!-- Monthly Report Card -->
          <div class="report-card">
            <div class="card-header">
              <h2><i class="fas fa-calendar-alt"></i> Monthly Attendance</h2>
              <span class="card-badge">This Year</span>
            </div>
            <div class="chart-container">
              <canvas id="monthlyPie"></canvas>
            </div>
            <div class="card-footer">
              <div class="stats">
                <span><i class="fas fa-user-check"></i> <?php echo array_sum($monthlyData); ?> total</span>
                <span><i class="fas fa-chart-line"></i> <?php echo count($monthlyData) > 0 ? round(array_sum($monthlyData)/count($monthlyData)) : 0; ?> avg/month</span>
              </div>
            </div>
          </div>


          <!-- Yearly Report Card -->
          <div class="report-card">
            <div class="card-header">
              <h2><i class="fas fa-calendar"></i> Yearly Attendance</h2>
              <span class="card-badge">All Time</span>
            </div>
            <div class="chart-container">
              <canvas id="yearlyPie"></canvas>
            </div>
            <div class="card-footer">
              <div class="stats">
                <span><i class="fas fa-user-check"></i> <?php echo array_sum($yearlyData); ?> total</span>
                <span><i class="fas fa-chart-line"></i> <?php echo count($yearlyData) > 0 ? round(array_sum($yearlyData)/count($yearlyData)) : 0; ?> avg/year</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <script>
    function toggleSidebar() {
      document.getElementById("sidebar").classList.toggle("expanded");
    }

    function showTab(tabId) {
      const tabs = document.querySelectorAll(".tab");
      tabs.forEach(tab => tab.classList.remove("active"));
      document.getElementById(tabId).classList.add("active");
    }

    function filterUsers() {
      const input = document.getElementById('searchUser').value.toLowerCase();
      const table = document.getElementById('usersTable');
      const tr = table.getElementsByTagName('tr');


      for (let i = 1; i < tr.length; i++) {
        let match = false;
        const td = tr[i].getElementsByTagName('td');
        for (let j = 0; j < td.length; j++) {
          if (td[j]) {
            const text = td[j].textContent || td[j].innerText;
            if (text.toLowerCase().indexOf(input) > -1) {
              match = true;
              break;
            }
          }
        }
        tr[i].style.display = match ? "" : "none";
      }
    }

    function selectStudent(student) {
      document.getElementById("selectedStudentId").value = student.student_id;
      document.getElementById("selectedDbId").value = student.id;

      // Autofill form (optional)
      document.getElementById("student_id").value = student.student_id;
      document.getElementById("student_name").value = student.student_name;

      // Highlight selected row (optional visual feedback)
      const rows = document.querySelectorAll("#usersTable tbody tr");
      rows.forEach(row => row.classList.remove("selected"));
      event.currentTarget.classList.add("selected");
    }

    function handleDelete() {
      const dbId = document.getElementById("selectedDbId").value;
      if (!dbId) return alert("Please select a student first.");

      if (confirm("Are you sure you want to delete this student?")) {
        window.location.href = "delete_student.php?id=" + dbId;
      }
    }

    function handleViewProfile() {
      const studentId = document.getElementById("selectedStudentId").value;
      const studentName = document.getElementById("student_name").value;

      if (!studentId) return alert("Please select a student first.");

      document.getElementById("modalStudentId").innerText = studentId;
      document.getElementById("modalStudentName").innerText = studentName;
      document.getElementById("profileModal").style.display = "block";
    }

    function closeModal() {
      document.getElementById("profileModal").style.display = "none";
    }

    window.onclick = function(event) {
      const modal = document.getElementById("profileModal");
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }

    function handleDeactivate() {
      const dbId = document.getElementById("selectedDbId").value;
      if (!dbId) return alert("Please select a student first.");

      // Example deactivation logic
      alert("Deactivating student with ID: " + dbId);
      // You can redirect or send an AJAX request here
    }

    // QR Code and Student Registration
    async function fetchLastId() {
      const res = await fetch("../user/get_last_id.php");
      const data = await res.json();
      document.getElementById("student_id").value = data.next_id;
    }

    document.addEventListener("DOMContentLoaded", () => {
      fetchLastId();
      showTab('homeTab');
      initializeCharts();
    });

    document.querySelector("form").addEventListener("submit", function (e) {
      e.preventDefault();
      const id = document.getElementById('student_id').value;
      const name = document.getElementById('student_name').value;
      const qrCodeDiv = document.getElementById("qr-code");
      const downloadBtn = document.getElementById("downloadBtn");

      qrCodeDiv.innerHTML = "";
      downloadBtn.classList.add("hidden");

      const qrData = JSON.stringify({ student_id: id, student_name: name });
      new QRCode(qrCodeDiv, {
        text: qrData,
        width: 200,
        height: 200,
        correctLevel: QRCode.CorrectLevel.H
      });

      setTimeout(() => {
        const img = qrCodeDiv.querySelector('img');
        if (img) {
          downloadBtn.classList.remove("hidden");
          downloadBtn.onclick = function () {
            const link = document.createElement("a");
            link.href = img.src;
            link.download = `QR_${id}.png`;
            link.click();
          };
        }
      }, 500);

      // Submit the form after generating QR code
      this.submit();
    });

    function startScan() {
      const html5QrCode = new Html5Qrcode("reader");
      html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 150, height: 150 } },
        (decodedText) => {
          html5QrCode.stop();
          const data = JSON.parse(decodedText);
          fetch('../user/scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
          })
            .then(res => res.text())
            .then(msg => document.getElementById('scanResult').textContent = msg);
        },
        (error) => { }
      ).catch((err) => console.error(err));
    }

    // Charts
    function initializeCharts() {
      // Weekly Chart
      new Chart(document.getElementById('weeklyPie').getContext('2d'), {
        type: 'pie',
        data: {
          labels: <?php echo json_encode($weeklyLabels); ?>,
          datasets: [{
            data: <?php echo json_encode($weeklyData); ?>,
            backgroundColor: [
              '#3498db', '#2ecc71', '#e74c3c', '#f39c12',
              '#9b59b6', '#1abc9c', '#d35400', '#34495e'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: '#fff',
                font: { size: 12 }
              }
            },
            title: {
              display: true,
              text: 'Weekly Attendance for <?php echo htmlspecialchars($subject); ?>',
              color: '#fff',
              font: { size: 14 }
            }
          }
        }
      });


      // Monthly Chart
      new Chart(document.getElementById('monthlyPie').getContext('2d'), {
        type: 'pie',
        data: {
          labels: <?php echo json_encode($monthlyLabels); ?>,
          datasets: [{
            data: <?php echo json_encode($monthlyData); ?>,
            backgroundColor: [
              '#3498db', '#2ecc71', '#e74c3c', '#f39c12',
              '#9b59b6', '#1abc9c', '#d35400', '#34495e'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: '#fff',
                font: { size: 12 }
              }
            },
            title: {
              display: true,
              text: 'Monthly Attendance for <?php echo htmlspecialchars($subject); ?>',
              color: '#fff',
              font: { size: 14 }
            }
          }
        }
      });


      // Yearly Chart
      new Chart(document.getElementById('yearlyPie').getContext('2d'), {
        type: 'pie',
        data: {
          labels: <?php echo json_encode($yearlyLabels); ?>,
          datasets: [{
            data: <?php echo json_encode($yearlyData); ?>,
            backgroundColor: [
              '#3498db', '#2ecc71', '#e74c3c', '#f39c12',
              '#9b59b6', '#1abc9c', '#d35400', '#34495e'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: '#fff',
                font: { size: 12 }
              }
            },
            title: {
              display: true,
              text: 'Yearly Attendance for <?php echo htmlspecialchars($subject); ?>',
              color: '#fff',
              font: { size: 14 }
            }
          }
        }
      });
    }
  
    function loadAttendance(filter) {
      document.querySelector("iframe").src = "../admin/view_attendance.php?filter=" + filter;
    }

    function openModal(filterType) {
      document.getElementById('attendanceModal').style.display = 'flex';
      fetch('../admin/view_attendance.php?filter=' + filterType)
        .then(res => res.text())
        .then(data => {
          document.getElementById('modalTableContainer').innerHTML = data;
        });
    }

    function closeModal() {
      document.getElementById('attendanceModal').style.display = 'none';
    }

    // Simple search function
    function filterTable() {
      const input = document.getElementById('searchInput').value.toLowerCase();
      const rows = document.querySelectorAll("#modalTableContainer table tbody tr");
      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? "" : "none";
      });
    }

    function exportPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF("p", "mm", "a4");
      
      const pageWidth = doc.internal.pageSize.getWidth();
      const pageHeight = doc.internal.pageSize.getHeight();
      
      // Add header with styling
      doc.setFillColor(69, 4, 106);
      doc.rect(0, 0, pageWidth, 40, "F");
      
      // Add title and info
      doc.setTextColor(255, 255, 255);
      doc.setFontSize(24);
      doc.text("SAN ISIDRO NATIONAL HIGH SCHOOL", pageWidth/2, 20, { align: "center" });
      
      doc.setFontSize(12);
      doc.text("Instructor: " + document.getElementById('instructor_name').textContent, pageWidth/2, 30, { align: "center" });
      doc.text("Subject: " + document.getElementById('subject_name').textContent, pageWidth/2, 37, { align: "center" });
      
      doc.setFontSize(10);
      doc.text("Generated on: " + new Date().toLocaleString(), 15, 50);
      
      doc.setTextColor(0, 0, 0);
      
      // Get only the rows for this subject's students
      const table = document.getElementById('modalTableContainer').querySelector('table');
      const rows = Array.from(table.querySelectorAll('tbody tr'));
      const subjectName = document.getElementById('subject_name').textContent;
      
      doc.autoTable({
        html: '#modalTableContainer table',
        startY: 60,
        styles: { fontSize: 10, cellPadding: 3 },
        headStyles: { 
          fillColor: [69, 4, 106],
          textColor: [255, 255, 255],
          fontSize: 12,
          fontStyle: 'bold',
          halign: 'center'
        },
        alternateRowStyles: { 
          fillColor: [245, 245, 245]
        },
        margin: { top: 60 }
      });
      
      const instructor = document.getElementById('instructor_name').textContent;
      doc.save(`SINHS_Attendance_${instructor}_${subjectName}_${new Date().toISOString().split('T')[0]}.pdf`);
    }

    function exportExcel() {
      const table = document.getElementById("attendanceTable");
      const instructor = document.getElementById('instructor_name').textContent;
      const subject = document.getElementById('subject_name').textContent;
      
      // Create a new workbook
      const wb = XLSX.utils.book_new();
      
      // Add header information
      const headerData = [
        ["SAN ISIDRO NATIONAL HIGH SCHOOL"],
        ["Instructor: " + instructor],
        ["Subject: " + subject],
        ["Generated on: " + new Date().toLocaleString()],
        [] // Empty row for spacing
      ];
      
      // Convert table to worksheet
      const ws = XLSX.utils.table_to_sheet(table);
      
      // Prepend header information
      XLSX.utils.sheet_add_aoa(ws, headerData, { origin: "A1" });
      
      // Add worksheet to workbook
      XLSX.utils.book_append_sheet(wb, ws, "Attendance");
      
      // Save the Excel file with instructor name and subject
      XLSX.writeFile(wb, `SINHS_Attendance_${instructor}_${subject}_${new Date().toISOString().split('T')[0]}.xlsx`);
    }

    function downloadAttendancePDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF("p", "mm", "a4");
      
      // Set page dimensions
      const pageWidth = doc.internal.pageSize.getWidth();
      const pageHeight = doc.internal.pageSize.getHeight();
      
      // Add header with styling
      doc.setFillColor(69, 4, 106);
      doc.rect(0, 0, pageWidth, 40, "F");
      
      // Add title
      doc.setTextColor(255, 255, 255);
      doc.setFontSize(24);
      doc.text("SAN ISIDRO NATIONAL HIGH SCHOOL", pageWidth/2, 20, { align: "center" });
      
      // Add instructor and subject info
      doc.setFontSize(12);
      doc.text("Instructor: <?php echo htmlspecialchars($instructor_name); ?>", pageWidth/2, 30, { align: "center" });
      doc.text("Subject: <?php echo htmlspecialchars($subject); ?>", pageWidth/2, 37, { align: "center" });
      
      // Add date
      doc.setFontSize(10);
      doc.text("Generated on: " + new Date().toLocaleString(), 15, 50);
      
      // Reset text color for table
      doc.setTextColor(0, 0, 0);
      
      // Add the table
      doc.autoTable({
        html: '#attendanceTable',
        startY: 60,
        styles: { 
          fontSize: 10,
          cellPadding: 3
        },
        headStyles: { 
          fillColor: [69, 4, 106],
          textColor: [255, 255, 255],
          fontSize: 12,
          fontStyle: 'bold',
          halign: 'center'
        },
        alternateRowStyles: { 
          fillColor: [245, 245, 245]
        },
        margin: { top: 60 },
        didDrawPage: function(data) {
          if (data.pageNumber > 1) {
            // Add header to each page
            doc.setFillColor(69, 4, 106);
            doc.rect(0, 0, pageWidth, 30, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("SAN ISIDRO NATIONAL HIGH SCHOOL", pageWidth/2, 15, { align: "center" });
            doc.setFontSize(10);
            doc.text("Instructor: <?php echo htmlspecialchars($instructor_name); ?>", pageWidth/2, 22, { align: "center" });
            doc.text("Subject: <?php echo htmlspecialchars($subject); ?>", pageWidth/2, 27, { align: "center" });
          }
          
          // Add footer
          doc.setTextColor(0, 0, 0);
          doc.setFontSize(10);
          doc.text('Page ' + data.pageNumber, pageWidth/2, pageHeight - 10, { align: 'center' });
        }
      });
      
      // Save the PDF with instructor name and subject
      doc.save(`SINHS_Attendance_${<?php echo json_encode($instructor_name); ?>}_${<?php echo json_encode($subject); ?>}_${new Date().toISOString().split('T')[0]}.pdf`);
    }

  </script>
</body>
</html>