<?php
session_start();
require_once '../../../../../config/config.php';

// 1) Only students allowed
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: /medical/src/auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$prescriptions = [];

// 2) Fetch recent prescriptions
try {
    $prescriptionsQuery = "SELECT p.*, u.first_name, u.last_name, d.specialization 
                          FROM prescriptions p
                          JOIN users u ON p.doctor_id = u.user_id
                          LEFT JOIN doctors d ON u.user_id = d.user_id
                          WHERE p.user_id = ? 
                          ORDER BY p.issue_date DESC";
    $prescriptionsStmt = $conn->prepare($prescriptionsQuery);
    $prescriptionsStmt->bind_param("i", $user_id);
    $prescriptionsStmt->execute();
    $prescriptions = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching prescriptions: " . $e->getMessage());
}

// Include header
include_once '../../../../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions - Medical Management System</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/student/styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .prescription-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .prescription-table th, .prescription-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .prescription-table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .prescription-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .prescription-table tr:hover {
            background-color: #f1f1f1;
        }
        .status-indicator {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: 500;
        }
        .active {
            background-color: #d4edda;
            color: #155724;
        }
        .expired {
            background-color: #f8d7da;
            color: #721c24;
        }
        .refilled {
            background-color: #d4edda;
            color: #155724;
        }
        .empty-state {
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="student-dashboard">
    <div class="dashboard-grid">
        <div class="dashboard-column">
            <div class="section-header">
                <h2 class="section-title">Recent Prescriptions</h2>
                <a href="/medical/src/modules/dashboard/patient/prescriptions/index.php" class="btn btn-primary">View All</a>
            </div>
            <div class="prescriptions-list">
                <?php if (!empty($prescriptions)): ?>
                    <table class="prescription-table">
                        <thead>
                            <tr>
                                <th>Date Issued</th>
                                <th>Prescription Details</th>
                                <th>Doctor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prescriptions as $prescription): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($prescription['issue_date'])) ?></td>
                                    <td>
                                        <div class="prescription-details">
                                            <div class="drug-name"><?= htmlspecialchars($prescription['drug_name'] ?? 'N/A') ?></div>
                                            <div class="dosage"><?= htmlspecialchars($prescription['dosage'] ?? 'N/A') ?></div>
                                            <div class="duration"><?= htmlspecialchars($prescription['duration'] ?? 'N/A') ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="doctor-info">
                                            <div class="doctor-name">Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></div>
                                            <div class="specialization"><?= htmlspecialchars($prescription['specialization'] ?? 'General Practitioner') ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-indicator <?= strtolower($prescription['status']) ?>"><?= htmlspecialchars($prescription['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-prescription-bottle"></i>
                        <div class="empty-message">
                            <div class="empty-title">No prescriptions</div>
                            <div class="empty-description">Your prescriptions will appear here</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add animation classes after DOM is loaded
    setTimeout(() => {
        document.querySelectorAll('table')[0].classList.add('animate-in');
    }, 300);
});
</script>

</body>
</html>
