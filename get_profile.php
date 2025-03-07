<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Validate requested user ID
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$user_id = intval($_GET['user_id']);

// Prepare response array
$response = [];

// Check if viewed profile is the logged-in user's profile
$response['is_self'] = $is_logged_in && ($_SESSION['user_id'] == $user_id);

// Get user details
$stmt = $conn->prepare("SELECT username, avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$response['username'] = $user['username'];
$response['avatar_url'] = $user['avatar_url'] ?: 'images/default_avatar.png';

// Get post count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$post_count = $stmt->get_result()->fetch_assoc()['count'];
$response['post_count'] = intval($post_count);

// Get follower count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE followed_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$follower_count = $stmt->get_result()->fetch_assoc()['count'];
$response['follower_count'] = intval($follower_count);

// Get following count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$following_count = $stmt->get_result()->fetch_assoc()['count'];
$response['following_count'] = intval($following_count);

// Check if logged-in user is following the profile user
if ($is_logged_in && !$response['is_self']) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ? AND followed_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $user_id);
    $stmt->execute();
    $is_following = $stmt->get_result()->fetch_assoc()['count'] > 0;
    $response['is_following'] = $is_following;
} else {
    $response['is_following'] = false;
}

// Get user posts
$stmt = $conn->prepare("
    SELECT p.id, p.title, p.content, p.type, p.media_url, p.created_at,
           COUNT(CASE WHEN v.type = 'up' THEN 1 END) as likes,
           COUNT(CASE WHEN v.type = 'down' THEN 1 END) as dislikes,
           COUNT(DISTINCT c.id) as comments
    FROM posts p
    LEFT JOIN votes v ON p.id = v.post_id
    LEFT JOIN comments c ON p.id = c.post_id
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$posts_result = $stmt->get_result();

$posts = [];
while ($post = $posts_result->fetch_assoc()) {
    $posts[] = [
        'id' => $post['id'],
        'title' => $post['title'],
        'content' => $post['content'],
        'type' => $post['type'],
        'media_url' => $post['media_url'],
        'created_at' => $post['created_at'],
        'likes' => intval($post['likes']),
        'dislikes' => intval($post['dislikes']),
        'comments' => intval($post['comments'])
    ];
}

$response['posts'] = $posts;

echo json_encode($response);
?>