<?php
session_start();
include 'db_connect.php';

$error_message = '';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

// Check if anonymous session exists, if not create one
if (!isset($_SESSION['anonymous_id'])) {
    // Generate a unique anonymous ID
    $_SESSION['anonymous_id'] = uniqid('anon_', true);
    $_SESSION['is_anonymous'] = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username']; 
            
            // Clear anonymous status if logging in
            unset($_SESSION['is_anonymous']);
            unset($_SESSION['anonymous_id']);
            
            // Redirect to index page
            header("location: index.php");
            exit;
        } else {
            $error_message = "Invalid username or password";
        }
    } else {
        $error_message = "Invalid username or password";
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Community Notes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&family=Pacifico&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF85A2;
            --secondary: #FF619A;
            --accent: #FFA9C6;
            --success: #B5DEFF;
            --danger: #FF5D8F;
            --light: #FFF0F5;
            --dark: #5E4C5A;
            --gray: #B493A6;
            --border-radius: 20px;
            --box-shadow: 0 8px 20px rgba(255, 133, 162, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #FFF0F5;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 169, 198, 0.2) 10%, transparent 20%),
                radial-gradient(circle at 90% 30%, rgba(181, 222, 255, 0.2) 15%, transparent 25%),
                radial-gradient(circle at 30% 70%, rgba(255, 169, 198, 0.2) 20%, transparent 30%),
                radial-gradient(circle at 80% 80%, rgba(181, 222, 255, 0.2) 10%, transparent 20%);
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 420px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: var(--primary);
            font-family: 'Pacifico', cursive;
            font-size: 2.2rem;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(255, 133, 162, 0.3);
        }
        
        .logo p {
            color: var(--gray);
            font-size: 1rem;
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
        
        .form-title {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .form-title::after {
            content: "♡";
            display: block;
            text-align: center;
            font-size: 1.2rem;
            color: var(--accent);
            margin-top: 5px;
        }
        
        .error {
            color: white;
            background-color: var(--danger);
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #FFE0EB;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #FFFAFC;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 133, 162, 0.2);
            outline: none;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: var(--primary);
        }
        
        .input-with-icon input {
            padding-left: 40px;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.5s;
            z-index: -1;
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(255, 97, 154, 0.3);
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: rgba(255, 133, 162, 0.1);
        }
        
        .link {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 15px;
        }
        
        .mb-3 {
            margin-bottom: 15px;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--accent), transparent);
        }
        
        .divider::before {
            margin-right: 10px;
        }
        
        .divider::after {
            margin-left: 10px;
        }
        
        .anonymous-login {
            display: flex;
            justify-content: center;
            margin-top: 15px;
            position: relative;
            z-index: 1;
        }
        
        .anonymous-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #FFF0F5;
            color: var(--dark);
            border: 2px solid var(--accent);
            border-radius: var(--border-radius);
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .anonymous-btn:hover {
            background-color: #FFE0EB;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(255, 169, 198, 0.2);
        }
        
        .bottom-links {
            text-align: center;
            margin-top: 20px;
            color: var(--gray);
            position: relative;
        }
        
        .bottom-links::before {
            content: "♡";
            display: block;
            text-align: center;
            font-size: 1.2rem;
            color: var(--accent);
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Welcome Chinggu-yaa!</h1>
            <p>Connect and Share with Everyone ✿</p>
        </div>
        
        <div class="card">
            <h2 class="form-title">Login to Your Account</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="divider">or</div>
            
            <div class="anonymous-login">
                <a href="index.php" class="anonymous-btn">
                    <i class=""></i> Continue as Anonymous👨🏻‍💻
                </a>
            </div>
            
            <p class="text-center mt-3">
                Don't have an account? <a href="register.php" class="link">Register now</a>
            </p>
        </div>
        
        <div class="bottom-links">
            <a href="index.php" class="link">Back to Home</a>
        </div>
    </div>
</body>
</html>