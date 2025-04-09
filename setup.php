<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config/db.php';

try {
    echo "<div style='font-family: Arial, sans-serif; margin: 20px;'>";
    
    // First ensure all roles exist
    $roles = [
        ['Admin', 'System administrator with full access'],
        ['Doctor', 'Medical professional who can diagnose and prescribe medication'],
        ['Nurse', 'Medical staff who can provide basic care and assist doctors'],
        ['Teacher', 'School faculty member'],
        ['Student', 'Enrolled student']
    ];

    foreach ($roles as $role) {
        $role_check = $conn->query("SELECT role_id FROM roles WHERE role_name = '{$role[0]}'");
        if ($role_check->num_rows == 0) {
            $conn->query("INSERT INTO roles (role_name, description) VALUES ('{$role[0]}', '{$role[1]}')");
            echo "<p>{$role[0]} role created.</p>";
        }
    }

    // Function to create or update user
    function createOrUpdateUser($conn, $username, $roleId, $schoolId, $email, $firstName, $lastName) {
        $check_sql = "SELECT user_id, is_active FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $password = 'admin123'; // Default password for all users
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        if ($result->num_rows == 0) {
            // Create new user
            $create_sql = "INSERT INTO users (role_id, school_id, username, password, email, first_name, last_name, is_active, first_login) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)";
            $stmt = $conn->prepare($create_sql);
            $stmt->bind_param('issssss', $roleId, $schoolId, $username, $hash, $email, $firstName, $lastName);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>Success: {$username} account has been created.</p>";
                return $stmt->insert_id;
            } else {
                throw new Exception("Failed to create {$username} account: " . $stmt->error);
            }
        } else {
            // Update existing user
            $row = $result->fetch_assoc();
            $update_sql = "UPDATE users SET 
                          is_active = 1,
                          first_login = 0,
                          password = ?
                          WHERE username = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('ss', $hash, $username);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>Success: Existing {$username} account has been updated.</p>";
                return $row['user_id'];
            } else {
                throw new Exception("Failed to update {$username} account: " . $stmt->error);
            }
        }
    }

    // Create or update admin
    $adminId = createOrUpdateUser($conn, 'admin', 1, 'ADMIN001', 'admin@medms.edu', 'System', 'Administrator');

    // Create or update doctor
    $doctorId = createOrUpdateUser($conn, 'doctor', 2, 'DOC001', 'doctor@medms.edu', 'John', 'Smith');

    // Create or update nurse
    $nurseId = createOrUpdateUser($conn, 'nurse', 3, 'NUR001', 'nurse@medms.edu', 'Sarah', 'Johnson');

    // Add or update medical staff records
    function createOrUpdateMedicalStaff($conn, $userId, $specialization, $licenseNumber) {
        $check_sql = "SELECT staff_id FROM medical_staff WHERE user_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $create_sql = "INSERT INTO medical_staff (user_id, specialization, license_number, availability_status) 
                          VALUES (?, ?, ?, 'Available')";
            $stmt = $conn->prepare($create_sql);
            $stmt->bind_param('iss', $userId, $specialization, $licenseNumber);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>Success: Medical staff record created.</p>";
                return $stmt->insert_id;
            }
        }
        return $result->fetch_assoc()['staff_id'];
    }

    // Add medical staff records for doctor and nurse
    if (isset($doctorId)) {
        $doctorStaffId = createOrUpdateMedicalStaff($conn, $doctorId, 'General Medicine', 'MD12345');
    }
    if (isset($nurseId)) {
        $nurseStaffId = createOrUpdateMedicalStaff($conn, $nurseId, 'General Nursing', 'RN12345');
    }

    // Add staff schedules
    function createStaffSchedule($conn, $staffId) {
        // Clear existing schedules
        $conn->query("DELETE FROM medical_staff_schedule WHERE staff_id = $staffId");
        
        // Add weekly schedule (Monday to Friday, 8 AM to 5 PM)
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $start_time = '08:00:00';
        $end_time = '17:00:00';
        
        foreach ($days as $day) {
            $sql = "INSERT INTO medical_staff_schedule (staff_id, day_of_week, start_time, end_time) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('isss', $staffId, $day, $start_time, $end_time);
            $stmt->execute();
        }
        echo "<p style='color: green;'>Success: Schedule created for staff ID: {$staffId}</p>";
    }

    // Create schedules for doctor and nurse
    if (isset($doctorStaffId)) {
        createStaffSchedule($conn, $doctorStaffId);
    }
    if (isset($nurseStaffId)) {
        createStaffSchedule($conn, $nurseStaffId);
    }

    echo "<div style='margin-top: 20px; padding: 10px; background-color: #f0f0f0; border: 1px solid #ddd;'>";
    echo "<strong>Login Credentials for all accounts:</strong><br>";
    echo "Admin - Username: admin, Password: admin123<br>";
    echo "Doctor - Username: doctor, Password: admin123<br>";
    echo "Nurse - Username: nurse, Password: admin123<br>";
    echo "</div>";
    
    echo "</div>";

} catch(Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
    echo "<pre>";
    print_r($conn->error_list);
    echo "</pre>";
}

$conn->close();
?>
