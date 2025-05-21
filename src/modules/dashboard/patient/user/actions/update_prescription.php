<?php
session_start();
require_once '../../../../../../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student','teacher','staff'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$prescription_id = intval($_POST['prescription_id'] ?? 0);
$status = $_POST['status'] ?? '';
if ($prescription_id <= 0 || !in_array($status, ['Active', 'Completed'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Only allow update if prescription belongs to this user
$sql = "UPDATE prescriptions SET status = ? WHERE prescription_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sii', $status, $prescription_id, $user_id);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Update failed or not allowed']);
} 