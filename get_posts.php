<?php
session_start();
include 'db_connect.php';

// Get user ID if logged in, or set to 0 if not
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Get all posts, including negative score posts (for sensitive content handling)
$sql = "SELECT 
          p.*,
          u.username, 
          COALESCE(SUM(CASE WHEN v.type = 'up' THEN 1 WHEN v.type = 'down' THEN -1 ELSE 0 END), 0) AS score,
          COUNT(DISTINCT c.id) AS comment_count,
          COUNT(CASE WHEN v.type = 'up' THEN 1 END) AS like_count,
          COUNT(CASE WHEN v.type = 'down' THEN 1 END) AS dislike_count
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN votes v ON p.id = v.post_id
        LEFT JOIN comments c ON p.id = c.post_id
        GROUP BY p.id
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);

$posts = [];

while($row = $result->fetch_assoc()) {
  // If post is anonymous, hide the real username
  $username = $row['is_anonymous'] ? 'Anonymous' : ($row['username'] ?? 'Unknown User');
  
  $post = [
    'id' => $row['id'],
    'user_id' => $row['user_id'],
    'title' => $row['title'],
    'content' => $row['content'],
    'username' => $username,
    'score' => (int)$row['score'],
    'likes' => (int)$row['like_count'],
    'dislikes' => (int)$row['dislike_count'],
    'type' => $row['type'],
    'media_url' => $row['media_url'],
    'comment_count' => (int)$row['comment_count'],
    'created_at' => $row['created_at']
  ];
  
  $posts[] = $post;  
}

// For carousel, prioritize posts with positive scores
usort($posts, function($a, $b) {
  return $b['score'] - $a['score'];
});

header('Content-Type: application/json');
echo json_encode($posts);
?>