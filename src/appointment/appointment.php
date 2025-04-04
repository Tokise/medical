<?php
session_start();
require_once '../../config/db.php';
require_once '../../auth/auth_check.php';

// Get user role and permissions
$stmt = $conn->prepare("
    SELECT r.name as role, p.name as permission, u.first_name, u.last_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    JOIN role_permissions rp ON r.id = rp.role_id 
    JOIN permissions p ON rp.permission_id = p.id 
    WHERE u.email = ?
");
$stmt->bind_param("s", $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
$permissions = [];
$role = '';
$fullname = '';

while ($row = $result->fetch_assoc()) {
    $role = $row['role'];
    $permissions[] = $row['permission'];
    $fullname = $row['first_name'] . ' ' . $row['last_name'];
}

// Add after getting user permissions
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_tutorials 
    FROM tutorials t 
    LEFT JOIN user_tutorials ut ON t.id = ut.tutorial_id AND ut.user_id = ?
    WHERE (t.user_role = ? OR t.user_role = 'all')
    AND (ut.completed IS NULL OR ut.completed = 0)
");
$stmt->bind_param("is", $_SESSION['user_id'], $role);
$stmt->execute();
$pending_tutorials = $stmt->get_result()->fetch_assoc()['pending_tutorials'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Appointment - Medical Management</title>
    <link rel="stylesheet" href="../../src/styles/global.css">
    <link rel="stylesheet" href="../../src/styles/appointment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include_once('../includes/header.php'); ?>
    <?php include_once('../includes/sidebar.php'); ?>

    <section>
        <div class="content">
            <div class="appointment-header">
                <div class="appointment-search">
                    <input type="text" placeholder="Search">
                    <i class="fas fa-search"></i>
                </div>
                <div class="actions">
                    <label>Filter</label>
                    <label>Status: Actionable</label>
                    <p class="date-update">
                        Last updated: 4 March 2025 10:45 AM
                    </p>
                </div>
                
        </div>
    </section>
</body>

</html>