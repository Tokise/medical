<?php
session_start();
require_once '../config/db.php';

// Store user info before destroying session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// First, try to log the action
if ($user_id) {
    $query = "INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) 
              VALUES (?, 'Logout', 'User logged out successfully', ?, ?)";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        // If logging fails, continue with logout anyway
        error_log("Failed to log logout action: " . $e->getMessage());
    }
}

// Then destroy session and redirect
session_destroy();
header("Location: ../auth/login.php");
exit;
?>