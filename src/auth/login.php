<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    // Redirect based on saved role
    $role = strtolower($_SESSION["role"]);
    switch($role) {
        case 'admin':
            header("location: ../modules/dashboard/admin/index.php");
            break;
        case 'doctor':
            header("location: ../modules/dashboard/doctor/index.php");
            break;
        case 'nurse':
            header("location: ../modules/dashboard/nurse/index.php");
            break;
        case 'student':
            header("location: ../modules/dashboard/patient/student/index.php");
            break;
        case 'teacher':
            header("location: ../modules/dashboard/patient/teacher/index.php");
            break;
        case 'staff':
            header("location: ../modules/dashboard/patient/staff/index.php");
            break;
        default:
            header("location: ../modules/dashboard/index.php");
    }
    exit;
}

// Include config file
require_once "../../config/config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement - check both username and school_id fields
        $sql = "SELECT user_id, username, password, role_id, school_id FROM users WHERE username = ? OR school_id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role_id, $school_id);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Get role name from role_id
                            $role_query = "SELECT role_name FROM roles WHERE role_id = ?";
                            $role_stmt = mysqli_prepare($conn, $role_query);
                            mysqli_stmt_bind_param($role_stmt, "i", $role_id);
                            mysqli_stmt_execute($role_stmt);
                            mysqli_stmt_bind_result($role_stmt, $role_name);
                            mysqli_stmt_fetch($role_stmt);
                            mysqli_stmt_close($role_stmt);
                            
                            // Get user details
                            $user_query = "SELECT first_name, last_name FROM users WHERE user_id = ?";
                            $user_stmt = mysqli_prepare($conn, $user_query);
                            mysqli_stmt_bind_param($user_stmt, "i", $id);
                            mysqli_stmt_execute($user_stmt);
                            mysqli_stmt_bind_result($user_stmt, $first_name, $last_name);
                            mysqli_stmt_fetch($user_stmt);
                            mysqli_stmt_close($user_stmt);
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["school_id"] = $school_id;
                            $_SESSION["role"] = strtolower($role_name);
                            $_SESSION["first_name"] = $first_name;
                            $_SESSION["last_name"] = $last_name;
                            $_SESSION["full_name"] = $first_name . " " . $last_name;
                            
                            // Redirect user to appropriate dashboard based on role
                            switch(strtolower($role_name)) {
                                case 'admin':
                                    header("location: ../modules/dashboard/admin/index.php");
                                    break;
                                case 'doctor':
                                    header("location: ../modules/dashboard/doctor/index.php");
                                    break;
                                case 'nurse':
                                    header("location: ../modules/dashboard/nurse/index.php");
                                    break;
                                case 'student':
                                    header("location: ../modules/dashboard/patient/student/index.php");
                                    break;
                                case 'teacher':
                                    header("location: ../modules/dashboard/patient/teacher/index.php");
                                    break;
                                case 'staff':
                                    header("location: ../modules/dashboard/patient/staff/index.php");
                                    break;
                                default:
                                    header("location: ../modules/dashboard/index.php");
                            }
                            exit;
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else{
                $login_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MedMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/variables.css">
    <link rel="stylesheet" href="../styles/auth.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="brand-container">
                <img src="../../assets/img/logo.png" alt="MedMS Logo" class="logo">
                <h1 class="brand-name">MedMS</h1>
            </div>
            <div class="login-message">
                <h2>Welcome to Medical Management System</h2>
                <p>Your comprehensive healthcare solution for educational institutions. Manage appointments, patient records, and medical supplies with ease.</p>
            </div>
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Schedule Appointments</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-user-md"></i>
                    <span>Medical Staff Management</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-heartbeat"></i>
                    <span>Health Monitoring</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-pills"></i>
                    <span>Inventory Management</span>
                </div>
            </div>
        </div>
        <div class="login-right">
            <div class="login-header">
                <h1>Login</h1>
                <p>Access your account to continue</p>
            </div>
            
            <?php 
            if(!empty($login_err)){
                echo '<div class="alert"><i class="fas fa-exclamation-circle"></i> ' . $login_err . '</div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username or School ID</label>
                    <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" placeholder="Enter your Username or School ID">
                    <?php if(!empty($username_err)): ?>
                        <span class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> <?php echo $username_err; ?></span>
                    <?php endif; ?>
                </div>    
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="password-input-container">
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="Enter your password">
                        <span class="password-toggle" onclick="togglePasswordVisibility()">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <?php if(!empty($password_err)): ?>
                        <span class="invalid-feedback"><i class="fas fa-exclamation-circle"></i> <?php echo $password_err; ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <button type="submit" class="login-btn">Login</button>
                </div>
            </form>
            
            <div class="login-footer">
                <p>Need assistance? <a href="mailto:support@medms.edu">Contact IT Support</a></p>
                <p class="copyright">&copy; <?php echo date('Y'); ?> MedMS. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
