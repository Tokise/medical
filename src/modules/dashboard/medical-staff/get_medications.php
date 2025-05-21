<?php
session_start();
require_once '../../../../config/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || strtolower($_SESSION['role']) !== 'doctor') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql = "SELECT medication_id as id, name FROM medications WHERE name LIKE ? LIMIT 10";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$search%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $medications = [];
    while ($row = $result->fetch_assoc()) {
        $medications[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($medications);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No search term provided']);
} 