<?php
// Include database configuration
require_once "config/config.php";

// Set page title and initialize variables
$title = "MedMS System Setup";
$message = "";
$success = false;
$created_accounts = [];
$setup_log = [];

// Automatically create default accounts every time this page is accessed
// Define default accounts
$default_accounts = [
    [
        'role' => 'Admin',
        'username' => 'admin',
        'password' => 'admin123',
        'email' => 'admin@medms.edu',
        'first_name' => 'System',
        'last_name' => 'Administrator',
        'is_active' => true
    ],
    [
        'role' => 'Doctor',
        'username' => 'doctor',
        'password' => 'doctor123',
        'email' => 'doctor@medms.edu',
        'first_name' => 'John',
        'last_name' => 'Smith',
        'specialization' => 'General Medicine',
        'license_number' => 'MD12345',
        'is_active' => true
    ],
    [
        'role' => 'Nurse',
        'username' => 'nurse',
        'password' => 'nurse123',
        'email' => 'nurse@medms.edu',
        'first_name' => 'Sarah',
        'last_name' => 'Johnson',
        'specialization' => 'General Nursing',
        'license_number' => 'RN12345',
        'is_active' => true
    ],
    [
        'role' => 'Teacher',
        'username' => 'teacher',
        'password' => 'teacher123',
        'email' => 'teacher@medms.edu',
        'first_name' => 'Robert',
        'last_name' => 'Williams',
        'is_active' => true
    ],
    [
        'role' => 'Student',
        'username' => 'student',
        'password' => 'student123',
        'email' => 'student@medms.edu',
        'first_name' => 'Emily',
        'last_name' => 'Davis',
        'is_active' => true
    ],
    [
        'role' => 'Staff',
        'username' => 'staff',
        'password' => 'staff123',
        'email' => 'staff@medms.edu',
        'first_name' => 'Michael',
        'last_name' => 'Brown',
        'is_active' => true
    ]
];

