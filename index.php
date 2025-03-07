<?php
session_start();
include('db_connect.php');

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

// Get all posts, including those with negative scores
$sql = "SELECT 
          p.*, 
          u.username,
          COALESCE(SUM(CASE WHEN v.type = 'up' THEN 1 WHEN v.type = 'down' THEN -1 ELSE 0 END), 0) AS score,
          COUNT(DISTINCT c.id) AS comment_count,
          (SELECT type FROM votes WHERE user_id = ? AND post_id = p.id) AS user_vote,
          sc.status AS sensitive_status,
          sc.reason AS sensitive_reason
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN votes v ON p.id = v.post_id
        LEFT JOIN comments c ON p.id = c.post_id
        LEFT JOIN sensitive_content sc ON p.id = sc.post_id
        GROUP BY p.id
        ORDER BY p.created_at DESC";

// Use prepared statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>❀ Community Notes ❀</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Girly Theme CSS */
        :root {
            --primary: #FF80AB;
            --primary-light: #ffb2c9;
            --primary-dark: #c94f7c;
            --secondary: #F48FB1;
            --accent: #AD1457;
            --success: #9CCC65;
            --danger: #EF5350;
            --light: #FFF5F7;
            --dark: #4a4a4a;
            --gray: #9e9e9e;
            --border-radius: 15px;
            --box-shadow: 0 5px 15px rgba(255, 128, 171, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #FFF5F7;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffb2c9' fill-opacity='0.2'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background-color: white;
            padding: 20px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            border: 2px solid var(--primary-light);
            position: relative;
        }
        
        .header::before, 
        .header::after {
            content: "❀";
            position: absolute;
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .header::before {
            top: 10px;
            left: 10px;
        }
        
        .header::after {
            top: 10px;
            right: 10px;
        }
        
        .header h1 {
            color: var(--accent);
            font-size: 2.2rem;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .header h1 i {
            color: var(--primary);
            margin: 0 5px;
        }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .user-welcome {
            font-weight: 500;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 7px;
        }
        
        .user-welcome strong {
            color: var(--accent);
        }
        
        .nav-links a {
            color: var(--accent);
            text-decoration: none;
            margin-left: 20px;
            transition: all 0.3s;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .nav-links a:hover {
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .nav-links a i {
            font-size: 1.1rem;
        }
        
        .create-post-btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(255, 128, 171, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .create-post-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 128, 171, 0.4);
        }
        
        .post {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
            transition: all 0.3s;
            border: 1px solid var(--primary-light);
        }
        
        .post:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(255, 128, 171, 0.2);
        }
        
        .post.upvoted {
            border-left: 5px solid var(--success);
        }
        
        .post.downvoted {
            border-left: 5px solid var(--danger);
        }
        
        .post-header {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .post-header h3 {
            color: var(--accent);
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .post-content {
            margin-bottom: 20px;
            line-height: 1.7;
        }
        
        .post-media img, 
        .post-media video, 
        .post-media audio {
            max-width: 100%;
            max-height: 400px;
            border-radius: var(--border-radius);
            border: 1px solid var(--primary-light);
        }
        
        .vote-buttons {
            display: flex;
            gap: 15px;
            margin: 20px 0;
        }
        
        .vote-btn {
            background-color: white;
            border: 2px solid var(--primary-light);
            padding: 8px 16px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            color: var(--dark);
            font-family: 'Poppins', sans-serif;
        }
        
        .vote-btn:hover {
            background-color: var(--light);
            transform: translateY(-2px);
        }
        
        .vote-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .post-footer {
            color: var(--gray);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--primary-light);
        }
        
        .post-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .post-footer a:hover {
            color: var(--accent);
        }
        
        .sensitive-content {
            background-color: #fff5fa;
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
        
        .hidden-content {
            display: none;
        }
        
        /* Cute little heart spinner for loading content */
        .heart-spinner {
            width: 40px;
            height: 40px;
            margin: 40px auto;
            position: relative;
            animation: heartBeat 1.2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
        }
        
        .heart-spinner:before,
        .heart-spinner:after {
            content: "";
            background-color: var(--primary);
            width: 20px;
            height: 30px;
            border-radius: 20px 20px 0 0;
            position: absolute;
            transform: rotate(-45deg);
            transform-origin: 0 100%;
            left: 20px;
            top: 5px;
        }
        
        .heart-spinner:after {
            transform: rotate(45deg);
            transform-origin: 100% 100%;
            left: 0;
        }
        
        @keyframes heartBeat {
            0% { transform: scale(0.8); }
            14% { transform: scale(1); }
            28% { transform: scale(0.8); }
            42% { transform: scale(1); }
            70% { transform: scale(0.8); }
            100% { transform: scale(0.8); }
        }
        
        /* Decorative elements */
        .decorative-hearts {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .decorative-heart {
            position: absolute;
            color: var(--primary-light);
            opacity: 0.2;
            font-size: 20px;
            user-select: none;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--primary-light);
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .navigation {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-links {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .nav-links a {
                margin: 5px;
            }
            
            .post-footer {
                justify-content: center;
            }
        }
        
        /* Screensaver Styles */
        .screensaver {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #FFF0F5;
            display: none;
            z-index: 9999;
            overflow: hidden;
        }

        .screensaver-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
            color: var(--dark);
        }

        .screensaver-title {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 0.5rem;
            text-shadow: 0 0 10px rgba(255, 128, 171, 0.5);
            animation: pulse 2s infinite;
        }

        .screensaver-subtitle {
            font-size: 1.5rem;
            color: var(--primary-dark);
            margin-bottom: 2rem;
            opacity: 0.8;
        }

        .floating-icons {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .floating-icon {
            position: absolute;
            color: var(--primary);
            opacity: 0.3;
            user-select: none;
            animation: float 20s linear infinite;
        }

        @keyframes pulse {
            0% { opacity: 0.7; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
            100% { opacity: 0.7; transform: scale(1); }
        }

        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); }
            100% { transform: translateY(-100px) rotate(360deg); }
        }

        .time-display {
            font-size: 4rem;
            font-weight: 200;
            margin-bottom: 1rem;
            color: var(--accent);
        }

        .date-display {
            font-size: 1.5rem;
            opacity: 0.7;
            margin-bottom: 3rem;
            color: var(--primary-dark);
        }

        .screensaver-button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            opacity: 0;
            animation: fadeIn 2s 1s forwards;
        }

        .screensaver-button:hover {
            background-color: var(--accent);
            transform: scale(1.05);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    
    <div class="decorative-hearts" id="decorativeHearts"></div>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-heart"></i> Isomy Notes <i class="fas fa-heart"></i></h1>
            
            <div class="navigation">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Logged-in user navigation -->
                    <div class="user-section">
                        <p class="user-welcome"><i class="fas fa-crown"></i> Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
                        <a href="create_post.php" class="create-post-btn">
                            <i class="fas fa-plus"></i> Create New Post
                        </a>
                    </div>
                    <div class="nav-links">
                        <a href="profile.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                <?php elseif (isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous']): ?>
                    <!-- Anonymous user navigation -->
                    <div class="user-section">
                        <p class="user-welcome"><i class="fas fa-mask"></i> Browsing as <strong>Anonymous</strong></p>
                        <a href="create_post.php" class="create-post-btn">
                            <i class="fas fa-plus"></i> Create New Post
                        </a>
                    </div>
                    <div class="nav-links">
                        <a href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                <?php else: ?>
                    <div></div>
                    <div class="nav-links">
                        <a href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="posts">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $score = (int)$row['score'];
                    $username = $row['is_anonymous'] ? 'Anonymous <i class="fas fa-mask"></i>' : htmlspecialchars($row['username'] ?? 'Unknown User');
                    $postClass = 'post';
                    
                    // Debug - Tampilkan informasi skor
                    echo "<!-- DEBUG: Post ID: {$row['id']}, Score: $score -->";
                    
                    // Add classes based on user's vote
                    if ($row['user_vote'] === 'up') {
                        $postClass .= ' upvoted';
                    } else if ($row['user_vote'] === 'down') {
                        $postClass .= ' downvoted';
                    }
                    
                    echo "<div class='$postClass' data-id='{$row['id']}'>";
                    
                    // Pastikan kita periksa dengan benar jika skor negatif
                    $is_negative_score = ($score < 0); // Paksa konversi ke boolean
                    $is_own_post = ($user_id > 0 && $user_id == $row['user_id']); // Pastikan user_id > 0
                    
                    // Debug - Tampilkan hasil pengecekan
                    echo "<!-- DEBUG: Negative: " . ($is_negative_score ? "Yes" : "No") . ", Own post: " . ($is_own_post ? "Yes" : "No") . " -->";
                    
                    // Bungkus postingan dengan konten sensitif jika sesuai kriteria
                    if ($is_negative_score && !$is_own_post) {
                        echo "<div class='sensitive-content' id='sensitive-post-{$row['id']}'>
                                <div class='sensitive-warning'><i class='fas fa-exclamation-circle'></i> Konten Sensitif</div>
                                <p>Konten ini memiliki nilai negatif atau mungkin berisi materi sensitif.</p>
                                <button class='reveal-btn' onclick='revealContent({$row['id']})'><i class='fas fa-eye'></i> Tampilkan Konten</button>
                              </div>";
                        echo "<div class='post-content hidden-content' id='post-content-{$row['id']}'>";
                    } else {
                        echo "<div class='post-content'>";
                    }
                    
                    // Post header with title
                    echo "<div class='post-header'>";
                    if (!empty($row['title'])) {
                        echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                    }
                    echo "</div>";
                    
                    // Post content based on type
                    if ($row['type'] === 'text') {
                        echo "<p>" . nl2br(htmlspecialchars($row['content'])) . "</p>";
                    } else {
                        echo "<div class='post-media'>";
                        if ($row['type'] === 'image') {
                            echo "<img src='" . htmlspecialchars($row['media_url']) . "' alt='Post image'>";
                        } else if ($row['type'] === 'video') {
                            echo "<video src='" . htmlspecialchars($row['media_url']) . "' controls></video>";
                        } else if ($row['type'] === 'audio') {
                            echo "<audio src='" . htmlspecialchars($row['media_url']) . "' controls></audio>";
                        }
                        echo "</div>";
                    }
                    echo "</div>"; // Close post-content
                    
                    // Vote buttons - everyone can vote now
                    echo "<div class='vote-buttons'>";
                    echo "<button class='vote-btn " . ($row['user_vote'] === 'up' ? 'active' : '') . "' onclick='vote({$row['id']}, \"up\")'><i class='fas fa-heart'></i> Love it</button>";
                    echo "<button class='vote-btn " . ($row['user_vote'] === 'down' ? 'active' : '') . "' onclick='vote({$row['id']}, \"down\")'><i class='fas fa-heart-broken'></i> Not for me</button>";
                    echo "</div>";
                    
                    // Post footer
                    echo "<div class='post-footer'>";
                    echo "<span><i class='fas fa-user'></i> $username</span>";
                    echo "<span><i class='fas fa-star'></i> Score: $score</span>";
                    echo "<a href='comments.php?post_id={$row['id']}'><i class='fas fa-comments'></i> " . $row['comment_count'] . " Comments</a>";
                    echo "<span><i class='fas fa-clock'></i> " . date("M j, Y, g:i a", strtotime($row['created_at'])) . "</span>";
                    echo "</div>";
                    
                    echo "</div>"; // Close post div
                }
            } else {
                echo "<div class='empty-state'>
                        <i class='fas fa-heart-broken'></i>
                        <p>No posts available yet. Be the first to share something!</p>
                      </div>";
            }
            ?>
        </div>
    </div>
    
    <!-- Screensaver Element -->
    <div class="screensaver" id="screensaver">
        <div class="floating-icons" id="floatingIcons"></div>
        <div class="screensaver-content">
            <div class="time-display" id="timeDisplay">00:00:00</div>
            <div class="date-display" id="dateDisplay">Memuat...</div>
            <h1 class="screensaver-title">✿ Isomy Notes ✿</h1>
            <p class="screensaver-subtitle">Bagikan ceritamu dengan dunia</p>
            <button class="screensaver-button" id="exitScreensaver">Klik untuk Kembali</button>
        </div>
    </div>
    
    <script>
    // Fungsi untuk menampilkan konten sensitif
    function revealContent(postId) {
        console.log("Revealing content for post ID:", postId);
        const sensitiveElement = document.getElementById(`sensitive-post-${postId}`);
        const contentElement = document.getElementById(`post-content-${postId}`);
        
        console.log("Sensitive element:", sensitiveElement);
        console.log("Content element:", contentElement);
        
        if (confirm("Konten ini mungkin berisi materi sensitif. Apakah Anda yakin ingin melihatnya?")) {
            if (sensitiveElement) {
                sensitiveElement.style.display = 'none';
                console.log("Sensitive element hidden");
            }
            if (contentElement) {
                contentElement.style.display = 'block';
                console.log("Content element shown");
            }
        }
    }
    
    // Create decorative hearts
    function createDecorativeHearts() {
        const hearts = document.getElementById('decorativeHearts');
        const heartCount = 20;
        
        for (let i = 0; i < heartCount; i++) {
            const heart = document.createElement('div');
            heart.classList.add('decorative-heart');
            heart.innerHTML = '♥';
            
            const size = Math.random() * 20 + 10;
            const left = Math.random() * 100;
            const top = Math.random() * 100;
            
            heart.style.fontSize = `${size}px`;
            heart.style.left = `${left}%`;
            heart.style.top = `${top}%`;
            
            hearts.appendChild(heart);
        }
    }
    
    // Initialize hearts
    createDecorativeHearts();
    
    // Post interactions
    function showSensitive(el) {
        if (confirm("This post may contain sensitive content. Are you sure you want to view it?")) {
            el.style.display = 'none';
            el.nextElementSibling.classList.remove('hidden-content');
        }
    }
    
    async function vote(postId, type) {
        try {
            const formData = new FormData();
            formData.append('post_id', postId);
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
    
    // Screensaver functionality
    (function() {
        // Configuration
        const waktuNonaktif = 5000; // 5 detik
        const ikon = ['fa-heart', 'fa-star', 'fa-crown', 
                      'fa-sun', 'fa-moon', 'fa-music', 'fa-heart'];
        
        // DOM Elements
        const screensaver = document.getElementById('screensaver');
        const timeDisplay = document.getElementById('timeDisplay');
        const dateDisplay = document.getElementById('dateDisplay');
        const floatingIcons = document.getElementById('floatingIcons');
        const exitButton = document.getElementById('exitScreensaver');
        
        // Variables
        let timerNonaktif;
        let intervalJam;
        
        // Membuat ikon mengambang
        function buatIkonMengambang() {
            floatingIcons.innerHTML = '';
            const jumlahIkon = Math.floor(window.innerWidth / 100); // Jumlah ikon responsif
            
            for (let i = 0; i < jumlahIkon; i++) {
                const ikon1 = document.createElement('i');
                const ikonAcak = ikon[Math.floor(Math.random() * ikon.length)];
                ikon1.className = `fas ${ikonAcak} floating-icon`;
                
                // Posisi acak dan durasi animasi
                const ukuran = Math.floor(Math.random() * 30) + 20; // 20-50px
                const posisiKiri = Math.floor(Math.random() * 100); // 0-100%
                const penundaan = Math.floor(Math.random() * 15); // 0-15s
                const durasi = Math.floor(Math.random() * 15) + 15; // 15-30s
                
                ikon1.style.fontSize = `${ukuran}px`;
                ikon1.style.left = `${posisiKiri}%`;
                ikon1.style.animationDuration = `${durasi}s`;
                ikon1.style.animationDelay = `${penundaan}s`;
                
                floatingIcons.ikon1.style.animationDuration = `${durasi}s`;
                ikon1.style.animationDelay = `${penundaan}s`;
                
                floatingIcons.appendChild(ikon1);
            }
        }
        
        // Perbarui jam
        function perbaruiJam() {
            const sekarang = new Date();
            
            // Waktu
            const jam = sekarang.getHours().toString().padStart(2, '0');
            const menit = sekarang.getMinutes().toString().padStart(2, '0');
            const detik = sekarang.getSeconds().toString().padStart(2, '0');
            timeDisplay.textContent = `${jam}:${menit}:${detik}`;
            
            // Tanggal
            const opsi = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateDisplay.textContent = sekarang.toLocaleDateString('id-ID', opsi);
        }
        
        // Tampilkan screensaver
        function tampilkanScreensaver() {
            buatIkonMengambang();
            perbaruiJam();
            
            screensaver.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Cegah scrolling
            
            // Mulai jam
            intervalJam = setInterval(perbaruiJam, 1000);
        }
        
        // Sembunyikan screensaver
        function sembunyikanScreensaver() {
            screensaver.style.display = 'none';
            document.body.style.overflow = '';
            
            // Bersihkan interval jam
            clearInterval(intervalJam);
            
            // Reset timer nonaktif
            resetTimer();
        }
        
        // Reset timer nonaktif
        function resetTimer() {
            clearTimeout(timerNonaktif);
            timerNonaktif = setTimeout(tampilkanScreensaver, waktuNonaktif);
        }
        
        // Event listener
        function setupEventListener() {
            // Event aktivitas pengguna
            ['mousemove', 'mousedown', 'keypress', 'touchstart', 'scroll'].forEach(jenisEvent => {
                document.addEventListener(jenisEvent, resetTimer, true);
            });
            
            // Tombol keluar
            exitButton.addEventListener('click', sembunyikanScreensaver);
            
            // Klik di mana saja untuk keluar
            screensaver.addEventListener('click', function(e) {
                if (e.target.id !== 'exitScreensaver') {
                    sembunyikanScreensaver();
                }
            });
            
            // Resize window
            window.addEventListener('resize', function() {
                if (screensaver.style.display === 'block') {
                    buatIkonMengambang();
                }
            });
        }
        
        // Inisialisasi
        function init() {
            setupEventListener();
            resetTimer();
        }
        
        // Mulai semuanya
        init();
    })();
    </script>
</body>
</html>
            
