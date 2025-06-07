<?php
include('../database/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM instructors WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if($check->get_result()->num_rows > 0) {
            $error = "Email already registered";
        } else {
            // Store both name and email
            $stmt = $conn->prepare("INSERT INTO instructors (instructor_name, email, password, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("sss", $name, $email, $password);
            if($stmt->execute()) {
                header("Location: instructor_login.php?registered=1");
                exit();
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Registration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #0B0C10;
            background: url('../homepage/logo_bg.jpg') no-repeat center center/cover;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .register-container {
            max-width: 400px;
            text-align: center;
            padding: 2rem;
            background-color: rgba(11, 12, 16, 0.95);
            border-radius: 15px;
            box-shadow: 0 0 20px #1F2833;
            width: 100%;
        }

        h2 {
            font-size: 2rem;
            color: #86c6c2;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        input {
            width: 100%;
            padding: 0.8rem;
            margin-top: 0.5rem;
            border: 2px solid #66FCF1;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
            color: #C5C6C7;
            transition: 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #86c6c2;
            box-shadow: 0 0 10px rgba(134, 198, 194, 0.3);
        }

        button {
            width: 100%;
            padding: 0.8rem 2rem;
            background: transparent;
            color: #89b6b3;
            border: 2px solid #66FCF1;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
            transition: 0.3s ease;
        }

        button:hover {
            background-color: #3e5453;
            color: #0B0C10;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #C5C6C7;
        }

        .login-link a, .back-btn {
            color: #89b6b3;
            text-decoration: none;
            transition: 0.3s ease;
        }

        .login-link a:hover, .back-btn:hover {
            color: #66FCF1;
        }

        .error {
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
            background-color: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            color: #ff0000;
        }

        .back-btn {
            display: inline-block;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Instructor Registration</h2>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <input type="text" name="name" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="instructor_login.php">Login</a>
        </div>

        <div style="text-align: center;">
            <a href="../homepage/homepage.html" class="back-btn">← Back to Homepage</a>
        </div>
    </div>

    <script>
    function validateForm() {
        var password = document.getElementById("password").value;
        var confirm_password = document.getElementById("confirm_password").value;
        
        if(password !== confirm_password) {
            alert("Passwords do not match!");
            return false;
        }
        return true;
    }
    </script>
</body>
</html>