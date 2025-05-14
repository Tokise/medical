<?php
session_start();
require_once '../../../../../config/config.php';

// Check if user is logged in and has staff role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'staff') {
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

// Get all prescriptions for the current user
$prescriptionsQuery = "SELECT p.*, u.first_name, u.last_name, u.profile_image 
                      FROM prescriptions p
                      JOIN users u ON p.doctor_id = u.user_id
                      WHERE p.user_id = ?
                      ORDER BY p.issue_date DESC";
$prescriptionsStmt = $conn->prepare($prescriptionsQuery);
$prescriptionsStmt->bind_param("i", $user_id);
$prescriptionsStmt->execute();
$prescriptions = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get prescription details (medications)
if (!empty($prescriptions)) {
    foreach ($prescriptions as $key => $prescription) {
        $medicationsQuery = "SELECT pi.*, m.name as medication_name, m.description as medication_description
                           FROM prescription_items pi
                           JOIN medications m ON pi.medication_id = m.medication_id
                           WHERE pi.prescription_id = ?";
        $medicationsStmt = $conn->prepare($medicationsQuery);
        $medicationsStmt->bind_param("i", $prescription['prescription_id']);
        $medicationsStmt->execute();
        $prescriptions[$key]['medications'] = $medicationsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Get active prescriptions count
$activeQuery = "SELECT COUNT(*) as count FROM prescriptions 
               WHERE user_id = ? AND expiry_date >= CURDATE()";
$activeStmt = $conn->prepare($activeQuery);
$activeStmt->bind_param("i", $user_id);
$activeStmt->execute();
$activeCount = $activeStmt->get_result()->fetch_assoc()['count'];

// Get expired prescriptions count
$expiredQuery = "SELECT COUNT(*) as count FROM prescriptions 
                WHERE user_id = ? AND expiry_date < CURDATE()";
$expiredStmt = $conn->prepare($expiredQuery);
$expiredStmt->bind_param("i", $user_id);
$expiredStmt->execute();
$expiredCount = $expiredStmt->get_result()->fetch_assoc()['count'];

// Filter prescriptions if requested
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if ($filter === 'active') {
    $filteredPrescriptions = array_filter($prescriptions, function($p) {
        return strtotime($p['expiry_date']) >= strtotime(date('Y-m-d'));
    });
} elseif ($filter === 'expired') {
    $filteredPrescriptions = array_filter($prescriptions, function($p) {
        return strtotime($p['expiry_date']) < strtotime(date('Y-m-d'));
    });
} else {
    $filteredPrescriptions = $prescriptions;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions - Staff Health Portal</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css"> 
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="../staff/styles/staff.css">
    <style>
        :root {
            --card-shadow: 0 3px 12px rgba(0,0,0,0.08);
            --hover-shadow: 0 8px 16px rgba(0,0,0,0.12);
            --card-radius: 12px;
            --transition-speed: 0.3s;
            --prescription-gradient: linear-gradient(135deg, var(--primary-light) 0%, rgba(255,255,255,0) 60%);
            --active-pill: linear-gradient(90deg, var(--success) 0%, var(--success-light) 100%);
            --expired-pill: linear-gradient(90deg, var(--danger) 0%, var(--danger-light) 100%);
        }

        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .prescription-card {
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: var(--card-radius);
            margin-bottom: 28px;
            background-color: #fff;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed) cubic-bezier(0.165, 0.84, 0.44, 1);
            overflow: hidden;
            position: relative;
        }

        .prescription-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: var(--prescription-gradient);
            opacity: 0.1;
            pointer-events: none;
        }

        .prescription-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .prescription-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            background-color: rgba(var(--primary-rgb), 0.03);
            border-radius: var(--card-radius) var(--card-radius) 0 0;
        }

        .prescription-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            align-items: center;
        }

        .prescription-title::before {
            content: '\f3e5';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 10px;
            color: var(--primary);
        }

        .prescription-status {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .status-active {
            background: var(--active-pill);
            color: white;
        }

        .status-expired {
            background: var(--expired-pill);
            color: white;
        }

        .prescription-body {
            padding: 24px;
        }

        .prescription-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .info-column {
            flex: 1;
            min-width: 250px;
        }

        .info-item {
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px dashed rgba(0,0,0,0.06);
        }

        .info-label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.05rem;
        }

        .doctor-info {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            padding: 15px;
            background-color: rgba(var(--primary-rgb), 0.03);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .doctor-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid white;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        .medications-list {
            margin-top: 24px;
        }

        .medications-list h4 {
            margin-bottom: 16px;
            font-size: 1.2rem;
            color: var(--text-dark);
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .medications-list h4::before {
            content: '\f484';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 10px;
            color: var(--primary);
            font-size: 0.9em;
        }

        .medication-item {
            padding: 18px;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 10px;
            margin-bottom: 14px;
            background-color: #fff;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .medication-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }

        .medication-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .medication-name {
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            font-size: 1.1rem;
        }

        .medication-dosage {
            font-weight: 600;
            color: var(--text-dark);
            background-color: rgba(var(--primary-rgb), 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .medication-instructions {
            color: var(--text-body);
            font-size: 0.95rem;
            margin-bottom: 12px;
            padding: 12px;
            background-color: rgba(0,0,0,0.02);
            border-radius: 6px;
            border-left: 3px solid var(--info);
        }

        .medication-details {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-style: italic;
        }

        .filter-tabs {
            display: flex;
            margin-bottom: 28px;
            border-bottom: 1px solid var(--border-color);
            background-color: #fff;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 0 10px;
        }

        .filter-tab {
            padding: 16px 24px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-muted);
            position: relative;
            transition: all 0.3s ease;
        }

        .filter-tab.active {
            color: var(--primary);
        }

        .filter-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary);
            border-radius: 3px 3px 0 0;
        }

        .filter-tab:hover {
            color: var(--primary-dark);
            background-color: rgba(var(--primary-rgb), 0.03);
        }

        .filter-tab .badge {
            margin-left: 8px;
            background-color: var(--bg-light);
            color: var(--text-muted);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .filter-tab.active .badge {
            background-color: var(--primary);
            color: white;
        }

        .prescription-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid var(--border-color);
        }

        .prescription-actions .btn {
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .prescription-actions .btn i {
            margin-right: 6px;
        }

        .btn-sm {
            font-size: 0.85rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-top: 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary-light);
            margin-bottom: 24px;
            opacity: 0.7;
        }

        .empty-title {
            font-size: 1.4rem;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .empty-description {
            color: var(--text-muted);
            max-width: 450px;
            margin: 0 auto 24px;
            line-height: 1.6;
        }

        .empty-state .btn {
            padding: 10px 24px;
            font-weight: 600;
            border-radius: 30px;
            box-shadow: 0 4px 8px rgba(var(--primary-rgb), 0.2);
            transition: all 0.3s ease;
        }

        .empty-state .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(var(--primary-rgb), 0.3);
        }

        .print-button {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }

        .print-button:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        /* Animation for cards */
        .animate-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .animate-in.show {
            opacity: 1;
            transform: translateY(0);
        }

        @media print {
            .no-print {
                display: none;
            }
            .prescription-card {
                box-shadow: none;
                border: 1px solid #ddd;
                margin-bottom: 30px;
                page-break-inside: avoid;
            }
            .prescription-card::before {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .prescription-info {
                flex-direction: column;
            }
            
            .info-column {
                flex: 100%;
            }
            
            .filter-tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding: 0;
            }
            
            .filter-tab {
                padding: 12px 16px;
            }
            
            .prescription-actions {
                flex-wrap: wrap;
            }
            
            .prescription-actions .btn {
                flex: 1;
                min-width: 120px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    <br><br><br>
    <div class="container">
        <div class="page-header no-print">
            <div>
                <h1 class="page-title">My Prescriptions</h1>
                <p class="page-subtitle">View and manage your medical prescriptions</p>
            </div>
            <div class="page-actions">
                <button onclick="window.print()" class="btn btn-outline-primary no-print">
                    <i class="fas fa-print"></i> Print All
                </button>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs no-print">
            <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
                All <span class="badge"><?= count($prescriptions) ?></span>
            </a>
            <a href="?filter=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">
                Active <span class="badge"><?= $activeCount ?></span>
            </a>
            <a href="?filter=expired" class="filter-tab <?= $filter === 'expired' ? 'active' : '' ?>">
                Expired <span class="badge"><?= $expiredCount ?></span>
            </a>
        </div>
        
        <!-- Prescriptions List -->
        <?php if (!empty($filteredPrescriptions)): ?>
            <?php foreach ($filteredPrescriptions as $prescription): ?>
                <?php 
                    $isActive = strtotime($prescription['expiry_date']) >= strtotime(date('Y-m-d'));
                    $status = $isActive ? 'Active' : 'Expired';
                    $statusClass = $isActive ? 'status-active' : 'status-expired';
                ?>
                <div class="prescription-card animate-in">
                    <div class="prescription-header">
                        <h3 class="prescription-title">
                            Prescription #<?= $prescription['prescription_id'] ?>
                        </h3>
                        <span class="prescription-status <?= $statusClass ?>"><?= $status ?></span>
                    </div>
                    
                    <div class="prescription-body">
                        <div class="prescription-info">
                            <div class="info-column">
                                <div class="info-item">
                                    <span class="info-label">Issue Date</span>
                                    <span class="info-value"><?= date('F j, Y', strtotime($prescription['issue_date'])) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Expiry Date</span>
                                    <span class="info-value"><?= date('F j, Y', strtotime($prescription['expiry_date'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="info-column">
                                <div class="info-item">
                                    <span class="info-label">Diagnosis</span>
                                    <span class="info-value"><?= htmlspecialchars($prescription['diagnosis'] ?? 'Not specified') ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Notes</span>
                                    <span class="info-value"><?= htmlspecialchars($prescription['notes'] ?? 'No additional notes') ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="doctor-info">
                            <img src="<?= htmlspecialchars($prescription['profile_image'] ?? '/medical/assets/img/default-avatar.png') ?>" alt="Doctor" class="doctor-avatar" onerror="this.src='/medical/assets/img/default-avatar.png'">
                            <div>
                                <span class="info-label">Prescribed by</span>
                                <span class="info-value">Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></span>
                            </div>
                        </div>
                        
                        <h4>Medications</h4>
                        <div class="medications-list">
                            <?php if (!empty($prescription['medications'])): ?>
                                <?php foreach ($prescription['medications'] as $medication): ?>
                                    <div class="medication-item">
                                        <div class="medication-header">
                                            <h5 class="medication-name"><?= htmlspecialchars($medication['medication_name']) ?></h5>
                                            <span class="medication-dosage"><?= htmlspecialchars($medication['dosage']) ?></span>
                                        </div>
                                        <p class="medication-instructions">
                                            <strong>Instructions:</strong> <?= htmlspecialchars($medication['frequency']) ?>
                                        </p>
                                        <p class="medication-details">
                                            <?= htmlspecialchars($medication['medication_description'] ?? '') ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <p>No medication details available for this prescription.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="prescription-actions no-print">
                            <a href="/medical/src/modules/prescription/view.php?id=<?= $prescription['prescription_id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <button onclick="printPrescription(this)" class="btn btn-sm print-button">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-prescription-bottle"></i>
                <h4 class="empty-title">No Prescriptions Found</h4>
                <p class="empty-description">
                    <?php if ($filter === 'active'): ?>
                        You don't have any active prescriptions at the moment.
                    <?php elseif ($filter === 'expired'): ?>
                        You don't have any expired prescriptions.
                    <?php else: ?>
                        You don't have any prescriptions in your medical record.
                    <?php endif; ?>
                </p>
                <a href="/medical/src/modules/appointment/book.php" class="btn btn-primary">Book an Appointment</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            const animateItems = document.querySelectorAll('.animate-in');
            animateItems.forEach(item => {
                item.classList.add('show');
            });
        });
        
        // Print individual prescription
        function printPrescription(button) {
            const prescriptionCard = button.closest('.prescription-card');
            const prescriptionCards = document.querySelectorAll('.prescription-card');
            const filterTabs = document.querySelector('.filter-tabs');
            const pageHeader = document.querySelector('.page-header');
            
            // Hide all other prescription cards and page elements
            prescriptionCards.forEach(card => {
                if (card !== prescriptionCard) {
                    card.style.display = 'none';
                }
            });
            
            filterTabs.style.display = 'none';
            pageHeader.style.display = 'none';
            
            // Print
            window.print();
            
            // Restore display
            prescriptionCards.forEach(card => {
                card.style.display = 'block';
            });
            
            filterTabs.style.display = 'flex';
            pageHeader.style.display = 'flex';
        }
    </script>
</body>
</html>