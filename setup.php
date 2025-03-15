<?php
require_once('config/db.php');
session_start();

$setup_completed = false;
$error_message = '';
$success_message = '';

// Check if admin password has been updated
$admin_check = $conn->query("SELECT * FROM users WHERE role_id = 1 AND password != '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'");
if ($admin_check && $admin_check->num_rows > 0) {
    $setup_completed = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$setup_completed) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        // Update admin password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = ? WHERE role_id = 1";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("s", $hashed_password);
        
        if ($stmt->execute()) {
            $success_message = "Admin account activated successfully!";
            $setup_completed = true;
        } else {
            $error_message = "Error activating admin account: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Management System Setup</title>
    <link rel="stylesheet" href="src/styles/setup.css">
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Admin Account Activation</h1>
            <p>Set a new password for the administrator account (admin@medical.com)</p>
        </div>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="message success">
                <?php echo $success_message; ?>
                <p>You can now <a href="admin/login.php">login to your account</a>.</p>
            </div>
        <?php endif; ?>

        <?php if ($setup_completed): ?>
            <div class="message success">
                Account has already been activated.
                <p>Please <a href="admin/login.php">login to your account</a>.</p>
            </div>
        <?php else: ?>
            <form method="POST" class="setup-form">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required 
                           minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                    <small>Password must contain at least 8 characters, including uppercase, lowercase and numbers</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="setup-btn">Activate Admin Account</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
