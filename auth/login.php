<?php
session_start();
require_once '../config/db.php';


$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = trim($_POST['school_id']);
    $password = $_POST['password'];
    
    if (empty($school_id) || empty($password)) {
        $error = "Please enter both School ID and password";
    } else {
        // Get user data
        $query = "SELECT u.user_id,  u.school_id, u.password, u.email, u.first_name, u.last_name, 
                         r.role_id, r.role_name 
                  FROM users u 
                  JOIN roles r ON u.role_id = r.role_id 
                  WHERE u.school_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Invalid School ID or password";
        } else {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['school_id'] = $user['school_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['show_tutorial'] = true; // Flag to show tutorial after login
                
                // No need to update last_login as the column doesn't exist
                
                // Log login action
                $logQuery = "INSERT INTO system_logs (user_id, action, description, ip_address, user_agent, created_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
                $logStmt = $conn->prepare($logQuery);
                $action = 'Login';
                $description = 'User logged in successfully';
                $ipAddress = $_SERVER['REMOTE_ADDR'];
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
                $logStmt->bind_param("issss", $user['user_id'], $action, $description, $ipAddress, $userAgent);
                $logStmt->execute();
                
                // Redirect based on role
                switch ($user['role_name']) {
                    case 'Admin':
                        header("Location: ../src/modules/dashboard/admin/index.php");
                        break;
                    case 'Doctor':
                        header("Location: ../src/modules/dashboard/doctor/index.php");
                        break;
                    case 'Nurse':
                        header("Location: ../src/modules/dashboard/nurse/index.php");
                        break;
                    case 'Teacher':
                        header("Location: ../src/modules/dashboard/teacher/index.php");
                        break;
                    case 'Student':
                        header("Location: ../src/modules/dashboard/student/index.php");
                        break;
                    default:
                        header("Location: ../index.php");
                }
                exit;
            } else {
                $error = "Invalid School ID or password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MedMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../styles/variables.css">
    <link rel="stylesheet" href="../styles/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-brand-section">
            <div class="auth-brand-overlay"></div>
            <div class="auth-brand-content">
                <a href="/medical/index.php">
                    <img src="/MedMS/assets/img/logo.png" alt="MedMS Logo">
                    <h1>MedMS</h1>
                </a>
                <p>Medical Management System for Schools</p>
            </div>
        </div>

        <div class="auth-form-section">
            <div class="auth-form-container">
                <div class="auth-card">
                    

                    <?php if (!empty($error)): ?>
                        <div class="error-message">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                        <div class="auth-input-group">
                            <label for="school_id">School ID</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-id-card input-icon"></i>
                                <input type="text" id="school_id" name="school_id" 
                                       placeholder="Enter your school ID"
                                       value="<?= isset($_POST['school_id']) ? htmlspecialchars($_POST['school_id']) : '' ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="auth-input-group">
                            <label for="password">Password</label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <i class="fas fa-eye-slash password-toggle"></i>
                            </div>
                        </div>

                        <button type="submit">Login</button>
                    </form>

                    <div class="auth-divider">
                        <span>Don't have an account?</span>
                    </div>

                    <div class="auth-footer">
                        <a href="signup.php">Create an Account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
