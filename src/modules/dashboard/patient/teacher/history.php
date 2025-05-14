<?php
session_start();
require_once '../../../../../config/config.php';

// Check if user is logged in and has teacher role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get current user data
$user_id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Set default filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$record_type = isset($_GET['record_type']) ? $_GET['record_type'] : 'all';

// Get consultation history
$consultationQuery = "SELECT c.*, u.first_name, u.last_name, u.profile_image 
                     FROM consultations c
                     JOIN users u ON c.doctor_id = u.user_id
                     WHERE c.patient_id = ? 
                     AND c.consultation_date BETWEEN ? AND ?
                     ORDER BY c.consultation_date DESC";
$consultationStmt = $conn->prepare($consultationQuery);
$consultationStmt->bind_param("iss", $user_id, $start_date, $end_date);
$consultationStmt->execute();
$consultations = $consultationStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get prescription history
$prescriptionQuery = "SELECT p.*, u.first_name, u.last_name, GROUP_CONCAT(m.name SEPARATOR ', ') as medications
                     FROM prescriptions p
                     JOIN users u ON p.doctor_id = u.user_id
                     LEFT JOIN prescription_items pi ON p.prescription_id = pi.prescription_id
                     LEFT JOIN medications m ON pi.medication_id = m.medication_id
                     WHERE p.user_id = ?
                     AND p.created_at BETWEEN ? AND ?
                     GROUP BY p.prescription_id
                     ORDER BY p.created_at DESC";
$prescriptionStmt = $conn->prepare($prescriptionQuery);
$prescriptionStmt->bind_param("iss", $user_id, $start_date, $end_date);
$prescriptionStmt->execute();
$prescriptions = $prescriptionStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get medical test history
$tests = []; // Initialize empty array since the table doesn't exist
// Commenting out the query to prevent errors
/*
$testQuery = "SELECT mt.*, u.first_name, u.last_name, tr.result_value, tr.result_notes
              FROM medical_tests mt
              JOIN users u ON mt.ordered_by = u.user_id
              LEFT JOIN test_results tr ON mt.test_id = tr.test_id
              WHERE mt.patient_id = ?
              AND mt.test_date BETWEEN ? AND ?
              ORDER BY mt.test_date DESC";
$testStmt = $conn->prepare($testQuery);
$testStmt->bind_param("iss", $user_id, $start_date, $end_date);
$testStmt->execute();
$tests = $testStmt->get_result()->fetch_all(MYSQLI_ASSOC);
*/

