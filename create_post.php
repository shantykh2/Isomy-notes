<?php
session_start();
include 'db_connect.php';

// Check if anonymous session exists, if not create one
if (!isset($_SESSION['user_id']) && !isset($_SESSION['anonymous_id'])) {
    // Generate a unique anonymous ID
    $_SESSION['anonymous_id'] = uniqid('anon_', true);
    $_SESSION['is_anonymous'] = true;
}

// User identification (regular or anonymous)
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$anonymous_id = isset($_SESSION['anonymous_id']) ? $_SESSION['anonymous_id'] : '';
$is_anonymous = isset($_SESSION['is_anonymous']) ? $_SESSION['is_anonymous'] : false;

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $title = sanitize($conn, $_POST['title']);
    $content = sanitize($conn, $_POST['content']);
    $type = sanitize($conn, $_POST['type']);
    $media_url = '';
    
    // Always post as anonymous for anonymous users
    $post_anonymous = ($is_anonymous || isset($_POST['is_anonymous'])) ? 1 : 0;

    // Handle file upload for media posts
    if ($type != 'text' && isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $media_url = upload_media($_FILES['media'], $type);
        if ($media_url === false) {
            $error_message = "Error uploading media file";
        }
    } else if ($type != 'text') {
        $error_message = "Media file is required for {$type} posts";
    }

    if (empty($error_message)) {
        if ($user_id > 0) {
            // For registered users
            $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, type, media_url, is_anonymous) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $user_id, $title, $content, $type, $media_url, $post_anonymous);
        } else {
            // For anonymous users
            $stmt = $conn->prepare("INSERT INTO posts (anonymous_id, title, content, type, media_url, is_anonymous) 
                                    VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssss", $anonymous_id, $title, $content, $type, $media_url);
        }

        if ($stmt->execute()) {
            $success_message = "Post created successfully!";
            // Redirect after 2 seconds
            header("refresh:2;url=index.php");
        } else {
            $error_message = "Error posting: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Function to handle media file upload
function upload_media($file, $type) {
    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
  
    $allowed_extensions = [
        'image' => ['jpg', 'jpeg', 'png', 'gif'],
        'video' => ['mp4', 'webm', 'ogg'],
        'audio' => ['mp3', 'wav', 'ogg']
    ];
  
    // Get file extension
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
  
    // Check if file extension is allowed
    if (!in_array($file_ext, $allowed_extensions[$type])) {
        return false;
    }
  
    // Check file size (5MB max)
    if ($file_size > 5242880) {
        return false;
    }
  
    // Generate a unique file name
    $new_file_name = uniqid() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_file_name;
  
    // Move uploaded file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return $upload_path;
    }
  
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Community Notes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #ff80ab;
            --secondary: #f48fb1;
            --accent: #ffb6c1;
            --success: #a7ffeb;
            --danger: #ff5252;
            --light: #fff5f8;
            --dark: #6d4c53;
            --gray: #b2929c;
            --border-radius: 20px;
            --box-shadow: 0 6px 15px rgba(255, 128, 171, 0.2);
        }
        
        @font-face {
            font-family: 'Quicksand';
            src: url('https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap');
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fff5f8;
            color: var(--dark);
            line-height: 1.6;
            background-image: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffcdd2' fill-opacity='0.3' fill-rule='evenodd'%3E%3Cpath d='M20 0C8.96 0 0 8.96 0 20s8.96 20 20 20 20-8.96 20-20S31.04 0 20 0zm0 36c-8.84 0-16-7.16-16-16S11.16 4 20 4s16 7.16 16 16-7.16 16-16 16z'/%3E%3C/g%3E%3C/svg%3E");
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .header:before, .header:after {
            content: "✿";
            font-size: 24px;
            color: var(--secondary);
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .header:before {
            left: -30px;
        }
        
        .header:after {
            right: -30px;
        }
        
        h1 {
            color: var(--primary);
            text-align: center;
            font-weight: 700;
            font-size: 2.2rem;
            position: relative;
            display: inline-block;
        }
        
        h1:after {
            content: "";
            display: block;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--accent), var(--primary));
            border-radius: 10px;
            margin-top: 5px;
        }
        
        .back-btn {
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            background-color: white;
            padding: 8px 15px;
            border-radius: 25px;
            border: 2px solid var(--accent);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(255, 182, 193, 0.2);
        }
        
        .back-btn:hover {
            background-color: var(--accent);
            color: white;
            transform: translateY(-2px);
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 25px;
            border: 2px solid #ffd6e0;
            position: relative;
        }
        
        .card:before {
            content: "❀";
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 18px;
            color: var(--accent);
            opacity: 0.5;
        }
        
        .card:after {
            content: "❀";
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 18px;
            color: var(--accent);
            opacity: 0.5;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
            position: relative;
            padding-left: 20px;
        }
        
        label:before {
            content: "♡";
            position: absolute;
            left: 0;
            color: var(--secondary);
        }
        
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 15px;
            border: 2px solid #ffcdd2;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: inherit;
            background-color: #fffbfd;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 128, 171, 0.25);
        }
        
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .submit-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(255, 128, 171, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .submit-btn:hover {
            background-color: var(--secondary);
            transform: translateY(-3px);
        }
        
        .cancel-btn {
            background-color: white;
            color: var(--dark);
            border: 2px solid var(--accent);
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .cancel-btn:hover {
            background-color: var(--accent);
            color: white;
            transform: translateY(-3px);
        }
        
        .error {
            color: var(--danger);
            margin-bottom: 20px;
            padding: 15px;
            background-color: #ffebee;
            border-radius: var(--border-radius);
            border-left: 5px solid var(--danger);
        }
        
        .success {
            color: #4caf50;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #e8f5e9;
            border-radius: var(--border-radius);
            border-left: 5px solid #4caf50;
        }
        
        .anonymous-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #fff0f3;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            color: var(--dark);
            border: 1px dashed var(--accent);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            background-color: #fff0f3;
            padding: 12px 15px;
            border-radius: var(--border-radius);
            border: 1px dashed var(--accent);
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            padding-left: 0;
        }
        
        .checkbox-group label:before {
            content: none;
        }
        
        #mediaGroup {
            display: none;
            background-color: #fff0f3;
            padding: 20px;
            border-radius: var(--border-radius);
            border: 1px dashed var(--accent);
        }
        
        .help-text {
            color: var(--gray);
            font-size: 0.9rem;
            margin-top: 8px;
            font-style: italic;
        }
        
        input[type="file"] {
            background-color: white;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #ffcdd2;
            width: 100%;
        }
        
        input[type="file"]::file-selector-button {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            margin-right: 15px;
            transition: all 0.3s ease;
        }
        
        input[type="file"]::file-selector-button:hover {
            background-color: var(--primary);
        }
        
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23ff80ab' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 45px;
        }
        
        ::placeholder {
            color: #d8b0bd;
            opacity: 0.7;
        }
        
        .title-area {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .page-title {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(255, 182, 193, 0.3);
        }
        
        .page-subtitle {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .hearts-decoration {
            position: absolute;
            font-size: 24px;
            color: var(--accent);
            opacity: 0.6;
        }
        
        .heart-1 {
            top: 0;
            left: 10%;
            font-size: 18px;
            animation: float 3s ease-in-out infinite;
        }
        
        .heart-2 {
            top: 20px;
            right: 15%;
            font-size: 22px;
            animation: float 4s ease-in-out infinite;
        }
        
        .heart-3 {
            bottom: 10px;
            left: 20%;
            animation: float 5s ease-in-out infinite;
        }
        
        .heart-4 {
            bottom: -5px;
            right: 25%;
            font-size: 16px;
            animation: float 3.5s ease-in-out infinite;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="title-area">
            <div class="hearts-decoration heart-1">♥</div>
            <div class="hearts-decoration heart-2">♥</div>
            <div class="hearts-decoration heart-3">♥</div>
            <div class="hearts-decoration heart-4">♥</div>
            <h1 class="page-title">✨ Create New Post ✨</h1>
            <p class="page-subtitle">Share your thoughts with the community</p>
        </div>
        
        <div class="header">
            <h1>Express Yourself</h1>
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Posts</a>
        </div>
        
        <div class="card">
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($is_anonymous && !$user_id): ?>
                <div class="anonymous-notice">
                    <i class="fas fa-mask"></i>
                    <span>You are posting as <strong>Anonymous</strong> ✨</span>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title (optional)</label>
                    <input type="text" id="title" name="title" placeholder="Give your post a lovely title...">
                </div>
                
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" placeholder="What's on your mind today? Share your thoughts..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="type">Post Type</label>
                    <select id="type" name="type" onchange="toggleMediaUpload()">
                        <option value="text">Text</option>
                        <option value="image">Image</option>
                        <option value="video">Video</option>
                        <option value="audio">Audio</option>
                    </select>
                </div>
                
                <div class="form-group" id="mediaGroup">
                    <label for="media">Upload Media</label>
                    <input type="file" id="media" name="media">
                    <p class="help-text" id="mediaHelp">Allowed file types: jpg, jpeg, png, gif (max 5MB)</p>
                </div>
                
                <?php if ($user_id > 0): ?>
                <div class="checkbox-group">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" value="1">
                    <label for="is_anonymous">Post anonymously</label>
                </div>
                <?php endif; ?>
                
                <div class="buttons">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Share Post
                    </button>
                    <a href="index.php" class="cancel-btn">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleMediaUpload() {
            const type = document.getElementById('type').value;
            const mediaGroup = document.getElementById('mediaGroup');
            const mediaHelp = document.getElementById('mediaHelp');
            
            if (type === 'text') {
                mediaGroup.style.display = 'none';
            } else {
                mediaGroup.style.display = 'block';
                
                if (type === 'image') {
                    mediaHelp.textContent = 'Allowed file types: jpg, jpeg, png, gif (max 5MB)';
                } else if (type === 'video') {
                    mediaHelp.textContent = 'Allowed file types: mp4, webm, ogg (max 5MB)';
                } else if (type === 'audio') {
                    mediaHelp.textContent = 'Allowed file types: mp3, wav, ogg (max 5MB)';
                }
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', toggleMediaUpload);
    </script>
</body>
</html>