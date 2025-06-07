<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>QR Attendance System</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: rgb(58, 1, 58);
      color: #fff;
    }

    header {
      background: #20030f;
      padding: 30px 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }

    header h1 {
      margin: 0;
      font-size: 22px;
    }

    .nav-icons i {
      margin-left: 20px;
      cursor: pointer;
      font-size: 20px;
      transition: color 0.3s ease;
    }

    .nav-icons i:hover {
      color: #00ffcc;
    }

    .container {
      max-width: 900px;
      margin: 40px auto;
      background: rgba(207, 203, 203, 0.05);
      backdrop-filter: blur(10px);
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 0 20px rgba(19, 0, 15, 0.15);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 24px;
      color: #ffffff;
      text-shadow: 0 0 5px #ff00cc;
    }

    input[type="text"], button {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border-radius: 10px;
      border: none;
      font-size: 16px;
      outline: none;
    }

    input[type="text"] {
      background-color: #20030f;
      color: #fff;
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

    #qr-code {
      margin: 20px 0;
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      text-align: center;
      justify-content: center;
    }

    #scanResult {
      font-weight: bold;
      color: #00ffcc;
      margin-top: 10px;
      text-align: center;
    }

    iframe, #reader {
      width: 100%;
      border: none;
      margin: 20px 0;
      background-color: rgb(41, 41, 104);
      border-radius: 12px;
      text-align: center;
      justify-content: center;
    }

    .hidden {
      display: none;
    }

    @media (max-width: 600px) {
      .container {
        margin: 20px;
        padding: 20px;
      }

      header {
        flex-direction: column;
        align-items: flex-start;
      }

      .nav-icons {
        margin-top: 10px;
      }
    }
  </style>
  
</head>
<body>

  <header>
    <h1><i class="fa-solid fa-qrcode"></i> QR Attendance System</h1>
    <div class="nav-icons">
      <i class="fa-solid fa-house" onclick="showTab('homeTab')" title="Home"></i>
      <i class="fa-solid fa-user-plus" onclick="showTab('addUserTab')" title="Add User"></i>
      <i class="fa-solid fa-file-lines" onclick="showTab('listUserTab')" title="List of Users"></i>
      <i class="fa-solid fa-right-from-bracket" onclick="logout()" title="Logout"></i>

    </div>
  </header>

  <div id="homeTab" class="container">
    <h2><i class="fa-solid fa-house"></i> Home</h2>

      <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-bottom: 30px;">
      <div style="flex: 1; min-width: 180px; background-color:rgb(41, 41, 104); padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 0 10px #0005;">
        <h3>Total Students</h3>
        <?php
          include '../database/db.php';
          $result = $conn->query("SELECT COUNT(*) AS total FROM students");
          $row = $result->fetch_assoc();
          echo '<p style="font-size: 28px; font-weight: bold;">' . $row['total'] . '</p>';
          $conn->close();
        ?>
      </div>

      <div style="flex: 1; min-width: 180px; background-color:rgb(41, 41, 104); padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 0 10px #0005;">
        <h3>Today's Attendance</h3>
        <p style="font-size: 28px; font-weight: bold;">
          <?php include '../user/get_today_attendance.php'; ?>
        </p>
      </div>
      
    </div>
    
    <div style="flex: 1; min-width: 180px; background-color:rgb(41, 41, 104); padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 0 10px #0005;">
        <h3>Student Attendance</h3>
        <iframe src="../user/view_attendance.php" height="300"></iframe>
    </div>
  </div>  

  <div id="addUserTab" class="container hidden">
    <h2><i class="fa-solid fa-user-pen"></i> Register Student</h2>
    <form action="register.php" method="POST">
      <input type="text" name="student_id" id="student_id" readonly placeholder="Student ID">
      <input type="text" name="student_name" id="student_name" placeholder="Student Name" required>
      <button type="submit"><i class="fa-solid fa-plus-circle"></i> Register</button>
    </form>
    <div id="qr-code"></div>
    <button id="downloadBtn" class="hidden"><i class="fa-solid fa-download"></i> Download QR Code</button>

    <h2><i class="fa-solid fa-barcode"></i> Scan QR Code</h2>
    <div id="reader" style="width: 100%; max-width: 300px; margin: auto;"></div>
    <button onclick="startScan()"><i class="fa-solid fa-camera"></i> Start Scan</button>
    <p id="scanResult"></p>
  </div>

  <div id="listUserTab" class="container hidden">
    <h2><i class="fa-solid fa-list-check"></i> Student Records</h2>
    <iframe src="../user/view_page.php" height="300"></iframe>
  </div>

  <script>
    const tabs = ['homeTab', 'addUserTab', 'listUserTab'];

    function showTab(tabId) {
      tabs.forEach(id => {
        document.getElementById(id).classList.add('hidden');
      });
      document.getElementById(tabId).classList.remove('hidden');
    }

    async function fetchLastId() {
      const res = await fetch("../user/get_last_id.php");
      const data = await res.json();
      document.getElementById("student_id").value = data.next_id;
    }

    document.addEventListener("DOMContentLoaded", () => {
      fetchLastId();
      showTab('homeTab'); 
    });

    document.querySelector("form").addEventListener("submit", function (e) {
      const id = document.getElementById('student_id').value;
      const name = document.getElementById('student_name').value;
      const qrCodeDiv = document.getElementById("qr-code");
      const downloadBtn = document.getElementById("downloadBtn");

      qrCodeDiv.innerHTML = "";
      downloadBtn.classList.add("hidden");

      const qrData = JSON.stringify({ student_id: id, student_name: name });
      const qrCode = new QRCode(qrCodeDiv, {
        text: qrData,
        width: 200,
        height: 200,
        correctLevel: QRCode.CorrectLevel.H
      });

      setTimeout(() => {
        const img = qrCodeDiv.querySelector('img') || qrCodeDiv.querySelector('canvas');
        if (img) {
          downloadBtn.classList.remove("hidden");
          downloadBtn.onclick = function () {
            const link = document.createElement("a");
            link.href = img.src || img.toDataURL("image/png");
            link.download = `QR_${id}.png`;
            link.click();
          };
        }
      }, 500);
    });

    function startScan() {
      const html5QrCode = new Html5Qrcode("reader");
      html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 200, height: 250 } },
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
function logout() {
  window.location.href = "/QR-Code-Attendance-System/homepage/homepage.html";
}

  </script>

</body>
</html>