// Create each default account
foreach ($default_accounts as $account) {
    // Check if account already exists
    $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $user_exists = false;
    $user_id = null;
    
    if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($check_stmt, "ss", $account['username'], $account['email']);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $user_exists = true;
            mysqli_stmt_bind_result($check_stmt, $user_id);
            mysqli_stmt_fetch($check_stmt);
            $setup_log[] = "Account '{$account['username']}' already exists. Account data updated.";
        }
        
        mysqli_stmt_close($check_stmt);
    }
    
    // Get role ID
    $role_id = null;
    $role_query = "SELECT role_id FROM roles WHERE role_name = ?";
    
    if ($role_stmt = mysqli_prepare($conn, $role_query)) {
        mysqli_stmt_bind_param($role_stmt, "s", $account['role']);
        mysqli_stmt_execute($role_stmt);
        mysqli_stmt_bind_result($role_stmt, $role_id);
        mysqli_stmt_fetch($role_stmt);
        mysqli_stmt_close($role_stmt);
    }
    
    if (!$role_id) {
        $setup_log[] = "Role '{$account['role']}' not found. Account not created.";
        continue;
    }
    
    // Generate school ID
    $prefix = substr($account['role'], 0, 3);
    $school_id = strtoupper($prefix) . rand(100000, 999999);
    
    if ($user_exists) {
        // Update existing user
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, is_active = 1 WHERE user_id = ?";
        
        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
            mysqli_stmt_bind_param(
                $update_stmt, 
                "ssi", 
                $account['first_name'],
                $account['last_name'],
                $user_id
            );
            
            if (mysqli_stmt_execute($update_stmt)) {
                $setup_log[] = "Account '{$account['username']}' updated successfully.";
                $created_accounts[] = [
                    'username' => $account['username'],
                    'password' => $account['password'],
                    'email' => $account['email'],
                    'role' => $account['role'],
                    'name' => $account['first_name'] . ' ' . $account['last_name'],
                    'school_id' => $school_id
                ];
            }
            
            mysqli_stmt_close($update_stmt);
        }
    } else {
        // Create new user
        $insert_sql = "INSERT INTO users (role_id, school_id, username, password, email, first_name, last_name, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
            $hashed_password = password_hash($account['password'], PASSWORD_DEFAULT);
            $is_active = $account['is_active'] ? 1 : 0;
            
            mysqli_stmt_bind_param(
                $insert_stmt, 
                "issssssi", 
                $role_id,
                $school_id,
                $account['username'],
                $hashed_password,
                $account['email'],
                $account['first_name'],
                $account['last_name'],
                $is_active
            );
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $user_id = mysqli_insert_id($conn);
                
                // Create role-specific records
                switch (strtolower($account['role'])) {
                    case 'doctor':
                        // Create doctor record
                        $license = isset($account['license_number']) ? $account['license_number'] : "DOC_" . rand(1000, 9999);
                        $specialization = isset($account['specialization']) ? $account['specialization'] : "General Medicine";
                        
                        $doctor_sql = "INSERT INTO doctors (user_id, license_number, specialization, availability_status) VALUES (?, ?, ?, 'Available')";
                        if ($doctor_stmt = mysqli_prepare($conn, $doctor_sql)) {
                            mysqli_stmt_bind_param($doctor_stmt, "iss", $user_id, $license, $specialization);
                            mysqli_stmt_execute($doctor_stmt);
                            
                            // Add default schedules for doctors
                            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                            foreach ($weekdays as $day) {
                                $schedule_sql = "INSERT INTO medical_schedule (user_id, day_of_week, start_time, end_time) VALUES (?, ?, '08:00:00', '17:00:00')";
                                $schedule_stmt = mysqli_prepare($conn, $schedule_sql);
                                mysqli_stmt_bind_param($schedule_stmt, "is", $user_id, $day);
                                mysqli_stmt_execute($schedule_stmt);
                                mysqli_stmt_close($schedule_stmt);
                            }
                            
                            mysqli_stmt_close($doctor_stmt);
                        }
                        break;
                        
                    case 'nurse':
                        // Create nurse record
                        $license = isset($account['license_number']) ? $account['license_number'] : "NRS_" . rand(1000, 9999);
                        $specialization = isset($account['specialization']) ? $account['specialization'] : "General Nursing";
                        
                        $nurse_sql = "INSERT INTO nurses (user_id, license_number, specialization, availability_status) VALUES (?, ?, ?, 'Available')";
                        if ($nurse_stmt = mysqli_prepare($conn, $nurse_sql)) {
                            mysqli_stmt_bind_param($nurse_stmt, "iss", $user_id, $license, $specialization);
                            mysqli_stmt_execute($nurse_stmt);
                            
                            // Add default schedules for nurses
                            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                            foreach ($weekdays as $day) {
                                $schedule_sql = "INSERT INTO medical_schedule (user_id, day_of_week, start_time, end_time) VALUES (?, ?, '08:00:00', '17:00:00')";
                                $schedule_stmt = mysqli_prepare($conn, $schedule_sql);
                                mysqli_stmt_bind_param($schedule_stmt, "is", $user_id, $day);
                                mysqli_stmt_execute($schedule_stmt);
                                mysqli_stmt_close($schedule_stmt);
                            }
                            
                            mysqli_stmt_close($nurse_stmt);
                        }
                        break;
                        
                    case 'student':
                        $student_sql = "INSERT INTO students (user_id) VALUES (?)";
                        if ($student_stmt = mysqli_prepare($conn, $student_sql)) {
                            mysqli_stmt_bind_param($student_stmt, "i", $user_id);
                            mysqli_stmt_execute($student_stmt);
                            mysqli_stmt_close($student_stmt);
                        }
                        break;
                        
                    case 'teacher':
                        $teacher_sql = "INSERT INTO teachers (user_id, date_of_birth, emergency_contact_name, emergency_contact_number, emergency_contact_relationship) VALUES (?, NOW(), 'Not Set', 'Not Set', 'Not Set')";
                        if ($teacher_stmt = mysqli_prepare($conn, $teacher_sql)) {
                            mysqli_stmt_bind_param($teacher_stmt, "i", $user_id);
                            mysqli_stmt_execute($teacher_stmt);
                            mysqli_stmt_close($teacher_stmt);
                        }
                        break;
                        
                    case 'staff':
                        $staff_sql = "INSERT INTO staff (user_id) VALUES (?)";
                        if ($staff_stmt = mysqli_prepare($conn, $staff_sql)) {
                            mysqli_stmt_bind_param($staff_stmt, "i", $user_id);
                            mysqli_stmt_execute($staff_stmt);
                            mysqli_stmt_close($staff_stmt);
                        }
                        break;
                }
                
                $setup_log[] = "Account '{$account['username']}' created successfully with School ID: {$school_id}";
                $created_accounts[] = [
                    'username' => $account['username'],
                    'password' => $account['password'],
                    'email' => $account['email'],
                    'role' => $account['role'],
                    'name' => $account['first_name'] . ' ' . $account['last_name'],
                    'school_id' => $school_id
                ];
            } else {
                $setup_log[] = "Error creating account '{$account['username']}': " . mysqli_error($conn);
            }
            
            mysqli_stmt_close($insert_stmt);
        }
    }
}

