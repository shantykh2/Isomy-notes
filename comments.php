<?php
session_start();
include 'db_connect.php';

// Check if post_id is provided
if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
    header("location: index.php");
    exit;
}

$post_id = intval($_GET['post_id']);
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$error_message = '';
$success_message = '';

// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])) {
    if (!$user_id) {
        $error_message = "Anda harus login untuk menambahkan komentar";
    } else {
        $comment_text = sanitize($conn, $_POST['comment']);
        
        if (empty($comment_text)) {
            $error_message = "Komentar tidak boleh kosong";
        } else {
            $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $user_id, $post_id, $comment_text);
            
            if ($stmt->execute()) {
                $success_message = "Komentar berhasil ditambahkan";
                // Clear form
                $_POST['comment'] = '';
            } else {
                $error_message = "Gagal menambahkan komentar: " . $stmt->error;
            }
        }
    }
}

// Get post details
$stmt = $conn->prepare("
    SELECT p.*, u.username, 
           COALESCE(SUM(CASE WHEN v.type = 'up' THEN 1 WHEN v.type = 'down' THEN -1 ELSE 0 END), 0) AS score,
           COUNT(CASE WHEN v.type = 'up' THEN 1 END) AS likes,
           COUNT(CASE WHEN v.type = 'down' THEN 1 END) AS dislikes
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN votes v ON p.id = v.post_id
    WHERE p.id = ?
    GROUP BY p.id
");

$stmt->bind_param("i", $post_id);
$stmt->execute();
$post_result = $stmt->get_result();

if ($post_result->num_rows === 0) {
    header("location: index.php");
    exit;
}

$post = $post_result->fetch_assoc();
$post_username = $post['is_anonymous'] ? 'Anonymous' : ($post['username'] ?? 'Unknown User');
$post_score = intval($post['score']);
$post_likes = intval($post['likes']);
$post_dislikes = intval($post['dislikes']);

// Get comments
$stmt = $conn->prepare("
    SELECT c.*, u.username, u.avatar_url
    FROM comments c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.created_at DESC
");

$stmt->bind_param("i", $post_id);
$stmt->execute();
$comments_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments - Community Notes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff6f9;
            color: #5a3d5c;
            line-height: 1.6;
        }
        
        .navigation {
            margin-bottom: 20px;
            background-color: #f8e1ec;
            padding: 12px;
            border-radius: 25px;
            text-align: center;
            box-shadow: 0 3px 8px rgba(255, 182, 219, 0.3);
        }
        
        .navigation a {
            text-decoration: none;
            color: #d85a8a;
            font-weight: bold;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .navigation a:hover {
            color: #ff3385;
            text-decoration: underline;
        }
        
        .post {
            border: 2px solid #ffd1e8;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 15px;
            background-color: white;
            box-shadow: 0 5px 15px rgba(255, 182, 219, 0.2);
        }
        
        .post-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border-bottom: 2px dotted #ffd1e8;
            padding-bottom: 10px;
        }
        
        .post-header h2 {
            color: #d85a8a;
            margin: 0;
            font-size: 1.6em;
        }
        
        .post-content {
            margin-bottom: 15px;
        }
        
        .post-media img, .post-media video, .post-media audio {
            max-width: 100%;
            max-height: 400px;
            border-radius: 10px;
            border: 3px solid #ffd1e8;
        }
        
        .post-footer {
            color: #ad729c;
            font-size: 0.9em;
            font-style: italic;
            text-align: right;
        }
        
        .vote-buttons {
            margin: 15px 0;
            text-align: center;
        }
        
        .vote-buttons button {
            padding: 8px 15px;
            margin-right: 10px;
            cursor: pointer;
            background-color: #ffd1e8;
            color: #d85a8a;
            border: none;
            border-radius: 20px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
        }
        
        .vote-buttons button:hover {
            background-color: #ff9ec9;
            transform: translateY(-2px);
        }
        
        .vote-buttons a {
            display: inline-block;
            padding: 8px 15px;
            background-color: #ffd1e8;
            color: #d85a8a;
            text-decoration: none;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .comments-section {
            margin-top: 30px;
        }
        
        .comments-section h3 {
            color: #d85a8a;
            text-align: center;
            font-size: 1.8em;
            margin-bottom: 20px;
            position: relative;
        }
        
        .comments-section h3:before, .comments-section h3:after {
            content: "✿";
            color: #ffa6d2;
            margin: 0 10px;
        }
        
        .comment-form {
            margin-bottom: 30px;
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            border: 2px solid #ffd1e8;
        }
        
        .comment-form textarea {
            width: 100%;
            min-height: 100px;
            padding: 15px;
            box-sizing: border-box;
            margin-bottom: 15px;
            border: 2px solid #ffd1e8;
            border-radius: 10px;
            font-family: inherit;
            color: #5a3d5c;
            background-color: #fffbfd;
        }
        
        .comment-form textarea:focus {
            outline: none;
            border-color: #ff9ec9;
            box-shadow: 0 0 8px rgba(255, 158, 201, 0.5);
        }
        
        .comment-form button {
            padding: 10px 20px;
            background-color: #ff9ec9;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 20px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: block;
            margin: 0 auto;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
        }
        
        .comment-form button:hover {
            background-color: #ff6eb0;
            transform: translateY(-2px);
        }
        
        .comment-form p {
            text-align: center;
            color: #ad729c;
            margin-top: 10px;
        }
        
        .comment-form p a {
            color: #d85a8a;
            text-decoration: none;
        }
        
        .comment {
            padding: 15px;
            border-bottom: 2px dotted #ffd1e8;
            margin-bottom: 20px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 3px 8px rgba(255, 182, 219, 0.2);
        }
        
        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .comment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            border: 3px solid #ffd1e8;
        }
        
        .comment-user {
            font-weight: bold;
            color: #d85a8a;
        }
        
        .comment-user a {
            color: #d85a8a;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .comment-user a:hover {
            color: #ff3385;
            text-decoration: underline;
        }
        
        .comment-date {
            color: #ad729c;
            font-size: 0.8em;
            margin-left: auto;
            font-style: italic;
        }
        
        .comment-content {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff9fc;
            border-radius: 10px;
            border-left: 3px solid #ffd1e8;
        }
        
        .error {
            color: #ff4d6d;
            margin-bottom: 15px;
            text-align: center;
            background-color: #ffe6ee;
            padding: 10px;
            border-radius: 10px;
            border-left: 5px solid #ff4d6d;
        }
        
        .success {
            color: #6db66d;
            margin-bottom: 15px;
            text-align: center;
            background-color: #e9ffe9;
            padding: 10px;
            border-radius: 10px;
            border-left: 5px solid #6db66d;
        }
        
        .sensitive-content {
            background-color: #fff9fc;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            color: #7e6e7f;
            margin-bottom: 15px;
            border: 2px dashed #ffd1e8;
        }
        
        .sensitive-warning {
            font-weight: bold;
            color: #ff4d6d;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .reveal-btn {
            background-color: #ffa6d2;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
        }
        
        .reveal-btn:hover {
            background-color: #ff6eb0;
            transform: translateY(-2px);
        }
        
        .comments-list p {
            text-align: center;
            color: #ad729c;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="navigation">
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
         | <a href="create_post.php"><i class="fas fa-pencil-alt"></i> Create Post</a>
         | <a href="profile.php?user_id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a>
         | <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
         | <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
         | <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
        <?php endif; ?>
    </div>
    
    <div class="post">
        <?php 
        // Show sensitive content warning if negative score and not owner
        $is_negative_score = $post_score < 0;
        $is_own_post = $user_id && $user_id == $post['user_id'];
        
        if ($is_negative_score && !$is_own_post):
        ?>
        <div class="sensitive-content" id="sensitive-post">
            <div class="sensitive-warning">⚠️ Konten Sensitif</div>
            <p>Konten ini memiliki nilai negatif atau mungkin berisi materi sensitif.</p>
            <button class="reveal-btn" onclick="revealContent()">Tampilkan Konten</button>
        </div>
        
        <div id="post-content" style="display: none;">
        <?php else: ?>
        <div id="post-content">
        <?php endif; ?>
            <div class="post-header">
                <h2>&#10048; <?php echo htmlspecialchars($post['title'] ?: 'Untitled Post'); ?> &#10048;</h2>
                <div>Score: <?php echo $post_score; ?></div>
            </div>
            
            <div class="post-content">
                <?php if ($post['type'] === 'text'): ?>
                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php else: ?>
                    <div class="post-media">
                        <?php if ($post['type'] === 'image'): ?>
                            <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post image">
                        <?php elseif ($post['type'] === 'video'): ?>
                            <video src="<?php echo htmlspecialchars($post['media_url']); ?>" controls></video>
                        <?php elseif ($post['type'] === 'audio'): ?>
                            <audio src="<?php echo htmlspecialchars($post['media_url']); ?>" controls></audio>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="vote-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button onclick="vote('up')">💗 Upvote (<?php echo $post_likes; ?>)</button>
                    <button onclick="vote('down')">💔 Downvote (<?php echo $post_dislikes; ?>)</button>
                <?php else: ?>
                    <a href="login.php">Login to vote</a>
                <?php endif; ?>
            </div>
            
            <div class="post-footer">
                Posted by ✿ <?php echo htmlspecialchars($post_username); ?> ✿ | 
                Posted on: <?php echo date("M j, Y, g:i a", strtotime($post['created_at'])); ?>
            </div>
        </div>
    </div>
    
    <div class="comments-section">
        <h3>Comments</h3>
        
        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <!-- Comment Form -->
        <div class="comment-form">
            <form method="post">
                <textarea name="comment" placeholder="Add a comment... ✨" required><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                <button type="submit"><?php echo isset($_SESSION['user_id']) ? '💕 Post Comment' : '💕 Login to Comment'; ?></button>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <p><a href="login.php">Login</a> to add comments</p>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Comments List -->
        <div class="comments-list">
            <?php if ($comments_result->num_rows === 0): ?>
                <p>No comments yet. Be the first to comment! ✨</p>
            <?php else: ?>
                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <img class="comment-avatar" src="<?php echo htmlspecialchars($comment['avatar_url'] ?: 'images/default_avatar.png'); ?>" alt="Avatar">
                            <span class="comment-user">
                                <a href="profile.php?user_id=<?php echo $comment['user_id']; ?>">
                                    <?php echo htmlspecialchars($comment['username'] ?: 'Unknown User'); ?>
                                </a>
                            </span>
                            <span class="comment-date"><?php echo date("M j, Y, g:i a", strtotime($comment['created_at'])); ?></span>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function revealContent() {
        if (confirm("Konten ini mungkin berisi materi sensitif. Apakah Anda yakin ingin melihatnya?")) {
            document.getElementById('sensitive-post').style.display = 'none';
            document.getElementById('post-content').style.display = 'block';
        }
    }
    
    async function vote(type) {
        // Check if user is logged in
        if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
            alert('Please login to vote');
            window.location.href = 'login.php';
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('post_id', <?php echo $post_id; ?>);
            formData.append('vote', type);
            
            const response = await fetch('vote.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                // Refresh the page to show updated votes
                location.reload();
            } else {
                alert('Error voting. Please try again.');
            }
        } catch (error) {
            console.error('Error voting:', error);
            alert('Error voting. Please try again.');
        }
    }
    </script>
</body>
</html>