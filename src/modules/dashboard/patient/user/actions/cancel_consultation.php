<?php
session_start();
require_once '../../../../../../config/config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'teacher', 'staff'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$consultation_id = intval($data['consultation_id'] ?? 0);

if ($consultation_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid consultation ID']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Check if consultation exists and belongs to the user
    $check_sql = "SELECT consultation_id FROM consultations 
                  WHERE consultation_id = ? AND patient_id = ? AND confirmation_status = 'pending'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $consultation_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Consultation not found or cannot be cancelled');
    }
    
    // Update consultation status
    $update_sql = "UPDATE consultations 
                   SET confirmation_status = 'cancelled', 
                       status = 'Cancelled',
                       updated_at = CURRENT_TIMESTAMP 
                   WHERE consultation_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $consultation_id);
    $update_stmt->execute();
    
    // Log the transaction
    $log_sql = "INSERT INTO medical_transactions 
                (user_id, transaction_type, reference_id, status, notes) 
                VALUES (?, 'consultation', ?, 'cancelled', 'Consultation cancelled by patient')";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param("ii", $_SESSION['user_id'], $consultation_id);
    $log_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Close statements
$check_stmt->close();
$update_stmt->close();
$log_stmt->close();
?> 