<?php
include('../database/db.php');

// Get subjects
$subjects = $conn->query("SELECT * FROM subjects");
$subject_list = [];
while($row = $subjects->fetch_assoc()) {
    $subject_list[] = $row;
}
?>

<div class="container">
    <div class="table-container">
        <h2>Manage Subjects</h2>
        <div class="add-subject-form">
            <input type="text" id="subjectName" placeholder="Enter subject name">
            <button onclick="addSubject()" class="btn-add">Add Subject</button>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Assigned Instructor</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($subject_list as $subject): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($subject['subject']); ?></td>
                        <td>
                            <?php
                            $stmt = $conn->prepare("SELECT instructor_name FROM instructors WHERE subject = ? AND status = 'approved'");
                            $stmt->bind_param("s", $subject['subject']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if($row = $result->fetch_assoc()) {
                                echo htmlspecialchars($row['instructor_name']);
                            } else {
                                echo "Not assigned";
                            }
                            ?>
                        </td>
                        <td>
                            <button onclick="deleteSubject('<?php echo htmlspecialchars($subject['subject']); ?>')" class="btn-reject">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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

.add-subject-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.add-subject-form input[type="text"] {
    flex: 1;
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #45A29E;
    background-color: rgba(255, 255, 255, 0.05);
    color: #C5C6C7;
}

.add-subject-form input[type="text"]:focus {
    outline: none;
    border-color: #66FCF1;
}

.btn-add, .btn-reject {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s ease;
}

.btn-add {
    background-color: #4CAF50;
    color: white;
}

.btn-add:hover {
    background-color: #45a049;
}

.btn-reject {
    background-color: #f44336;
    color: white;
}

.btn-reject:hover {
    background-color: #da190b;
}

h2 {
    color: #66FCF1;
    margin-bottom: 20px;
}
</style>

<script>
function addSubject() {
    const subjectName = document.getElementById('subjectName').value.trim();
    if (!subjectName) {
        alert('Please enter a subject name');
        return;
    }

    const formData = new FormData();
    formData.append('subject', subjectName);

    fetch('add_subject.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the subject');
    });
}

function deleteSubject(subject) {
    if (confirm("Are you sure you want to delete this subject? This will remove it from all assigned instructors.")) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('subject', subject);

        fetch('manage_subjects.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the subject');
        });
    }
}
</script> 