<?php
session_start();
include 'db_connect.php';

// Check if anonymous session exists, if not create one
if (!isset($_SESSION['user_id']) && !isset($_SESSION['anonymous_id'])) {
    // Generate a unique anonymous ID
    $_SESSION['anonymous_id'] = uniqid('anon_', true);
    $_SESSION['is_anonymous'] = true;
}

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    // If logged in, show own profile
    if (isset($_SESSION['user_id'])) {
        header("location: profile.php?user_id=" . $_SESSION['user_id']);
        exit;
    } else {
        header("location: login.php");
        exit;
    }
}

// Get user ID from URL parameter
$profile_user_id = intval($_GET['user_id']);
$is_own_profile = isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $profile_user_id);
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$anonymous_id = isset($_SESSION['anonymous_id']) ? $_SESSION['anonymous_id'] : '';
$is_anonymous = isset($_SESSION['is_anonymous']) ? $_SESSION['is_anonymous'] : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Community Notes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #ff80bf;
            --secondary: #f987c5;
            --accent: #ffb8e1;
            --success: #a7e9af;
            --danger: #ff6b97;
            --light: #fff5fa;
            --dark: #6a4162;
            --gray: #b393a5;
            --border-radius: 20px;
            --box-shadow: 0 4px 15px rgba(255, 128, 191, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fff5fa;
            color: var(--dark);
            line-height: 1.6;
            background-image: repeating-linear-gradient(45deg, #ffffff, #ffffff 10px, #fff9fd 10px, #fff9fd 20px);
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header/Navigation */
        .header {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid #ffdbee;
        }
        
        .site-title {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .site-title:before {
            content: "❀";
            margin-right: 8px;
            color: var(--primary);
        }
        
        .site-title:after {
            content: "❀";
            margin-left: 8px;
            color: var(--primary);
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 600;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        /* Profile Header */
        .profile-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 25px;
            border: 2px solid #ffdbee;
            position: relative;
        }
        
        .profile-card:before {
            content: "✿";
            position: absolute;
            font-size: 1.5rem;
            top: 15px;
            left: 15px;
            color: var(--accent);
        }
        
        .profile-card:after {
            content: "✿";
            position: absolute;
            font-size: 1.5rem;
            top: 15px;
            right: 15px;
            color: var(--accent);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #ffdbee;
            box-shadow: var(--box-shadow);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-info h2 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 10px;
            text-shadow: 1px 1px 2px rgba(255, 128, 191, 0.2);
        }
        
        .profile-stats {
            display: flex;
            gap: 25px;
            margin: 15px 0;
            background-color: #fff5fa;
            padding: 12px;
            border-radius: 15px;
            border: 1px dashed #ffdbee;
        }
        
        .stat {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-value {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--primary);
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .profile-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #ff4982;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #ffdbee;
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background-color: #ffc4e2;
            transform: translateY(-2px);
        }
        
        /* Posts Section */
        .posts-section {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            border: 2px solid #ffdbee;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 1px dashed #ffdbee;
        }
        
        .section-title h3 {
            font-size: 1.4rem;
            color: var(--primary);
            position: relative;
            padding-left: 30px;
            padding-right: 30px;
        }
        
        .section-title h3:before {
            content: "♡";
            position: absolute;
            left: 0;
            color: var(--accent);
        }
        
        .section-title h3:after {
            content: "♡";
            position: absolute;
            right: 0;
            color: var(--accent);
        }
        
        .post {
            border-radius: var(--border-radius);
            border: 2px solid #ffdbee;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            background-color: #fffbfd;
        }
        
        .post:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 128, 191, 0.2);
        }
        
        .post-header {
            margin-bottom: 15px;
            border-bottom: 1px dotted #ffdbee;
            padding-bottom: 10px;
        }
        
        .post-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .post-content {
            margin-bottom: 15px;
        }
        
        .post-media {
            margin-bottom: 15px;
        }
        
        .post-media img, 
        .post-media video, 
        .post-media audio {
            max-width: 100%;
            max-height: 400px;
            border-radius: 12px;
            border: 3px solid #ffdbee;
        }
        
        .post-footer {
            color: var(--gray);
            font-size: 0.9rem;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background-color: #fff5fa;
            padding: 10px;
            border-radius: 12px;
        }
        
        .post-footer a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .post-footer a:hover {
            text-decoration: underline;
        }
        
        .post-meta {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Sensitive Content */
        .sensitive-content {
            background-color: #fff5fa;
            padding: 30px 20px;
            border-radius: var(--border-radius);
            text-align: center;
            color: var(--gray);
            margin-bottom: 15px;
            border: 2px dashed #ffdbee;
        }
        
        .sensitive-warning {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: bold;
            color: var(--danger);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .reveal-btn {
            background-color: var(--accent);
            color: var(--dark);
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .reveal-btn:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Loader */
        .loader-container {
            display: flex;
            justify-content: center;
            padding: 30px;
        }
        
        .loader {
            border: 4px solid #ffdbee;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .profile-actions {
                justify-content: center;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
            background-color: #fff5fa;
            border-radius: 15px;
            border: 1px dashed #ffdbee;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header/Navigation -->
        <div class="header">
            <a href="index.php" class="site-title">Klan's Community Notes</a>
            <div class="nav-links">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="create_post.php"><i class="fas fa-plus"></i> Create Post</a>
                    <a href="profile.php?user_id=<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user"></i> My Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php elseif ($is_anonymous): ?>
                    <a href="create_post.php"><i class="fas fa-plus"></i> Create Post</a>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Profile Information -->
        <div class="profile-card">
            <div class="profile-header">
                <img id="profilePic" class="profile-image" src="images/default_avatar.png" alt="Profile picture">
                <div class="profile-info">
                    <h2 id="username">Loading...</h2>
                    
                    <div class="profile-stats">
                        <div class="stat">
                            <span id="postCountValue" class="stat-value">0</span>
                            <span class="stat-label">Posts</span>
                        </div>
                        <div class="stat">
                            <span id="followerCountValue" class="stat-value">0</span>
                            <span class="stat-label">Followers</span>
                        </div>
                        <div class="stat">
                            <span id="followingCountValue" class="stat-value">0</span>
                            <span class="stat-label">Following</span>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <button id="followBtn" class="btn btn-primary" style="display: none;">
                            <i class="fas fa-user-plus"></i> Follow
                        </button>
                        <?php if ($is_own_profile): ?>
                        <a href="profile_edit.php" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Posts Section -->
        <div class="posts-section">
            <div class="section-title">
                <h3>Posts</h3>
            </div>
            
            <div id="posts" class="posts-container">
                <div class="loader-container">
                    <div class="loader"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    const profilePic = document.getElementById('profilePic');
    const username = document.getElementById('username');
    const postCountValue = document.getElementById('postCountValue');
    const followerCountValue = document.getElementById('followerCountValue');
    const followingCountValue = document.getElementById('followingCountValue');
    const followBtn = document.getElementById('followBtn');
    const posts = document.getElementById('posts');
    
    async function getProfile(userId) {
        try {
            const response = await fetch(`get_profile.php?user_id=${userId}`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch profile data');
            }
            
            const data = await response.json();
            
            if (data.error) {
                posts.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Error: ${data.error}</p></div>`;
                return;
            }
            
            // Update profile information
            profilePic.src = data.avatar_url || 'images/default_avatar.png';
            username.textContent = data.username;
            postCountValue.textContent = data.post_count;
            followerCountValue.textContent = data.follower_count;
            followingCountValue.textContent = data.following_count;
            
            // Handle follow button
            if (data.is_self) {
                followBtn.style.display = 'none';
            } else {
                followBtn.style.display = 'inline-flex';
                
                if (data.is_following) {
                    followBtn.innerHTML = '<i class="fas fa-user-minus"></i> Unfollow';
                    followBtn.className = 'btn btn-danger';
                } else {
                    followBtn.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
                    followBtn.className = 'btn btn-primary';
                }
            }
            
            // Display posts
            if (data.posts.length === 0) {
                posts.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-heart"></i>
                        <p>No posts yet.</p>
                    </div>
                `;
            } else {
                let postsHTML = '';
                
                data.posts.forEach(post => {
                    let mediaContent = '';
                    
                    if (post.type === 'text') {
                        mediaContent = `<p>${post.content}</p>`;
                    } else if (post.type === 'image') {
                        mediaContent = `<div class="post-media"><img src="${post.media_url}" alt="Post image"></div>`;
                    } else if (post.type === 'video') {
                        mediaContent = `<div class="post-media"><video src="${post.media_url}" controls></video></div>`;
                    } else if (post.type === 'audio') {
                        mediaContent = `<div class="post-media"><audio src="${post.media_url}" controls></audio></div>`;
                    }
                    
                    // Check if post has negative score for sensitive content
                    const isNegativeScore = post.likes < post.dislikes;
                    const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
                    const isOwnPost = (currentUserId == <?php echo $profile_user_id; ?>);
                    
                    if (isNegativeScore && !isOwnPost) {
                        postsHTML += `
                            <div class="post" data-post-id="${post.id}">
                                <div class="sensitive-content" id="sensitive-${post.id}">
                                    <div class="sensitive-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Konten Sensitif
                                    </div>
                                    <p>Konten ini memiliki nilai negatif atau mungkin berisi materi sensitif.</p>
                                    <button class="reveal-btn" onclick="revealContent(${post.id})">
                                        <i class="fas fa-eye"></i> Tampilkan Konten
                                    </button>
                                </div>
                                <div style="display:none;" id="content-${post.id}">
                                    <div class="post-header">
                                        <h3 class="post-title">♡ ${post.title || 'Untitled Post'} ♡</h3>
                                    </div>
                                    <div class="post-content">
                                        ${mediaContent}
                                    </div>
                                    <div class="post-footer">
                                        <span class="post-meta"><i class="fas fa-heart"></i> ${post.likes}</span>
                                        <span class="post-meta"><i class="fas fa-heart-broken"></i> ${post.dislikes}</span>
                                        <a href="comments.php?post_id=${post.id}" class="post-meta">
                                            <i class="fas fa-comments"></i> ${post.comments}
                                        </a>
                                        <span class="post-meta">
                                            <i class="fas fa-clock"></i> ${new Date(post.created_at).toLocaleString()}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        postsHTML += `
                            <div class="post">
                                <div class="post-header">
                                    <h3 class="post-title">♡ ${post.title || 'Untitled Post'} ♡</h3>
                                </div>
                                <div class="post-content">
                                    ${mediaContent}
                                </div>
                                <div class="post-footer">
                                    <span class="post-meta"><i class="fas fa-heart"></i> ${post.likes}</span>
                                    <span class="post-meta"><i class="fas fa-heart-broken"></i> ${post.dislikes}</span>
                                    <a href="comments.php?post_id=${post.id}" class="post-meta">
                                        <i class="fas fa-comments"></i> ${post.comments}
                                    </a>
                                    <span class="post-meta">
                                        <i class="fas fa-clock"></i> ${new Date(post.created_at).toLocaleString()}
                                    </span>
                                </div>
                            </div>
                        `;
                    }
                });
                
                posts.innerHTML = postsHTML;
            }
        } catch (error) {
            console.error('Error fetching profile:', error);
            posts.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Error loading profile data. Please try again later.</p>
                </div>
            `;
        }
    }
    
    function revealContent(postId) {
        if (confirm("Konten ini mungkin berisi materi sensitif. Apakah Anda yakin ingin melihatnya?")) {
            document.getElementById(`sensitive-${postId}`).style.display = 'none';
            document.getElementById(`content-${postId}`).style.display = 'block';
        }
    }
    
    followBtn.addEventListener('click', async () => {
        const userId = new URLSearchParams(window.location.search).get('user_id');
        const action = followBtn.textContent.trim().toLowerCase().includes('unfollow') ? 'unfollow' : 'follow';
        
        try {
            const response = await fetch('follow.php', {
                method: 'POST',
                body: JSON.stringify({ userId, action }),
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (!response.ok) {
                throw new Error('Failed to process follow/unfollow request');
            }
            
            // Reload profile data
            getProfile(userId);
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to process request. Please try again later.');
        }
    });
    
    // Get user ID from URL and load profile
    const params = new URLSearchParams(window.location.search);
    const userId = params.get('user_id');
    
    if (userId) {
        getProfile(userId);
    } else {
        posts.innerHTML = '<div class="empty-state"><i class="fas fa-user-slash"></i><p>User ID is missing.</p></div>';
    }
    </script>
</body>
</html>