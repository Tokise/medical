<?php
session_start();
require_once '../../../../../config/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'teacher', 'staff'])) {
    header('Location: /medical/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get role-specific profile data
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
} elseif ($role === 'teacher') {
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
} elseif ($role === 'staff') {
    $stmt = $conn->prepare("SELECT * FROM staff WHERE user_id = ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get consultation history
$stmt = $conn->prepare("
    SELECT c.*, d.specialization, u.first_name, u.last_name, ct.name as consultation_type
    FROM consultations c
    JOIN doctors d ON c.doctor_id = d.doctor_id
    JOIN users u ON d.user_id = u.user_id
    JOIN consultation_types ct ON c.consultation_type_id = ct.consultation_type_id
    WHERE c.patient_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get medical history
$stmt = $conn->prepare("
    SELECT mh.*, d.specialization, u.first_name, u.last_name
    FROM medical_history mh
    JOIN doctors d ON mh.doctor_id = d.doctor_id
    JOIN users u ON d.user_id = u.user_id
    WHERE mh.user_id = ?
    ORDER BY mh.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$medical_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - Medical Management System</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/user/styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php include_once '../../../../../includes/header.php'; ?>

<div class="history-page">
    <div class="section-header">
        <h2 class="section-title">History</h2>
        <!-- (Optional) Add action buttons here if needed -->
    </div>
    <div class="centered-table-container">
        <?php if (!empty($consultations)): ?>
            <table class="centered-table">
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
                            <td><?= isset($consultation['type']) ? htmlspecialchars($consultation['type']) : 'N/A' ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($consultation['status']) ?>">
                                    <?= $consultation['status'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
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
