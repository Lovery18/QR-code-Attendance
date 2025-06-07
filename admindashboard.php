<?php
session_start();
include('../database/db.php');

// Database connection
$conn = new mysqli("localhost", "root", "", "attendance_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/// Total Students
$userResult = $conn->query("SELECT COUNT(*) AS total_student FROM students");
$userData = $userResult->fetch_assoc();
$totalStudents = $userData['total_student'];

// Attendance Today
$todayResult = $conn->query("SELECT COUNT(DISTINCT student_id) AS attendance_today FROM attendance WHERE DATE(scan_time) = CURDATE()");
$todayData = $todayResult->fetch_assoc();
$attendanceToday = $todayData['attendance_today'];

// Absent Today
$absentToday = $totalStudents - $attendanceToday;

// Weekly Attendance (by Week Number)
$weeklyLabels = [];
$weeklyData = [];

$weeklyQuery = "SELECT WEEK(scan_time) AS week, COUNT(*) AS count
                FROM attendance
                WHERE YEAR(scan_time) = YEAR(CURDATE())
                GROUP BY week
                ORDER BY week";
$weeklyResult = $conn->query($weeklyQuery);
while ($row = $weeklyResult->fetch_assoc()) {
    $weeklyLabels[] = "Week " . $row['week'];
    $weeklyData[] = $row['count'];
}

// Monthly Attendance
$monthlyLabels = [];
$monthlyData = [];

$monthlyQuery = "SELECT DATE_FORMAT(scan_time, '%b') AS month, COUNT(*) AS count
                 FROM attendance
                 WHERE YEAR(scan_time) = YEAR(CURDATE())
                 GROUP BY MONTH(scan_time)";
$monthlyResult = $conn->query($monthlyQuery);
while ($row = $monthlyResult->fetch_assoc()) {
    $monthlyLabels[] = $row['month'];
    $monthlyData[] = $row['count'];
}

// Yearly Attendance
$yearlyLabels = [];
$yearlyData = [];

$yearlyQuery = "SELECT YEAR(scan_time) AS year, COUNT(*) AS count
                FROM attendance
                GROUP BY year";
$yearlyResult = $conn->query($yearlyQuery);
while ($row = $yearlyResult->fetch_assoc()) {
    $yearlyLabels[] = $row['year'];
    $yearlyData[] = $row['count'];
}

// Get total instructors
$instructorResult = $conn->query("SELECT COUNT(*) AS total_instructors FROM instructors");
$instructorData = $instructorResult->fetch_assoc();
$totalInstructors = $instructorData['total_instructors'];

// Get pending instructors
$pendingResult = $conn->query("SELECT COUNT(*) AS pending_instructors FROM instructors WHERE status = 'pending'");
$pendingData = $pendingResult->fetch_assoc();
$pendingInstructors = $pendingData['pending_instructors'];

// Get active instructors
$activeInstructors = $totalInstructors - $pendingInstructors;

$approved_instructors = $conn->query("SELECT * FROM instructors WHERE status='approved' AND subject IS NOT NULL AND time IS NOT NULL AND day IS NOT NULL");

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
        body {
            display: flex;
            margin: 0;
            height: 100vh;
            background-color: #0B0C10;
            color: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .sidebar {
            width: 220px;
            background-color: #1F2833;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar .logo {
            color: #66FCF1;
            font-size: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar nav {
            width: 100%;
        }

        .sidebar .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #C5C6C7;
            text-decoration: none;
            transition: 0.3s;
        }

        .sidebar .nav-item:hover,
        .sidebar .nav-item.active {
            background-color: #45A29E;
            color: #fff;
        }

        .sidebar .nav-item i {
            margin-right: 10px;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            background: #0B0C10;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .stats-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 20px;
        }

        .stat-card {
            flex: 1;
            background: rgba(31, 40, 51, 0.7);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            border: 1px solid #45A29E;
            box-shadow: 0 0 20px rgba(102, 252, 241, 0.1);
            transition: all 0.3s ease;
            min-width: 250px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 30px rgba(102, 252, 241, 0.2);
        }

        .stat-card h3 {
            color: #66FCF1;
            font-size: 1.5em;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .stat-card .stat-value {
            font-size: 4em;
            font-weight: bold;
            color: #fff;
            margin: 0;
            text-shadow: 0 0 15px rgba(102, 252, 241, 0.3);
        }

        .header {
            margin-bottom: 30px;
            padding: 20px;
        }

        .header h1 {
            color: #66FCF1;
            font-size: 2.5em;
            margin: 0;
            font-weight: 500;
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
            margin-top: 30px;
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

        .chart-container {
            position: relative;
            height: 380px;
            width: 100%;
        }

        .card-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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

        .export-btn {
            padding: 8px 15px;
            border-radius: 6px;
            background: rgb(69, 4, 106);
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        .export-btn:hover {
            background: rgb(2, 35, 57);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-user-shield"></i>
            <span>Admin Panel</span>
        </div>
        <nav>
            <a href="#" class="nav-item active" data-tab="dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item" data-tab="instructors">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Manage Instructors</span>
            </a>
            <a href="#" class="nav-item" data-tab="subjects">
                <i class="fas fa-book"></i>
                <span>Manage Subjects</span>
            </a>
            <a href="../homepage/homepage.html" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Admin Dashboard</h1>
        </div>

        <div id="dashboard" class="tab-content active">
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Instructors</h3>
                    <p class="stat-value"><?php echo $totalInstructors; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active Instructors</h3>
                    <p class="stat-value"><?php echo $activeInstructors; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Approvals</h3>
                    <p class="stat-value"><?php echo $pendingInstructors; ?></p>
                </div>
            </div>

            <!-- Analytics Section -->
            <div class="reports-header">
                <h1 class="title"><i class="fas fa-chart-line"></i> Attendance Analytics</h1>
                <div class="report-controls">
                    <form method="post" action="export_attendance.php">
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

        <div id="instructors" class="tab-content">
            <?php include 'instructor_management.php'; ?>
        </div>

        <div id="subjects" class="tab-content">
            <?php include 'subject_management.php'; ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if(!this.getAttribute('href') || !this.getAttribute('href').includes('homepage')) {
                    e.preventDefault();
                    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    const tabId = this.getAttribute('data-tab');
                    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
                    document.getElementById(tabId).classList.add('active');
                }
            });
        });

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
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
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>