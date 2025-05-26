<?php
session_start();
require_once '../../../../../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor', 'nurse'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$consultation_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$consultation_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing consultation ID.']);
    exit;
}

// Fetch consultation details, ensuring it belongs to the logged-in doctor/nurse
$sql = "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as patient_name, 
        ct.name as consultation_type_name
        FROM consultations c
        JOIN users u ON c.patient_id = u.user_id
        LEFT JOIN consultation_types ct ON c.consultation_type_id = ct.consultation_type_id
        WHERE c.consultation_id = ? AND c.doctor_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $consultation_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($consultation = $result->fetch_assoc()) {
        echo json_encode($consultation);
    } else {
        http_response_code(404); // Not Found or not authorized to view
        echo json_encode(['error' => 'Consultation not found or you are not authorized to view it.']);
    }
    
    $stmt->close();
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
}

$conn->close();
?> 