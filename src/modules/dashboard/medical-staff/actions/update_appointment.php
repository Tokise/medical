<?php
session_start();
require_once '../../../../../config/config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['doctor', 'nurse', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$appointment_id = intval($data['appointment_id'] ?? 0);
$action = $data['action'] ?? '';
$notes = trim($data['notes'] ?? '');

if (!$appointment_id || !in_array($action, ['confirm', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Update appointment status
    $status = $action === 'confirm' ? 'Confirmed' : 'Rejected';
    $sql = "UPDATE appointments 
            SET status = ?, 
                confirmed_by = ?, 
                confirmation_date = NOW(), 
                confirmation_notes = ? 
            WHERE appointment_id = ? AND appointment_type = 'internal'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $status, $_SESSION['id'], $notes, $appointment_id);
    $stmt->execute();

    // Log the transaction
    $sql = "INSERT INTO medical_transactions 
            (user_id, transaction_type, reference_id, status, notes, created_by) 
            VALUES (?, 'appointment', ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissi", $_SESSION['id'], $appointment_id, $status, $notes, $_SESSION['id']);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 