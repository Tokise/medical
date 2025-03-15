<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$tutorial_id = $data['tutorial_id'] ?? null;

if (!$tutorial_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Tutorial ID is required']);
    exit;
}

// Insert or update tutorial completion status
$sql = "
    INSERT INTO user_tutorials (user_id, tutorial_id, completed, completed_at) 
    VALUES (?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $tutorial_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update tutorial status']);
}
