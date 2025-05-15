<?php
session_start();
require_once '../../../../config/config.php';

// Check if user is logged in and has nurse role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'nurse') {
    header("Location: /medical/src/auth/login.php");
    exit;
}

// Get current user data
$user_id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get counts for dashboard stats
$totalVisitsQuery = "SELECT COUNT(*) as count FROM consultations";
$totalVisits = $conn->query($totalVisitsQuery)->fetch_assoc()['count'] ?? 0;

$todayVisitsQuery = "SELECT COUNT(*) as count FROM consultations WHERE DATE(consultation_date) = CURDATE()";
$todayVisits = $conn->query($todayVisitsQuery)->fetch_assoc()['count'] ?? 0;

$currentVisitsQuery = "SELECT COUNT(*) as count FROM consultations 
                    WHERE DATE(consultation_date) = CURDATE() AND status = 'Scheduled'";
$currentVisits = $conn->query($currentVisitsQuery)->fetch_assoc()['count'] ?? 0;

// Get today's visits
$todayQuery = "SELECT c.*, u.first_name, u.last_name, u.school_id, u.profile_image
              FROM consultations c
              JOIN users u ON c.patient_id = u.user_id
              WHERE DATE(c.consultation_date) = CURDATE()
              ORDER BY c.consultation_date DESC";
$todayVisitsList = $conn->query($todayQuery)->fetch_all(MYSQLI_ASSOC) ?? [];

// Get inventory alerts (items with low stock)
$inventoryAlertsQuery = "SELECT * FROM medical_supplies 
                       WHERE current_quantity <= reorder_level
                       ORDER BY current_quantity ASC
                       LIMIT 5";
