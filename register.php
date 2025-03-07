<?php
session_start();
include 'db_connect.php';

$error_message = '';
$success_message = '';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize($conn, $_POST['username']);
    $email = sanitize($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (strlen($username) < 3) {
        $error_message = "Username must be at least 3 characters";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Username already taken";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Email already registered";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $success_message = "Registration successful! You can now login.";
                } else {
                    $error_message = "Error: " . $stmt->error;
                }
            }
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Community Notes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            padding: 10px 15px;
            background-color: #FF85A2;
            color: white;
            border: none;
            cursor: pointer;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 20px;
            border: 2px solid rgba(255, 169, 198, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: "";
            position: absolute;
            top: -10px;
            right: -10px;
            width: 100px;
            height: 100px;
            background-color: rgba(255, 169, 198, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
        
        .card::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: -10px;
            width: 70px;
            height: 70px;
            background-color: rgba(181, 222, 255, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
    </style>
</head>
<body>
    <h1>Register</h1>
    <div class="card">
    <?php if (!empty($error_message)): ?>
        <div class="error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <small>Password must be at least 6 characters</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        
        <div class="form-group">
            <input type="submit" value="Register">
        </div>
    </form>
    
    <p>Already have an account? <a href="login.php">Login here</a></p>
    <p><a href="index.php">Back to Home</a></p>
</body>
</html>