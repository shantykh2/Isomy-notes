<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    
    // Handle profile picture upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Debug info
        error_log("File upload info: " . print_r($_FILES['avatar'], true));
        
        // Validate file type
        if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            $error_message = "Hanya file JPG, PNG, dan GIF yang diperbolehkan";
        }
        // Validate file size
        elseif ($_FILES['avatar']['size'] > $max_size) {
            $error_message = "Ukuran file terlalu besar (maksimal 2MB)";
        }
        else {
            // Create upload directory if it doesn't exist
            $upload_dir = 'images/avatars/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $error_message = "Gagal membuat direktori upload";
                }
            }
            
            // Generate unique filename
            $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                // Update user profile in database
                $stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                $stmt->bind_param("si", $upload_path, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Foto profil berhasil diperbarui";
                } else {
                    $error_message = "Gagal memperbarui profil: " . $stmt->error;
                }
                
                $stmt->close();
            } else {
                $error_message = "Gagal mengupload file: " . error_get_last()['message'];
            }
        }
    } else if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] != 0) {
        // Handle file upload errors
        $upload_errors = [
            1 => "File terlalu besar (melebihi upload_max_filesize di php.ini)",
            2 => "File terlalu besar (melebihi MAX_FILE_SIZE dalam form HTML)",
            3 => "File hanya terupload sebagian",
            4 => "Tidak ada file yang dipilih",
            6 => "Tidak ada folder temporary",
            7 => "Gagal menyimpan file ke disk",
            8 => "PHP extension menghentikan upload file"
        ];
        $error_code = $_FILES['avatar']['error'];
        $error_message = "Error upload: " . ($upload_errors[$error_code] ?? "Error tidak diketahui");
    }
}

// Get current profile picture
$stmt = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$avatar_url = $result->fetch_assoc()['avatar_url'] ?? 'images/default_avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Community Notes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --success: #4cc9f0;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border-radius: 10px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .site-title {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        /* Main Content Card */
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 25px;
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Avatar Preview Section */
        .avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--light);
            border-radius: var(--border-radius);
        }
        
        .avatar-preview {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 5px solid white;
            box-shadow: var(--box-shadow);
        }
        
        .avatar-label {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        /* Form Styling */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 12px 15px;
            background-color: var(--light);
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        /* File Input Styling */
        .file-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: var(--border-radius);
            background-color: var(--light);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .file-upload:hover {
            border-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .file-upload input[type="file"] {
            display: none;
        }
        
        .upload-icon {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .upload-text {
            font-weight: 500;
            color: var(--gray);
            text-align: center;
        }
        
        .file-help-text {
            margin-top: 10px;
            font-size: 0.85rem;
            color: var(--gray);
            text-align: center;
        }
        
        /* Button Styling */
        .btn {
            display: inline-block;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
        }
        
        .btn-secondary {
            background-color: var(--light);
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background-color: #e2e6ea;
        }
        
        .btn-icon {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Actions Section */
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        /* Alert Styling */
        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-danger {
            background-color: rgba(247, 37, 133, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header/Navigation -->
        <div class="header">
            <a href="index.php" class="site-title">Community Notes</a>
            <div class="nav-links">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="create_post.php"><i class="fas fa-plus"></i> Create Post</a>
                <a href="profile.php?user_id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="card">
            <h1 class="page-title"><i class="fas fa-user-edit"></i> Edit Profile</h1>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <!-- Avatar Preview -->
                <div class="avatar-section">
                    <h3 class="avatar-label">Profile Picture</h3>
                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Profile Picture" class="avatar-preview" id="preview">
                </div>
                
                <!-- File Upload -->
                <div class="form-group">
                    <label class="form-label">Upload New Profile Picture</label>
                    <label class="file-upload" for="avatar">
                        <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/gif" onchange="previewImage(this);">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <div class="upload-text">Click to select a file or drag and drop</div>
                        <p class="file-help-text">Acceptable formats: JPG, PNG, GIF (Max 2MB)</p>
                    </label>
                </div>
                
                <!-- Actions -->
                <div class="actions">
                    <button type="submit" class="btn btn-primary btn-icon">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="profile.php?user_id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-secondary btn-icon">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Preview image before upload
    function previewImage(input) {
        const preview = document.getElementById('preview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Drag and drop functionality
    const dropArea = document.querySelector('.file-upload');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropArea.style.borderColor = var(--primary);
        dropArea.style.backgroundColor = 'rgba(67, 97, 238, 0.1)';
    }
    
    function unhighlight() {
        dropArea.style.borderColor = '#ddd';
        dropArea.style.backgroundColor = 'var(--light)';
    }
    
    dropArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        const fileInput = document.getElementById('avatar');
        
        fileInput.files = files;
        previewImage(fileInput);
    }
    </script>
</body>
</html>