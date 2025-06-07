<?php
include('../database/db.php');

// Handle instructor approval
if(isset($_POST['approve_instructor'])) {
    $instructor_id = $_POST['instructor_id'];
    $subject = $_POST['subject'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $day = $_POST['day'];
    
    $stmt = $conn->prepare("UPDATE instructors SET status='approved', subject=?, time=CONCAT(?, ' - ', ?), day=? WHERE id=?");
    $stmt->bind_param("ssssi", $subject, $start_time, $end_time, $day, $instructor_id);
    if($stmt->execute()) {
        echo "<script>alert('Instructor approved successfully!');</script>";
    } else {
        echo "<script>alert('Error approving instructor: " . $conn->error . "');</script>";
    }
}

// Handle instructor rejection
if(isset($_POST['reject_instructor'])) {
    $instructor_id = $_POST['instructor_id'];
    $stmt = $conn->prepare("DELETE FROM instructors WHERE id=?");
    $stmt->bind_param("i", $instructor_id);
    if($stmt->execute()) {
        echo "<script>alert('Instructor rejected successfully!');</script>";
    }
}

// Get pending instructors
$pending_instructors = $conn->query("SELECT * FROM instructors WHERE status='pending'");

// Get approved instructors
$approved_instructors = $conn->query("SELECT * FROM instructors WHERE status='approved'");

// Get subjects for dropdown
$subjects = $conn->query("SELECT * FROM subjects");
$subject_list = [];
while($row = $subjects->fetch_assoc()) {
    $subject_list[] = $row;
}
?>
<div class="container">
    <div class="table-container">
        <h2>Pending Instructor Approvals</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Time</th>
                        <th>Day</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($instructor = $pending_instructors->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($instructor['instructor_name']); ?></td>
                        <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                        <td colspan="3">
                            <form method="POST" class="approval-form" style="display: flex; gap: 10px;">
                                <input type="hidden" name="instructor_id" value="<?php echo $instructor['id']; ?>">
                                <select name="subject" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach($subject_list as $subject): ?>
                                        <option value="<?php echo htmlspecialchars($subject['subject']); ?>">
                                            <?php echo htmlspecialchars($subject['subject']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="time-inputs">
                                    <input type="time" name="start_time" required placeholder="Start Time">
                                    <span class="time-separator">to</span>
                                    <input type="time" name="end_time" required placeholder="End Time">
                                </div>
                                <select name="day" required>
                                    <option value="">Select Day</option>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                </select>
                            </td>
                            <td>
                                <button type="submit" name="approve_instructor" class="btn-approve">Approve</button>
                                <button type="submit" name="reject_instructor" class="btn-reject">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-container">
        <h2>Approved Instructors</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Instructor Name</th>
                        <th>Subject</th>
                        <th>Time</th>
                        <th>Day</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($instructor = $approved_instructors->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($instructor['instructor_name']); ?></td>
                        <td><?php echo htmlspecialchars($instructor['subject']); ?></td>
                        <td><?php echo htmlspecialchars($instructor['time']); ?></td>
                        <td><?php echo htmlspecialchars($instructor['day']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.table-container {
    margin-bottom: 30px;
    padding: 20px;
    background-color: rgba(31, 40, 51, 0.95);
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(102, 252, 241, 0.1);
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #45A29E;
    color: #C5C6C7;
}

th {
    background-color: #1F2833;
    color: #66FCF1;
}

tr:hover {
    background-color: rgba(69, 162, 158, 0.1);
}

h2 {
    color: #66FCF1;
    margin-bottom: 20px;
}

.approval-form {
    margin: 0;
    padding: 0;
}

.btn-approve, .btn-reject {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin: 0 5px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.btn-approve {
    background-color: #4CAF50;
    color: white;
}

.btn-approve:hover {
    background-color: #45a049;
}

.btn-reject {
    background-color: #f44336;
    color: white;
}

.btn-reject:hover {
    background-color: #da190b;
}

.time-inputs {
    display: flex;
    align-items: center;
    gap: 5px;
}

.time-separator {
    color: #66FCF1;
    margin: 0 5px;
}

select, input[type="time"] {
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #45A29E;
    background-color: rgba(255, 255, 255, 0.05);
    color: #C5C6C7;
    min-width: 120px;
}

input[type="time"] {
    min-width: 100px;
}

select:focus, input[type="time"]:focus {
    outline: none;
    border-color: #66FCF1;
}
</style>