// Get absence/medical leave history
$absences = []; // Initialize empty array
// Check if the absences table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'absences'");
if($tableCheck->num_rows > 0) {
    $absenceQuery = "SELECT a.*, u.first_name, u.last_name
                    FROM absences a
                    JOIN users u ON a.approved_by = u.user_id
                    WHERE a.user_id = ?
                    AND a.start_date BETWEEN ? AND ?
                    ORDER BY a.start_date DESC";
    $absenceStmt = $conn->prepare($absenceQuery);
    $absenceStmt->bind_param("iss", $user_id, $start_date, $end_date);
    $absenceStmt->execute();
    $absences = $absenceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History - Teacher Health Dashboard</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr for date selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css"> 
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="styles/teacher.css">
    <style>
        .history-timeline {
            position: relative;
            padding: 20px 0;
            margin-top: 20px;
        }
        
        .timeline-line {
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: var(--border-color);
            z-index: 1;
        }
        
        .history-item {
            position: relative;
            margin-bottom: 25px;
            padding-left: 60px;
            z-index: 2;
        }
        
        .history-date {
            position: absolute;
            left: 0;
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            line-height: 1;
            font-weight: 600;
            z-index: 3;
        }
        
        .history-date .date-day {
            font-size: 16px;
        }
        
        .history-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 15px;
            transition: transform 0.3s ease;
        }
        
        .history-card:hover {
            transform: translateY(-3px);
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: flex-start;
        }
        
        .history-title {
            margin: 0;
            font-size: 16px;
            color: var(--text-color);
        }
        
        .history-provider {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 5px 0;
        }
        
        .history-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-consultation {
            background-color: rgba(var(--primary-rgb), 0.15);
            color: var(--primary-color);
        }
        
        .badge-prescription {
            background-color: rgba(var(--success-rgb), 0.15);
            color: var(--success-color);
        }
        
        .badge-test {
            background-color: rgba(var(--info-rgb), 0.15);
            color: var(--info-color);
        }
        
        .badge-absence {
            background-color: rgba(var(--warning-rgb), 0.15);
            color: var(--warning-color);
        }
        
        .history-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        .filter-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .filter-tab {
            padding: 10px 15px;
            cursor: pointer;
            font-weight: 500;
            position: relative;
            color: var(--text-secondary);
        }
        
        .filter-tab.active {
            color: var(--primary-color);
        }
        
        .filter-tab.active:after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .filter-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .date-range {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 40px;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }
        
        .empty-title {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .empty-description {
            color: var(--text-secondary);
        }
        
        @media (max-width: 768px) {
            .history-item {
                padding-left: 50px;
            }
            
            .filter-controls {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    
    <div class="teacher-dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-history"></i> Medical History</h1>
            <p>View your complete medical history including consultations, prescriptions, tests, and absences.</p>
        </div>
        
        <!-- Filter Controls -->
        <div class="filter-controls">
            <div class="filter-tabs">
                <div class="filter-tab <?= $record_type == 'all' ? 'active' : '' ?>" data-type="all">All Records</div>
                <div class="filter-tab <?= $record_type == 'consultations' ? 'active' : '' ?>" data-type="consultations">Consultations</div>
                <div class="filter-tab <?= $record_type == 'prescriptions' ? 'active' : '' ?>" data-type="prescriptions">Prescriptions</div>
                <div class="filter-tab <?= $record_type == 'tests' ? 'active' : '' ?>" data-type="tests">Medical Tests</div>
                <div class="filter-tab <?= $record_type == 'absences' ? 'active' : '' ?>" data-type="absences">Medical Leaves</div>
            </div>
            
            <div class="date-range">
                <form id="filterForm" method="GET" action="">
                    <input type="hidden" name="record_type" id="record_type" value="<?= htmlspecialchars($record_type) ?>">
                    <div class="form-group">
                        <label for="start_date">From:</label>
                        <input type="date" class="form-control date-picker" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">To:</label>
                        <input type="date" class="form-control date-picker" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </form>
            </div>
        </div>
        
        <!-- History Timeline -->
        <div class="history-timeline">
            <div class="timeline-line"></div>
            
            <?php
            $allRecords = [];
            
            // Prepare consultations
            if ($record_type == 'all' || $record_type == 'consultations') {
                foreach ($consultations as $consultation) {
                    $record = [
                        'type' => 'consultation',
                        'date' => $consultation['consultation_date'],
                        'title' => $consultation['consultation_type'],
                        'provider' => 'Dr. ' . $consultation['first_name'] . ' ' . $consultation['last_name'],
                        'details' => $consultation['notes'] ?? 'No notes provided',
                        'status' => $consultation['status']
                    ];
                    $allRecords[] = $record;
                }
            }
            
            // Prepare prescriptions
            if ($record_type == 'all' || $record_type == 'prescriptions') {
                foreach ($prescriptions as $prescription) {
                    $record = [
                        'type' => 'prescription',
                        'date' => $prescription['created_at'],
                        'title' => 'Prescription #' . $prescription['prescription_id'],
                        'provider' => 'Dr. ' . $prescription['first_name'] . ' ' . $prescription['last_name'],
                        'details' => $prescription['medications'] ?? 'No medications listed',
                        'status' => 'Active'
                    ];
                    $allRecords[] = $record;
                }
            }
            
            // We're skipping tests since the table doesn't exist
            
            // Prepare absences (if table exists)
            if (($record_type == 'all' || $record_type == 'absences') && !empty($absences)) {
                foreach ($absences as $absence) {
                    $record = [
                        'type' => 'absence',
                        'date' => $absence['start_date'],
                        'title' => 'Medical Leave',
                        'provider' => 'Approved by: ' . $absence['first_name'] . ' ' . $absence['last_name'],
                        'details' => 'Duration: ' . date('M d', strtotime($absence['start_date'])) . ' to ' . date('M d, Y', strtotime($absence['end_date'])) . '<br>Reason: ' . $absence['reason'],
                        'status' => $absence['status']
                    ];
                    $allRecords[] = $record;
                }
            }
            
            // Sort records by date (most recent first)
            usort($allRecords, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            // Display records
            if (count($allRecords) > 0):
                foreach ($allRecords as $index => $record):
                    $recordDate = new DateTime($record['date']);
            ?>
                <div class="history-item animate-in" style="animation-delay: <?= 0.1 * $index ?>s;">
                    <div class="history-date">
                        <span class="date-month"><?= $recordDate->format('M') ?></span>
                        <span class="date-day"><?= $recordDate->format('d') ?></span>
                    </div>
                    <div class="history-card">
                        <div class="history-header">
                            <div>
                                <h4 class="history-title"><?= htmlspecialchars($record['title']) ?></h4>
                                <p class="history-provider"><?= htmlspecialchars($record['provider']) ?></p>
                            </div>
                            <span class="history-badge badge-<?= $record['type'] ?>">
                                <?= ucfirst(htmlspecialchars($record['type'])) ?>
                            </span>
                        </div>
                        <div class="history-details">
                            <?= htmlspecialchars($record['details']) ?>
                            <?php if (isset($record['status'])): ?>
                                <div class="status-badge <?= strtolower($record['status']) ?>">
                                    <?= htmlspecialchars($record['status']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h4 class="empty-title">No Records Found</h4>
                    <p class="empty-description">There are no medical records matching your filters for the selected date range.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Export Options -->
        <div class="export-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-file-export"></i>
                    Export Options
                </h3>
            </div>
            <div class="export-buttons">
                <a href="export.php?format=pdf&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&record_type=<?= urlencode($record_type) ?>" class="btn btn-secondary">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </a>
                <a href="export.php?format=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&record_type=<?= urlencode($record_type) ?>" class="btn btn-secondary">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date pickers
        flatpickr(".date-picker", {
            dateFormat: "Y-m-d"
        });
        
        // Handle filter tab clicks
        const filterTabs = document.querySelectorAll('.filter-tab');
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Update hidden input and submit form
                document.getElementById('record_type').value = this.dataset.type;
                document.getElementById('filterForm').submit();
            });
        });
        
        // Animate elements when they come into view
        const animateItems = document.querySelectorAll('.history-item');
        animateItems.forEach(item => {
            item.classList.add('animate-in');
        });
    });
    </script>
</body>
</html>