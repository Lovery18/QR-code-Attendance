<?php
session_start();
include('../database/db.php');

if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM instructors WHERE email = ? AND password = ? AND status = 'approved'");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $instructor = $result->fetch_assoc();
        $_SESSION['instructor_id'] = $instructor['id'];
        $_SESSION['instructor_name'] = $instructor['instructor_name'];
        header("Location: instructor_dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials or account not yet approved";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #0B0C10;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('../homepage/logo_bg.jpg') no-repeat center center/cover;
        }

        .login-container {
            background-color: rgba(31, 40, 51, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(102, 252, 241, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container h2 {
            color: #66FCF1;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #C5C6C7;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #45A29E;
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.05);
            color: #C5C6C7;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #66FCF1;
        }

        .login-btn {
            background-color: #45A29E;
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .login-btn:hover {
            background-color: #66FCF1;
        }

        .error-message {
            color: #ff6b6b;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .register-link {
            margin-top: 1rem;
            color: #C5C6C7;
        }

        .register-link a {
            color: #66FCF1;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #66FCF1;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 1rem;
        }

        .back-btn:hover {
            color: #45A29E;
        }
    </style>
</head>
<body>
    <a href="../homepage/homepage.html" class="back-btn">← Back to Home</a>
    
    <div class="login-container">
        <h2>Instructor Login</h2>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" name="login" class="login-btn">Login</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="instructor_reg.php">Register here</a>
        </div>
    </div>
</body>
</html>
