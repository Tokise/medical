<?php
session_start();
require_once '../config/db.php';
require_once '../vendor/autoload.php';

header('Content-Type: application/json');

// Google API Client configuration
$client = new Google_Client();
$client->setClientId('851838852969-fetbeefl2lqobjvh0dlnq10vi17t1g4t.apps.googleusercontent.com');

try {
    if (!isset($_POST['credential'])) {
        throw new Exception("No credential provided");
    }

    $payload = $client->verifyIdToken($_POST['credential']);

    if ($payload) {
        $email = $payload['email'];
        $google_id = $payload['sub'];
        $name = $payload['name'];
        $picture = $payload['picture'] ?? null;
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Get default role (student)
            $stmt = $conn->prepare("SELECT id FROM roles WHERE name = 'student' LIMIT 1");
            $stmt->execute();
            $role = $stmt->get_result()->fetch_assoc();
            
            if (!$role) {
                throw new Exception("Default role not found");
            }
            
            // Split name into first_name and last_name
            $name_parts = explode(' ', $name);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
            
            // Create new user
            $stmt = $conn->prepare("INSERT INTO users (email, google_id, first_name, last_name, role_id, profile_picture) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $email, $google_id, $first_name, $last_name, $role['id'], $picture);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create user: " . $stmt->error);
            }
            $user_id = $conn->insert_id;
        } else {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            
            // Update user's Google information
            $stmt = $conn->prepare("UPDATE users SET google_id = ?, profile_picture = ? WHERE id = ?");
            $stmt->bind_param("ssi", $google_id, $picture, $user_id);
            $stmt->execute();
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['auth_type'] = 'google';
        
        echo json_encode(['status' => 'success']);
        exit;
    }
    
    throw new Exception("Invalid token");
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>
<script>
const xhr = new XMLHttpRequest();
xhr.onload = function() {
    if (xhr.responseText === 'success') {
        window.location.href = '../src/dashboard/index.php';
    }
};
</script>
