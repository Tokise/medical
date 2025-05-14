<<<<<<< HEAD
<<<<<<< HEAD
<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../../config/config.php";
 
// Define variables and initialize with empty values
$username = $password = $confirm_password = $email = $fullname = $role = $school_id = "";
$username_err = $password_err = $confirm_password_err = $email_err = $fullname_err = $role_err = $school_id_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate fullname
    if(empty(trim($_POST["fullname"]))){
        $fullname_err = "Please enter the full name.";     
    } else{
        $fullname = trim($_POST["fullname"]);
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else{
        // Prepare a select statement
        $sql = "SELECT user_id FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already taken.";
                } elseif(!filter_var($param_email, FILTER_VALIDATE_EMAIL)) {
                    $email_err = "Please enter a valid email address.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT user_id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate role
    if(empty(trim($_POST["role"]))){
        $role_err = "Please select a role.";
    } else {
        $role = trim($_POST["role"]);
    }
    
    // Validate/generate school ID
    if(empty(trim($_POST["school_id"]))){
        // Auto-generate school ID based on role
        $prefix = "";
        switch($role) {
            case "student":
                $prefix = "STU";
                break;
            case "teacher":
                $prefix = "TCH";
                break;
            case "doctor":
                $prefix = "DOC";
                break;
            case "nurse":
                $prefix = "NRS";
                break;
            case "admin":
                $prefix = "ADM";
                break;
            default:
                $prefix = "USR";
        }
        $school_id = $prefix . rand(100000, 999999);
    } else {
        $school_id = trim($_POST["school_id"]);
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($fullname_err) && empty($role_err)){
        
        // Get role ID
        $role_query = "SELECT role_id FROM roles WHERE role_name = ?";
        $role_stmt = mysqli_prepare($conn, $role_query);
        mysqli_stmt_bind_param($role_stmt, "s", $role);
        mysqli_stmt_execute($role_stmt);
        mysqli_stmt_bind_result($role_stmt, $role_id);
        
        if(!mysqli_stmt_fetch($role_stmt)){
            $role_err = "Invalid role selected.";
            mysqli_stmt_close($role_stmt);
        } else {
            mysqli_stmt_close($role_stmt);
            
            // Parse fullname into first and last name
            $name_parts = explode(" ", $fullname, 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
            
            // Prepare an insert statement
            $sql = "INSERT INTO users (role_id, school_id, username, password, email, first_name, last_name, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
             
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "issssss", $role_id, $school_id, $param_username, $param_password, $param_email, $first_name, $last_name);
                
                // Set parameters
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                $param_email = $email;
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Get the user_id
                    $user_id = mysqli_insert_id($conn);
                    
                    // Insert additional data based on role
                    switch($role) {
                        case "student":
                            $additional_sql = "INSERT INTO students (user_id) VALUES (?)";
                            break;
                        case "teacher":
                            $additional_sql = "INSERT INTO teachers (user_id, date_of_birth, emergency_contact_name, emergency_contact_number, emergency_contact_relationship) VALUES (?, NOW(), 'Not Set', 'Not Set', 'Not Set')";
                            break;
                        case "doctor":
                        case "nurse":
                            $additional_sql = "INSERT INTO medical_staff (user_id, license_number) VALUES (?, 'Not Set')";
                            break;
                        default:
                            $additional_sql = "";
                    }
                    
                    if(!empty($additional_sql)) {
                        $additional_stmt = mysqli_prepare($conn, $additional_sql);
                        mysqli_stmt_bind_param($additional_stmt, "i", $user_id);
                        mysqli_stmt_execute($additional_stmt);
                        mysqli_stmt_close($additional_stmt);
                    }
                    
                    // Show success message
                    $success_message = "Account created successfully!";
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Close connection
    mysqli_close($conn);
}

// Get all roles for the role select dropdown
$roles = [];
$roles_query = "SELECT role_name FROM roles";
$roles_result = mysqli_query($conn, $roles_query);
if ($roles_result) {
    while ($row = mysqli_fetch_assoc($roles_result)) {
        $roles[] = $row['role_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - MedMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/variables.css">
    <link rel="stylesheet" href="../styles/auth.css">
</head>
<body>
    <div class="signup-container">
        <div class="signup-left">
            <img src="../../assets/img/logo.png" alt="MedMS Logo">
            <div class="signup-message">
                <h2>Admin User Creation</h2>
                <p>Create new user accounts in the Medical Management System for Schools.</p>
            </div>
        </div>
        <div class="signup-right">
            <div class="signup-header">
                <h1>Create New User</h1>
                <p>Administrator access only</p>
            </div>
            
            <?php if(isset($success_message)): ?>
                <div class="alert success-alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" class="form-control <?php echo (!empty($fullname_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $fullname; ?>">
                        <?php if(!empty($fullname_err)): ?>
                            <span class="invalid-feedback"><?php echo $fullname_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <?php if(!empty($email_err)): ?>
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <?php if(!empty($username_err)): ?>
                            <span class="invalid-feedback"><?php echo $username_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Role</option>
                            <?php foreach($roles as $role_option): ?>
                                <option value="<?php echo strtolower($role_option); ?>" <?php echo ($role === strtolower($role_option)) ? 'selected' : ''; ?>>
                                    <?php echo $role_option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(!empty($role_err)): ?>
                            <span class="invalid-feedback"><?php echo $role_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>School ID (Optional - Auto-generated if left blank)</label>
                        <input type="text" name="school_id" class="form-control <?php echo (!empty($school_id_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $school_id; ?>">
                        <?php if(!empty($school_id_err)): ?>
                            <span class="invalid-feedback"><?php echo $school_id_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                        <?php if(!empty($password_err)): ?>
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                        <?php if(!empty($confirm_password_err)): ?>
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="signup-btn">Create User</button>
                </div>
            </form>
            
            <div class="login-link">
                <p><a href="../modules/dashboard/admin/index.php">Back to Dashboard</a></p>
            </div>
        </div>
    </div>
</body>
</html>
=======
=======
>>>>>>> 6555137 (Added my changes)
<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../../config/config.php";
 
// Define variables and initialize with empty values
$username = $password = $confirm_password = $email = $fullname = $role = $school_id = "";
$username_err = $password_err = $confirm_password_err = $email_err = $fullname_err = $role_err = $school_id_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate fullname
    if(empty(trim($_POST["fullname"]))){
        $fullname_err = "Please enter the full name.";     
    } else{
        $fullname = trim($_POST["fullname"]);
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else{
        // Prepare a select statement
        $sql = "SELECT user_id FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already taken.";
                } elseif(!filter_var($param_email, FILTER_VALIDATE_EMAIL)) {
                    $email_err = "Please enter a valid email address.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT user_id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate role
    if(empty(trim($_POST["role"]))){
        $role_err = "Please select a role.";
    } else {
        $role = trim($_POST["role"]);
    }
    
    // Validate/generate school ID
    if(empty(trim($_POST["school_id"]))){
        // Auto-generate school ID based on role
        $prefix = "";
        switch($role) {
            case "student":
                $prefix = "STU";
                break;
            case "teacher":
                $prefix = "TCH";
                break;
            case "doctor":
                $prefix = "DOC";
                break;
            case "nurse":
                $prefix = "NRS";
                break;
            case "admin":
                $prefix = "ADM";
                break;
            default:
                $prefix = "USR";
        }
        $school_id = $prefix . rand(100000, 999999);
    } else {
        $school_id = trim($_POST["school_id"]);
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($fullname_err) && empty($role_err)){
        
        // Get role ID
        $role_query = "SELECT role_id FROM roles WHERE role_name = ?";
        $role_stmt = mysqli_prepare($conn, $role_query);
        mysqli_stmt_bind_param($role_stmt, "s", $role);
        mysqli_stmt_execute($role_stmt);
        mysqli_stmt_bind_result($role_stmt, $role_id);
        
        if(!mysqli_stmt_fetch($role_stmt)){
            $role_err = "Invalid role selected.";
            mysqli_stmt_close($role_stmt);
        } else {
            mysqli_stmt_close($role_stmt);
            
            // Parse fullname into first and last name
            $name_parts = explode(" ", $fullname, 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
            
            // Prepare an insert statement
            $sql = "INSERT INTO users (role_id, school_id, username, password, email, first_name, last_name, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
             
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "issssss", $role_id, $school_id, $param_username, $param_password, $param_email, $first_name, $last_name);
                
                // Set parameters
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                $param_email = $email;
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Get the user_id
                    $user_id = mysqli_insert_id($conn);
                    
                    // Insert additional data based on role
                    switch($role) {
                        case "student":
                            $additional_sql = "INSERT INTO students (user_id) VALUES (?)";
                            break;
                        case "teacher":
                            $additional_sql = "INSERT INTO teachers (user_id, date_of_birth, emergency_contact_name, emergency_contact_number, emergency_contact_relationship) VALUES (?, NOW(), 'Not Set', 'Not Set', 'Not Set')";
                            break;
                        case "doctor":
                        case "nurse":
                            $additional_sql = "INSERT INTO medical_staff (user_id, license_number) VALUES (?, 'Not Set')";
                            break;
                        default:
                            $additional_sql = "";
                    }
                    
                    if(!empty($additional_sql)) {
                        $additional_stmt = mysqli_prepare($conn, $additional_sql);
                        mysqli_stmt_bind_param($additional_stmt, "i", $user_id);
                        mysqli_stmt_execute($additional_stmt);
                        mysqli_stmt_close($additional_stmt);
                    }
                    
                    // Show success message
                    $success_message = "Account created successfully!";
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Close connection
    mysqli_close($conn);
}

// Get all roles for the role select dropdown
$roles = [];
$roles_query = "SELECT role_name FROM roles";
$roles_result = mysqli_query($conn, $roles_query);
if ($roles_result) {
    while ($row = mysqli_fetch_assoc($roles_result)) {
        $roles[] = $row['role_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - MedMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/variables.css">
    <link rel="stylesheet" href="../styles/auth.css">
</head>
<body>
    <div class="signup-container">
        <div class="signup-left">
            <img src="../../assets/img/logo.png" alt="MedMS Logo">
            <div class="signup-message">
                <h2>Admin User Creation</h2>
                <p>Create new user accounts in the Medical Management System for Schools.</p>
            </div>
        </div>
        <div class="signup-right">
            <div class="signup-header">
                <h1>Create New User</h1>
                <p>Administrator access only</p>
            </div>
            
            <?php if(isset($success_message)): ?>
                <div class="alert success-alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" class="form-control <?php echo (!empty($fullname_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $fullname; ?>">
                        <?php if(!empty($fullname_err)): ?>
                            <span class="invalid-feedback"><?php echo $fullname_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <?php if(!empty($email_err)): ?>
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <?php if(!empty($username_err)): ?>
                            <span class="invalid-feedback"><?php echo $username_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Role</option>
                            <?php foreach($roles as $role_option): ?>
                                <option value="<?php echo strtolower($role_option); ?>" <?php echo ($role === strtolower($role_option)) ? 'selected' : ''; ?>>
                                    <?php echo $role_option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(!empty($role_err)): ?>
                            <span class="invalid-feedback"><?php echo $role_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>School ID (Optional - Auto-generated if left blank)</label>
                        <input type="text" name="school_id" class="form-control <?php echo (!empty($school_id_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $school_id; ?>">
                        <?php if(!empty($school_id_err)): ?>
                            <span class="invalid-feedback"><?php echo $school_id_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                        <?php if(!empty($password_err)): ?>
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                        <?php if(!empty($confirm_password_err)): ?>
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="signup-btn">Create User</button>
                </div>
            </form>
            
            <div class="login-link">
                <p><a href="../modules/dashboard/admin/index.php">Back to Dashboard</a></p>
            </div>
        </div>
    </div>
</body>
</html>
<<<<<<< HEAD
>>>>>>> 6555137 (Added my changes)
=======
>>>>>>> 6555137 (Added my changes)
