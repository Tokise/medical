<?php
session_start();
require_once '../config/db.php';




$error = '';
$success = '';

// Process signup form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $school_id = trim($_POST['school_id']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    
    // Basic validation
    if (empty($school_id) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
        $error = "All fields are required";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if school_id already exists
        $checkSchoolId = "SELECT user_id FROM users WHERE school_id = ?";
        $stmt = $conn->prepare($checkSchoolId);
        $stmt->bind_param("s", $school_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "School ID already exists";
        } else {
            // Get student role ID
            $roleQuery = "SELECT role_id FROM roles WHERE role_name = 'Student'";
            $roleResult = $conn->query($roleQuery);
            $role = $roleResult->fetch_assoc();
            $role_id = $role['role_id'];
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert into users table
                $insertQuery = "INSERT INTO users (role_id, school_id, username, email, password, first_name, last_name, first_login, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, TRUE, NOW())";
                $stmt = $conn->prepare($insertQuery);
                $username = strtolower($firstName . '.' . $lastName); // Create username
                $stmt->bind_param("issssss", $role_id, $school_id, $username, $email, $hashedPassword, $firstName, $lastName);
                
                if ($stmt->execute()) {
                    $userId = $stmt->insert_id;
                    
                    // Create student profile with default values
                    $profileQuery = "INSERT INTO students (user_id, date_of_birth, emergency_contact_name, emergency_contact_number, emergency_contact_relationship) 
                                    VALUES (?, CURRENT_DATE, 'To be updated', 'To be updated', 'To be updated')";
                    $profileStmt = $conn->prepare($profileQuery);
                    $profileStmt->bind_param("i", $userId);
                    $profileStmt->execute();
                    
                    $conn->commit();
                    $success = "Account created successfully! You can now login.";
                    header("refresh:1;url=login.php");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Registration failed: " . $e->getMessage();
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
    <title>Sign Up - MedMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../styles/variables.css">
    <link rel="stylesheet" href="../styles/auth.css">
</head>
<body>
    <div class="auth-container signup-layout">
        <div class="auth-brand-overlay"></div>
        <div class="auth-content-wrapper">
            <div class="auth-brand-content">
                <img src="/MedMS/assets/img/logo.png" alt="MedMS Logo">
                <h1>MedMS</h1>
                <p>Medical Management System for Schools</p>
            </div>

            <div class="auth-form-section">
                <div class="auth-form-container">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h2>Student Registration</h2>
                            <p>Create your account to access health services</p>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                            <div class="error-message">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="success-message">
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                            <div class="auth-input-group">
                                <label for="school_id">Student ID</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-id-card input-icon"></i>
                                    <input type="text" name="school_id" id="school_id" placeholder="Enter your student ID" required>
                                </div>
                            </div>
                            
                            <div class="auth-input-group">
                                <label for="first_name">First Name</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" name="first_name" id="first_name" placeholder="Enter your first name" required>
                                </div>
                            </div>
                            
                            <div class="auth-input-group">
                                <label for="last_name">Last Name</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" name="last_name" id="last_name" placeholder="Enter your last name" required>
                                </div>
                            </div>
                            
                            <div class="auth-input-group">
                                <label for="email">Email Address</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" name="email" id="email" placeholder="Enter your email address" required>
                                </div>
                            </div>
                            
                            <div class="auth-input-group">
                                <label for="password">Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" name="password" id="password" placeholder="Create a password" required>
                                    <i class="fas fa-eye-slash password-toggle"></i>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="password-strength-meter"></div>
                                </div>
                            </div>

                            <button type="submit">Create Account</button>
                        </form>

                        <div class="auth-footer">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.password-toggle');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const passwordField = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                } else {
                    passwordField.type = 'password';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                }
            });
        });
        
        const passwordField = document.getElementById('password');
        const strengthMeter = document.querySelector('.password-strength-meter');
        
        passwordField.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^A-Za-z0-9]/)) strength += 1;
            
            strengthMeter.className = 'password-strength-meter';
            if (password.length === 0) {
                strengthMeter.style.width = '0';
            } else {
                switch (strength) {
                    case 1:
                        strengthMeter.classList.add('weak');
                        break;
                    case 2:
                        strengthMeter.classList.add('medium');
                        break;
                    case 3:
                        strengthMeter.classList.add('strong');
                        break;
                    case 4:
                        strengthMeter.classList.add('very-strong');
                        break;
                }
            }
        });
        
        const confirmPasswordField = document.getElementById('confirm_password');
        
        confirmPasswordField.addEventListener('input', function() {
            if (this.value !== passwordField.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    });
    </script>
</body>
</html>