$inventoryAlerts = $conn->query($inventoryAlertsQuery)->fetch_all(MYSQLI_ASSOC) ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - MedMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/nurse/styles/nurse.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include_once '../../../../includes/header.php'; ?>
    
    <section class="main-content">
        <div class="container">
           
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>Welcome, <?= htmlspecialchars($user['first_name'] ?? ($_SESSION['full_name'] ?? $_SESSION['username'])); ?>!</h1>
                <p>Manage student visits, track health records, and monitor medical supplies from your dashboard.</p>
            </div>
            <div class="welcome-image">
                <img src="/medical/assets/img/nurse-dashboard.svg" alt="Nurse Dashboard" onerror="this.src='/medical/assets/img/default-banner.png'">
            </div>
        </div>

            <!-- Stats Cards -->
            <div class="stats-row">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-hospital-user"></i>
                    </div>
                    <div class="stats-info">
                        <h3>Total Visits</h3>
                        <p class="stats-number"><?= $totalVisits ?></p>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stats-info">
                        <h3>Today's Visits</h3>
                        <p class="stats-number"><?= $todayVisits ?></p>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="fas fa-procedures"></i>
                    </div>
                    <div class="stats-info">
                        <h3>Current Visits</h3>
                        <p class="stats-number"><?= $currentVisits ?></p>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stats-info">
                        <h3>Inventory Alerts</h3>
                        <p class="stats-number"><?= count($inventoryAlerts) ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-content">
                <!-- Today's Clinic Visits -->
                <div class="content-section visits-section">
                    <div class="section-header">
                        <h2><i class="fas fa-clipboard-list"></i> Today's Clinic Visits</h2>
                        <span class="badge"><?= count($todayVisitsList) ?></span>
                    </div>
                    <div class="section-body">
                        <?php if (count($todayVisitsList) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>ID</th>
                                            <th>Patient</th>
                                            <th>Complaint</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todayVisitsList as $visit): ?>
                                            <tr>
                                                <td><?= date('h:i A', strtotime($visit['consultation_date'])) ?></td>
                                                <td><?= htmlspecialchars($visit['school_id'] ?? 'N/A') ?></td>
                                                <td>
                                                    <div class="patient-info">
                                                        <img src="<?= !empty($visit['profile_image']) ? htmlspecialchars($visit['profile_image']) : '/medical/assets/img/user-icon.png' ?>" class="profile-img" alt="Patient">
                                                        <?= htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($visit['chief_complaint'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php
                                                    $status = $visit['status'] ?? '';
                                                    $statusClass = '';
                                                    $statusText = 'Unknown';
                                                    
                                                    switch (strtolower($status)) {
                                                        case 'waiting':
                                                            $statusClass = 'badge-warning';
                                                            $statusText = 'Waiting';
                                                            break;
                                                        case 'in-progress':
                                                            $statusClass = 'badge-info';
                                                            $statusText = 'In Progress';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'badge-success';
                                                            $statusText = 'Completed';
                                                            break;
                                                        case 'referred':
                                                            $statusClass = 'badge-primary';
                                                            $statusText = 'Referred';
                                                            break;
                                                        default:
                                                            $statusClass = 'badge-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge-status <?= $statusClass ?>"><?= $statusText ?></span>
                                                </td>
                                                <td class="actions">
                                                    <a href="/medical/src/modules/consultation/view.php?id=<?= $visit['consultation_id'] ?? '' ?>" class="btn-action btn-view">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/medical/src/modules/consultation/process.php?id=<?= $visit['consultation_id'] ?? '' ?>" class="btn-action btn-process">
                                                        <i class="fas fa-heartbeat"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-info-circle"></i>
                                <p>No clinic visits recorded for today.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="sidebar-content">
                    <!-- Inventory Alerts -->
                    <div class="content-section alerts-section">
                        <div class="section-header">
                            <h2><i class="fas fa-exclamation-triangle"></i> Inventory Alerts</h2>
                        </div>
                        <div class="section-body">
                            <?php if (count($inventoryAlerts) > 0): ?>
                                <ul class="alert-list">
                                    <?php foreach ($inventoryAlerts as $item): ?>
                                        <li class="alert-item">
                                            <div class="alert-info">
                                                <span class="alert-title"><?= htmlspecialchars($item['item_name'] ?? 'Unknown Item') ?></span>
                                                <div class="alert-meta">
                                                    Stock: <span class="text-danger"><?= $item['current_quantity'] ?? 0 ?></span> / <?= $item['reorder_level'] ?? 0 ?>
                                                </div>
                                            </div>
                                            <a href="/medical/src/modules/inventory/update.php?id=<?= $item['item_id'] ?? '' ?>" class="btn-restock">
                                                <i class="fas fa-plus"></i> Restock
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <p>All inventory items are adequately stocked.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="content-section quick-actions-section">
                        <div class="section-header">
                            <h2><i class="fas fa-tasks"></i> Quick Actions</h2>
                        </div>
                        <div class="section-body">
                            <div class="quick-actions-list">
                                <a href="/medical/src/modules/consultation/create.php" class="quick-action-item">
                                    <div class="quick-action-icon green">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <span class="quick-action-text">Record New Visit</span>
                                </a>
                                <a href="/medical/src/modules/health/search.php" class="quick-action-item">
                                    <div class="quick-action-icon blue">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <span class="quick-action-text">Search Health Records</span>
                                </a>
                                <a href="/medical/src/modules/medical/notes.php" class="quick-action-item">
                                    <div class="quick-action-icon purple">
                                        <i class="fas fa-file-medical"></i>
                                    </div>
                                    <span class="quick-action-text">Add Medical Notes</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Visit Statistics and Recent Activities -->
            <div class="dashboard-charts">
                <!-- Visit Statistics Chart -->
                <div class="content-section chart-section">
                    <div class="section-header">
                        <h2><i class="fas fa-chart-line"></i> Weekly Visit Statistics</h2>
                    </div>
                    <div class="section-body">
                        <div class="chart-container">
                            <canvas id="visitsStatsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="sidebar-right">
                    <!-- Recent Activities -->
                    <div class="content-section activities-section">
                        <div class="section-header">
                            <h2><i class="fas fa-history"></i> Recent Activities</h2>
                        </div>
                        <div class="section-body">
                            <ul class="timeline">
                                <li class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h5 class="timeline-title">Inventory updated</h5>
                                        <p class="timeline-text">Restocked Paracetamol tablets</p>
                                        <p class="timeline-date">Today, 10:45 AM</p>
                                    </div>
                                </li>
                                <li class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h5 class="timeline-title">Patient visit completed</h5>
                                        <p class="timeline-text">Sarah Johnson (ID: S12345)</p>
                                        <p class="timeline-date">Today, 9:15 AM</p>
                                    </div>
                                </li>
                                <li class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h5 class="timeline-title">Daily report generated</h5>
                                        <p class="timeline-text">For <?= date('F j, Y', strtotime('-1 day')) ?></p>
                                        <p class="timeline-date">Yesterday, 5:00 PM</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    

    
    <!-- Custom JS for Charts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var visitsStatsCtx = document.getElementById('visitsStatsChart').getContext('2d');
        var visitsStatsChart = new Chart(visitsStatsCtx, {
            type: 'line',
            data: {
                labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                datasets: [{
                    label: 'Clinic Visits',
                    data: [15, 12, 18, 14, 20, 8, 5],
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    borderColor: 'rgb(14, 165, 233)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Referred Cases',
                    data: [3, 5, 4, 2, 6, 1, 0],
                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                    borderColor: 'rgb(234, 179, 8)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5,
                            color: '#94a3b8',
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            boxWidth: 12,
                            color: '#333',
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        titleColor: '#f8fafc',
                        bodyColor: '#e2e8f0',
                        titleFont: {
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 12
                        },
                        borderColor: 'rgba(148, 163, 184, 0.2)',
                        borderWidth: 1,
                        displayColors: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        boxPadding: 4,
                        usePointStyle: true
                    }
                }
            }
        });
    });
    </script>
</body>
</html>
<<<<<<< HEAD
>>>>>>> 6555137 (Added my changes)
=======
>>>>>>> 6555137 (Added my changes)
