<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to follow/unfollow users']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['userId']) || !is_numeric($input['userId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$followed_id = intval($input['userId']);
$follower_id = $_SESSION['user_id'];

// Can't follow yourself
if ($follower_id == $followed_id) {
    http_response_code(400);
    echo json_encode(['error' => 'You cannot follow yourself']);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $followed_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Follow or unfollow based on action
if (isset($input['action']) && $input['action'] === 'unfollow') {
    // Unfollow
    $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
    $stmt->bind_param("ii", $follower_id, $followed_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'User unfollowed successfully']);
} else {
    // Check if already following
    $stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
    $stmt->bind_param("ii", $follower_id, $followed_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Already following this user']);
        exit;
    }
    
    // Follow
    $stmt = $conn->prepare("INSERT INTO follows (follower_id, followed_id, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $follower_id, $followed_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'User followed successfully']);
}
?>