<<<<<<< HEAD
<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_user':
            createUser($conn);
            break;
        case 'update_user':
            updateUser($conn);
            break;
        case 'delete_user':
            deleteUser($conn);
            break;
        default:
            header("Location: /medical/src/modules/dashboard/admin/index.php");
            exit;
    }
}

// Function to create a new user
function createUser($conn) {
    // Get form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    $school_id = $_POST['school_id'] ?? '';

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password) || empty($role_id) || empty($school_id)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: /medical/src/modules/dashboard/admin/index.php");
        exit;
    }

    // Check if username, email, or school_id already exists
    $checkQuery = "SELECT * FROM users WHERE username = ? OR email = ? OR school_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("sss", $username, $email, $school_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username, email, or School ID already exists";
        header("Location: /medical/src/modules/dashboard/admin/index.php");
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insertQuery = "INSERT INTO users (first_name, last_name, email, username, password, role_id, school_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("sssssis", $first_name, $last_name, $email, $username, $hashed_password, $role_id, $school_id);

    if ($insertStmt->execute()) {
        // Log the action
        $user_id = $_SESSION['id'];
        $action = "Created new user: $username";
        $logQuery = "INSERT INTO system_logs (user_id, action) VALUES (?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("is", $user_id, $action);
        $logStmt->execute();

        $_SESSION['success'] = "User created successfully";
    } else {
        $_SESSION['error'] = "Error creating user: " . $conn->error;
    }

    header("Location: /medical/src/modules/dashboard/admin/index.php");
    exit;
}

// Function to update an existing user
function updateUser($conn) {
    // Get form data
    $user_id = $_POST['user_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role_id = $_POST['role_id'] ?? '';

    // Validate required fields
    if (empty($user_id) || empty($first_name) || empty($last_name) || empty($email) || empty($role_id)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: /medical/src/modules/dashboard/admin/index.php");
        exit;
    }

    // Update user
    $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role_id = ? WHERE user_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sssii", $first_name, $last_name, $email, $role_id, $user_id);

    if ($updateStmt->execute()) {
        // Log the action
        $admin_id = $_SESSION['id'];
        $action = "Updated user ID: $user_id";
        $logQuery = "INSERT INTO system_logs (user_id, action) VALUES (?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("is", $admin_id, $action);
        $logStmt->execute();

        $_SESSION['success'] = "User updated successfully";
    } else {
        $_SESSION['error'] = "Error updating user: " . $conn->error;
    }

    header("Location: /medical/src/modules/dashboard/admin/index.php");
    exit;
}

// Function to delete a user
function deleteUser($conn) {
    // Get user ID
    $user_id = $_POST['user_id'] ?? '';

    if (empty($user_id)) {
        $_SESSION['error'] = "User ID is required";
        header("Location: /medical/src/modules/dashboard/admin/index.php");
        exit;
    }

    // Delete user
    $deleteQuery = "DELETE FROM users WHERE user_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $user_id);

    if ($deleteStmt->execute()) {
        // Log the action
        $admin_id = $_SESSION['id'];
        $action = "Deleted user ID: $user_id";
        $logQuery = "INSERT INTO system_logs (user_id, action) VALUES (?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("is", $admin_id, $action);
        $logStmt->execute();

        $_SESSION['success'] = "User deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting user: " . $conn->error;
    }

    header("Location: /medical/src/modules/dashboard/admin/index.php");
    exit;
}
=======
<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_user':
            createUser($conn);
            break;
        case 'update_user':
            updateUser($conn);
            break;
        case 'delete_user':
            deleteUser($conn);
            break;
        default:
            header("Location: /medical/src/modules/dashboard/admin/index.php");
            exit;
    }
}

// Function to create a new user
function createUser($conn) {
    // Get form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    $school_id = $_POST['school_id'] ?? '';

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password) || empty($role_id) || empty($school_id)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: /medical/src/modules/dashboard/admin/index.php");
        exit;
    }

    // Check if username, email, or school_id already exists
    $checkQuery = "SELECT * FROM users WHERE username = ? OR email = ? OR school_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("sss", $username, $email, $school_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username, email, or School ID already exists";
        header("Location: /medical/src/modules/dashboard/admin/index.php");
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insertQuery = "INSERT INTO users (first_name, last_name, email, username, password, role_id, school_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("sssssis", $first_name, $last_name, $email, $username, $hashed_password, $role_id, $school_id);

    if ($insertStmt->execute()) {
        // Log the action
        $user_id = $_SESSION['id'];
        $action = "Created new user: $username";
        $logQuery = "INSERT INTO system_logs (user_id, action) VALUES (?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("is", $user_id, $action);
        $logStmt->execute();

        $_SESSION['success'] = "User created successfully";
    } else {
        $_SESSION['error'] = "Error creating user: " . $conn->error;
    }

    header("Location: /medical/src/modules/dashboard/admin/index.php");
    exit;
}

// Function to update an existing user
function updateUser($conn) {
    // Get form data
    $user_id = $_POST['user_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role_id = $_POST['role_id'] ?? '';

    // Validate required fields
    if (empty($user_id) || empty($first_name) || empty($last_name) || empty($email) || empty($role_id)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: /medical/src/modules/dashboard/admin/index.php");
        exit;
    }

    // Update user
    $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role_id = ? WHERE user_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sssii", $first_name, $last_name, $email, $role_id, $user_id);

    if ($updateStmt->execute()) {
        // Log the action
        $admin_id = $_SESSION['id'];
        $action = "Updated user ID: $user_id";
        $logQuery = "INSERT INTO system_logs (user_id, action) VALUES (?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("is", $admin_id, $action);
        $logStmt->execute();

        $_SESSION['success'] = "User updated successfully";
    } else {
        $_SESSION['error'] = "Error updating user: " . $conn->error;
    }

    header("Location: /medical/src/modules/dashboard/admin/index.php");
    exit;
}

// Function to delete a user
function deleteUser($conn) {
    // Get user ID
    $user_id = $_POST['user_id'] ?? '';

    if (empty($user_id)) {
        $_SESSION['error'] = "User ID is required";
        header("Location: /medical/src/modules/dashboard/admin/index.php");
        exit;
    }

    // Delete user
    $deleteQuery = "DELETE FROM users WHERE user_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $user_id);

    if ($deleteStmt->execute()) {
        // Log the action
        $admin_id = $_SESSION['id'];
        $action = "Deleted user ID: $user_id";
        $logQuery = "INSERT INTO system_logs (user_id, action) VALUES (?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("is", $admin_id, $action);
        $logStmt->execute();

        $_SESSION['success'] = "User deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting user: " . $conn->error;
    }

    header("Location: /medical/src/modules/dashboard/admin/index.php");
    exit;
}
>>>>>>> 6555137 (Added my changes)
?> 