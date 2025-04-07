<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$completed = isset($data['completed']) ? $data['completed'] : false;

if ($completed) {
    // Update user's demo state in database
    $userId = $_SESSION['user_id'];
    $query = "UPDATE users SET demo_completed = 1, demo_completed_at = NOW() WHERE user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update demo state']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
