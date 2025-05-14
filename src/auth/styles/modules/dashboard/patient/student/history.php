<?php
session_start();
require_once '../../../../../config/config.php';

// Check if user is logged in and has student role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get current user data
$user_id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // User not found in database
    session_destroy();
    header("Location: /medical/auth/login.php?error=user_not_found");
    exit;
}

// Get consultation history
try {
    $historyQuery = "SELECT c.*, u.first_name, u.last_name, d.specialization 
                    FROM consultations c
                    JOIN users u ON c.doctor_id = u.user_id
                    LEFT JOIN doctors d ON u.user_id = d.user_id
                    WHERE c.patient_id = ? 
                    ORDER BY c.consultation_date DESC";
    $historyStmt = $conn->prepare($historyQuery);
    $historyStmt->bind_param("i", $user_id);
    $historyStmt->execute();
    $consultations = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching consultation history: " . $e->getMessage());
    $consultations = [];
}

// Pass the role to be used in the sidebar
$role = 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation History - Medical Management System</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/student/styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php include_once '../../../../../includes/header.php'; ?>

<div class="student-dashboard">
    <div class="dashboard-column">
        <div class="section-header">
            <h2 class="section-title">Consultation History</h2>
        </div>
        
        <div class="history-table">
            <?php if (!empty($consultations)): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $consultation): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($consultation['consultation_date'])) ?></td>
                                <td>
                                    Dr. <?= htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']) ?>
                                    <?php if (isset($consultation['specialization'])): ?>
                                        <span class="text-secondary">(<?= htmlspecialchars($consultation['specialization']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($consultation['type']) ?></td>
                                <td>
                                    <span class="status-badge <?= strtolower($consultation['status']) ?>">
                                        <?= $consultation['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="/medical/src/modules/dashboard/patient/appointments/view.php?id=<?= $consultation['consultation_id'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="/medical/src/modules/dashboard/patient/appointments/print.php?id=<?= $consultation['consultation_id'] ?>" 
                                           class="btn btn-secondary btn-sm">
                                            <i class="fas fa-print"></i> Print
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <div class="empty-message">
                        <div class="empty-title">No consultation history</div>
                        <div class="empty-description">Your consultations will appear here</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add delete confirmation
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this consultation?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

</body>
</html>
