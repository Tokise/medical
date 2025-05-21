<?php
session_start();
require_once '../../../../config/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || strtolower($_SESSION['role']) !== 'doctor') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['id'])) {
    $consultation_id = (int)$_GET['id'];
    $user_id = $_SESSION['id'];
    
    $sql = "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as patient_name 
            FROM consultations c 
            JOIN users u ON c.patient_id = u.user_id 
            WHERE c.consultation_id = ? AND c.doctor_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $consultation_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode($row);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Consultation not found']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No consultation ID provided']);
} 