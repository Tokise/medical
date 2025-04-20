<?php
// Initialize the session
session_start();

// Check if the user is not logged in, redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../auth/login.php");
    exit;
}

// Get user role and redirect to appropriate dashboard
$role = isset($_SESSION["role"]) ? strtolower($_SESSION["role"]) : "";

switch($role) {
    case 'admin':
        header("location: admin/index.php");
        break;
    case 'doctor':
        header("location: doctor/index.php");
        break;
    case 'nurse':
        header("location: nurse/index.php");
        break;
    case 'student':
        header("location: patient/student/index.php");
        break;
    case 'teacher':
        header("location: patient/teacher/index.php");
        break;
    case 'staff':
        header("location: patient/staff/index.php");
        break;
    default:
        // If we reach here, something went wrong with the role assignment
        // Display a generic dashboard
        $dashboard_title = "MedMS Dashboard";
        $error_message = "Your role ($role) does not have a specific dashboard. Please contact the administrator.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $dashboard_title; ?> - MedMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../styles/variables.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .error-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .error-title {
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .error-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: #555;
        }
        
        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .button:hover {
            background-color: var(--primary-color-dark);
        }
        
        .button.secondary {
            background-color: #6c757d;
        }
        
        .button.secondary:hover {
            background-color: #5a6268;
        }
        
        .user-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #e9f7fd;
            border-radius: 5px;
            border-left: 5px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <h1 class="error-title">Welcome to MedMS</h1>
            
            <div class="user-info">
                <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION["full_name"] ?? $_SESSION["username"]); ?></strong></p>
                <p>Role: <strong><?php echo htmlspecialchars(ucfirst($_SESSION["role"])); ?></strong></p>
                <p>School ID: <strong><?php echo htmlspecialchars($_SESSION["school_id"]); ?></strong></p>
            </div>
            
            <?php if(isset($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>
            
            <div class="nav-buttons">
                <a href="../../auth/login.php" class="button secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <a href="javascript:history.back()" class="button">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>
        </div>
    </div>
</body>
</html> 