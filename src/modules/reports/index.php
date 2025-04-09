<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get consultation statistics
$consultationStats = $conn->query("
    SELECT 
        COUNT(*) as total_consultations,
        COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'Scheduled' THEN 1 END) as scheduled,
        COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled
    FROM consultations
")->fetch_assoc();

// Get most common diagnoses
$commonDiagnoses = $conn->query("
    SELECT diagnosis, COUNT(*) as count
    FROM consultations 
    WHERE diagnosis IS NOT NULL
    GROUP BY diagnosis 
    ORDER BY count DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get staff performance
$staffPerformance = $conn->query("
    SELECT 
        u.first_name,
        u.last_name,
        r.role_name,
        COUNT(c.consultation_id) as total_consultations,
        AVG(CASE WHEN c.status = 'Completed' THEN 1 ELSE 0 END) * 100 as completion_rate
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    JOIN medical_staff ms ON u.user_id = ms.user_id
    LEFT JOIN consultations c ON ms.staff_id = c.staff_id
    WHERE r.role_name IN ('Doctor', 'Nurse')
    GROUP BY u.user_id
    ORDER BY total_consultations DESC
")->fetch_all(MYSQLI_ASSOC);

// Get medicine inventory stats
$medicineStats = $conn->query("
    SELECT 
        item_name,
        current_quantity,
        reorder_level,
        unit,
        expiry_date
    FROM medical_supplies
    WHERE current_quantity <= reorder_level
    ORDER BY current_quantity ASC
")->fetch_all(MYSQLI_ASSOC);

// Pass the role to be used in the header
$role = 'Admin';

require_once '../../includes/header.php';
?>

<link rel="stylesheet" href="../reports/styles/reports.css">
<div class="reports-dashboard">
    <div class="section-header">
        <h2>Reports & Analytics</h2>
        <div class="header-actions">
            <div class="date-filter">
                <select id="dateRange">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="year">This Year</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Consultations</span>
                <div class="stat-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
            </div>
            <div class="stat-value"><?= $consultationStats['total_consultations'] ?></div>
            <div class="stat-breakdown">
                <div class="breakdown-item">
                    <span class="label">Completed</span>
                    <span class="value"><?= $consultationStats['completed'] ?></span>
                </div>
                <div class="breakdown-item">
                    <span class="label">Scheduled</span>
                    <span class="value"><?= $consultationStats['scheduled'] ?></span>
                </div>
                <div class="breakdown-item">
                    <span class="label">Cancelled</span>
                    <span class="value"><?= $consultationStats['cancelled'] ?></span>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Common Diagnoses</span>
                <div class="stat-icon">
                    <i class="fas fa-notes-medical"></i>
                </div>
            </div>
            <div class="diagnoses-list">
                <?php foreach ($commonDiagnoses as $diagnosis): ?>
                <div class="diagnosis-item">
                    <span class="diagnosis-name"><?= htmlspecialchars($diagnosis['diagnosis']) ?></span>
                    <span class="diagnosis-count"><?= $diagnosis['count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Inventory Alerts</span>
                <div class="stat-icon">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
            <div class="inventory-alerts">
                <?php foreach ($medicineStats as $item): ?>
                <div class="inventory-item">
                    <div class="item-info">
                        <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                        <span class="quantity">
                            <?= $item['current_quantity'] ?>/<?= $item['reorder_level'] ?> <?= $item['unit'] ?>
                        </span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?= ($item['current_quantity'] / $item['reorder_level']) * 100 ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Staff Performance -->
    <div class="section-card">
        <div class="section-header">
            <h3>Staff Performance</h3>
        </div>
        <div class="performance-table">
            <table>
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Role</th>
                        <th>Total Consultations</th>
                        <th>Completion Rate</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffPerformance as $staff): ?>
                    <tr>
                        <td>
                            <div class="staff-name">
                                <?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?>
                            </div>
                        </td>
                        <td><?= $staff['role_name'] ?></td>
                        <td><?= $staff['total_consultations'] ?></td>
                        <td><?= number_format($staff['completion_rate'], 1) ?>%</td>
                        <td>
                            <div class="performance-bar">
                                <div class="progress" style="width: <?= $staff['completion_rate'] ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateRange = document.getElementById('dateRange');
    
    dateRange.addEventListener('change', function() {
        // Implement date range filtering
        // This would typically involve an AJAX call to fetch new data
        console.log('Date range changed to:', this.value);
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>