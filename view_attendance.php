<?php 
include '../database/db.php';

$res = $conn->query("SELECT * FROM attendance ORDER BY scan_time DESC");

echo "<style>
  .table-wrapper {
    width: 100%;
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background: rgba(0, 0, 40, 0.7);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 0 20px #1F2833;
    color: #66FCF1;
  }

  th, td {
    font-family: Verdana;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: #66FCF1;
    text-align: center;
    white-space: nowrap;
  }

  th {
    background: rgba(11, 12, 16, 0.95);
    color: white;
    font-size: 16px;
    text-shadow: 0 0 5px #66FCF1;
  }

  td[contenteditable='true'] {
    background-color: rgba(255, 255, 255, 0.05);
    cursor: text;
    border-radius: 5px;
    color: #fff;
  }

  tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
  }

</style>";

echo "<div class='table-wrapper'><table>
        <tr><th>Student ID</th><th>Name</th><th>Date & Time</th>";

while ($row = $res->fetch_assoc()) {
  echo "<tr id='row-{$row['student_id']}'>
          <td>{$row['student_id']}</td>
          <td contenteditable='true' onblur=\"updateName('{$row['student_id']}', this.innerText)\">{$row['student_name']}</td>
          <td>{$row['scan_time']}</td>
        </tr>";
}

$conn->close();
?>