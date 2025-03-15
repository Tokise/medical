<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$role = $_GET['role'] ?? 'all';

// Get tutorials that haven't been completed by the user
$sql = "
    SELECT t.* 
    FROM tutorials t 
    LEFT JOIN user_tutorials ut ON t.id = ut.tutorial_id AND ut.user_id = ?
    WHERE (t.user_role = ? OR t.user_role = 'all')
    AND (ut.completed IS NULL OR ut.completed = 0)
    ORDER BY t.sequence_order ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $role);
$stmt->execute();
$result = $stmt->get_result();

$tutorials = [];
while ($row = $result->fetch_assoc()) {
    $tutorials[] = $row;
}

echo json_encode($tutorials);