if (!empty($created_accounts)) {
    $message = "Setup completed successfully. Default accounts have been created.";
    $success = true;
}

// Get all users from the database
$all_users = [];
$all_users_sql = "
    SELECT 
        u.user_id, 
        u.username, 
        u.school_id,
        u.email, 
        u.first_name, 
        u.last_name, 
        u.is_active,
        r.role_id,
        r.role_name
    FROM 
        users u
    JOIN 
        roles r ON u.role_id = r.role_id
    ORDER BY 
        r.role_name ASC, 
        u.last_name ASC, 
        u.first_name ASC
";

$result = mysqli_query($conn, $all_users_sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $all_users[] = $row;
    }
}

// Count active and inactive users
$active_count = 0;
$inactive_count = 0;
foreach ($all_users as $user) {
    if ($user['is_active']) {
        $active_count++;
    } else {
        $inactive_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - MedMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="src/styles/variables.css">
    <link rel="stylesheet" href="src/styles/auth.css">
    <style>
        .setup-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .account-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        
        .account-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .account-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--primary-color);
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .account-detail {
            margin-bottom: 5px;
        }
        
        .account-detail strong {
            display: inline-block;
            width: 100px;
        }
        
        .setup-log {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .setup-log-item {
            margin-bottom: 5px;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .setup-log-success {
            color: #155724;
        }
        
        .setup-log-error {
            color: #721c24;
        }
        
        .login-button {
            display: block;
            width: 100%;
            max-width: 300px;
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 5px;
            text-decoration: none;
            margin: 30px auto;
            font-size: 18px;
            transition: background-color 0.3s;
            font-weight: bold;
        }
        
        .login-button:hover {
            background-color: var(--primary-color-dark);
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <h1><?php echo $title; ?></h1>
            <p>Medical Management System for Schools</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo count($all_users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $active_count; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $inactive_count; ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
        </div>
        
        <div class="message success">
            <strong>Setup complete!</strong> The following accounts are available for use:
        </div>
        
        <h3>System Accounts</h3>
        <div class="account-grid">
            <?php foreach ($created_accounts as $account): ?>
                <div class="account-card">
                    <div class="account-title"><?php echo htmlspecialchars($account['role']); ?>: <?php echo htmlspecialchars($account['name']); ?></div>
                    <div class="account-detail"><strong>Username:</strong> <?php echo htmlspecialchars($account['username']); ?></div>
                    <div class="account-detail"><strong>Password:</strong> <?php echo htmlspecialchars($account['password']); ?></div>
                    <div class="account-detail"><strong>School ID:</strong> <?php echo htmlspecialchars($account['school_id']); ?></div>
                    <div class="account-detail"><strong>Email:</strong> <?php echo htmlspecialchars($account['email']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($setup_log)): ?>
            <h3>Setup Log</h3>
            <div class="setup-log">
                <?php foreach ($setup_log as $log): ?>
                    <div class="setup-log-item <?php echo strpos($log, 'successfully') !== false ? 'setup-log-success' : (strpos($log, 'Error') !== false ? 'setup-log-error' : ''); ?>">
                        <?php echo htmlspecialchars($log); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <a href="src/auth/login.php" class="login-button">
            <i class="fas fa-sign-in-alt"></i> Go to Login Page
        </a>
    </div>
</body>
</html>
