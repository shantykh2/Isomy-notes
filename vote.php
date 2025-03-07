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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    if (!isset($_POST['post_id']) || !isset($_POST['vote'])) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    $post_id = (int)$_POST['post_id'];
    $vote_type = sanitize($conn, $_POST['vote']);
    
    // Validate vote type
    if ($vote_type !== 'up' && $vote_type !== 'down') {
        echo json_encode(['error' => 'Invalid vote type']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        if ($user_id > 0) {
            // Logged-in user vote
            $stmt = $conn->prepare("SELECT id, type FROM votes WHERE user_id = ? AND post_id = ?");
            $stmt->bind_param("ii", $user_id, $post_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $existing_vote = $result->fetch_assoc();
                
                // If same vote type, remove the vote (toggle)
                if ($existing_vote['type'] === $vote_type) {
                    $delete_stmt = $conn->prepare("DELETE FROM votes WHERE id = ?");
                    $delete_stmt->bind_param("i", $existing_vote['id']);
                    $delete_stmt->execute();
                } else {
                    // Update existing vote
                    $update_stmt = $conn->prepare("UPDATE votes SET type = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $vote_type, $existing_vote['id']);
                    $update_stmt->execute();
                }
            } else {
                // Insert new vote
                $insert_stmt = $conn->prepare("INSERT INTO votes (user_id, post_id, type) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iis", $user_id, $post_id, $vote_type);
                $insert_stmt->execute();
            }
        } else if ($is_anonymous) {
            // Anonymous user vote
            $stmt = $conn->prepare("SELECT id, type FROM votes WHERE anonymous_id = ? AND post_id = ?");
            $stmt->bind_param("si", $anonymous_id, $post_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $existing_vote = $result->fetch_assoc();
                
                // If same vote type, remove the vote (toggle)
                if ($existing_vote['type'] === $vote_type) {
                    $delete_stmt = $conn->prepare("DELETE FROM votes WHERE id = ?");
                    $delete_stmt->bind_param("i", $existing_vote['id']);
                    $delete_stmt->execute();
                } else {
                    // Update existing vote
                    $update_stmt = $conn->prepare("UPDATE votes SET type = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $vote_type, $existing_vote['id']);
                    $update_stmt->execute();
                }
            } else {
                // Insert new vote
                $insert_stmt = $conn->prepare("INSERT INTO votes (anonymous_id, post_id, type) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("sis", $anonymous_id, $post_id, $vote_type);
                $insert_stmt->execute();
            }
        } else {
            throw new Exception("Unable to identify user");
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>