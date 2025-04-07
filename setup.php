<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config/db.php';

try {
    echo "<div style='font-family: Arial, sans-serif; margin: 20px;'>";
    
    // First ensure roles table has admin role
    $role_check = $conn->query("SELECT role_id FROM roles WHERE role_name = 'Admin'");
    if ($role_check->num_rows == 0) {
        $conn->query("INSERT INTO roles (role_name, description) VALUES ('Admin', 'System administrator with full access')");
        echo "<p>Admin role created.</p>";
    }

    // Check if admin exists
    $check_sql = "SELECT user_id, is_active FROM users WHERE username = 'admin'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        // Create new admin account
        $password = 'admin123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $create_sql = "INSERT INTO users (role_id, school_id, username, password, email, first_name, last_name, is_active, first_login, has_seen_demo) 
                       VALUES (1, 'ADMIN001', 'admin', ?, 'admin@medms.edu', 'System', 'Administrator', 1, 0, 1)";
        
        $stmt = $conn->prepare($create_sql);
        $stmt->bind_param('s', $hash);
        
        if ($stmt->execute()) {
            echo "<h3 style='color: green;'>Success: Admin account has been created and activated.</h3>";
        } else {
            throw new Exception("Failed to create admin account: " . $stmt->error);
        }
    } else {
        $row = $result->fetch_assoc();
        
        // Update existing admin account
        $update_sql = "UPDATE users SET 
                      is_active = 1,
                      first_login = 0,
                      has_seen_demo = 1,
                      password = ?
                      WHERE username = 'admin'";
        
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('s', $hash);
        
        if ($stmt->execute()) {
            echo "<h3 style='color: green;'>Success: Existing admin account has been activated and password reset.</h3>";
        } else {
            throw new Exception("Failed to update admin account: " . $stmt->error);
        }
    }
    
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #f0f0f0; border: 1px solid #ddd;'>";
    echo "<strong>Admin Login Credentials:</strong><br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "</div>";
    
    echo "</div>";

} catch(Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
    echo "<pre>"; // Add debug information
    print_r($conn->error_list);
    echo "</pre>";
}

$conn->close();
?>
