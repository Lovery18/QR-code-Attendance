<?php 
include '../database/db.php';

$res = $conn->query("SELECT * FROM students ORDER BY scan_time DESC");

echo "<style>
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
    background: #5e005e;
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

  button {
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    background: #0f0c29;
    color: white;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  button:hover {
    transform: scale(1.05);
    box-shadow: 0 0 10px #ff00cc;
  }
</style>";

echo "<div class='table-wrapper'><table>
        <tr><th>Student ID</th><th>Name</th><th>Date & Time</th><th>Update</th><th>Delete</th></tr>";

while ($row = $res->fetch_assoc()) {
  echo "<tr id='row-{$row['student_id']}'>
          <td>{$row['student_id']}</td>
          <td contenteditable='true' onblur=\"updateName('{$row['student_id']}', this.innerText)\">{$row['student_name']}</td>
          <td>{$row['scan_time']}</td>
          <td><button onclick=\"alert('Edit the name directly and click outside the cell to update.')\">Update</button></td>
          <td><button onclick=\"deleteRecord('{$row['student_id']}')\">Delete</button></td>
        </tr>";
}

echo "</table></div>
      <script>
      function updateName(id, name) {
        fetch('update_name.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ student_id: id, student_name: name })
        })
        .then(res => res.text())
        .then(msg => alert(msg));
      }

      function deleteRecord(id) {
        if (confirm('Are you sure you want to delete this record?')) {
          fetch('delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_id: id })
          })
          .then(res => res.text())
          .then(msg => {
            alert(msg);
            document.getElementById('row-' + id).remove();
          });
        }
      }
      </script>";

$conn->close();
?>