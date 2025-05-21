<?php
session_start();
require_once '../../../../../../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student','teacher','staff'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$prescription_id = intval($_GET['id'] ?? 0);
if ($prescription_id <= 0) {
    echo json_encode(['error' => 'Invalid prescription ID']);
    exit;
}

$sql = "SELECT p.*, u.first_name, u.last_name, d.specialization
        FROM prescriptions p
        JOIN users u ON p.doctor_id = u.user_id
        LEFT JOIN doctors d ON u.user_id = d.user_id
        WHERE p.prescription_id = ? AND p.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $prescription_id, $user_id);
$stmt->execute();
$prescription = $stmt->get_result()->fetch_assoc();
if (!$prescription) {
    echo json_encode(['error' => 'Prescription not found']);
    exit;
}
// Fetch medication details
$sqlMed = "SELECT medication_name, dosage, duration, notes FROM prescription_medications WHERE prescription_id = ? LIMIT 1";
$stmtMed = $conn->prepare($sqlMed);
$stmtMed->bind_param('i', $prescription_id);
$stmtMed->execute();
$med = $stmtMed->get_result()->fetch_assoc();
$prescription['medication'] = $med;
echo json_encode($prescription); 