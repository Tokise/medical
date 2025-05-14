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

// Get user's prescriptions with doctor information
$prescriptionsQuery = "SELECT p.*, u.first_name, u.last_name, u.profile_image 
                      FROM prescriptions p
                      JOIN users u ON p.doctor_id = u.user_id
                      WHERE p.user_id = ?
                      ORDER BY p.created_at DESC";
$prescriptionsStmt = $conn->prepare($prescriptionsQuery);
$prescriptionsStmt->bind_param("i", $user_id);
$prescriptionsStmt->execute();
$prescriptions = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get medication details for each prescription
foreach ($prescriptions as $key => $prescription) {
    $medicationQuery = "SELECT pi.*, m.name as medication_name, m.description 
                       FROM prescription_items pi
                       JOIN medications m ON pi.medication_id = m.medication_id
                       WHERE pi.prescription_id = ?";
    $medicationStmt = $conn->prepare($medicationQuery);
    $medicationStmt->bind_param("i", $prescription['prescription_id']);
    $medicationStmt->execute();
    $prescriptions[$key]['medications'] = $medicationStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions - Teacher Health Dashboard</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="styles/teacher.css">
    <style>
        .prescription-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .prescription-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .prescription-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .prescription-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem;
            background-color: var(--primary-light);
            border-bottom: 1px solid var(--border-color);
        }
        
        .prescription-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--primary);
        }
        
        .prescription-icon {
            background-color: var(--primary-lightest);
            color: var(--primary);
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .prescription-status {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .status-active {
            background-color: var(--success-light);
            color: var(--success);
        }
        
        .status-expired {
            background-color: var(--danger-light);
            color: var(--danger);
        }
        
        .prescription-body {
            padding: 1.25rem;
        }
        
        .prescription-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .doctor-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .doctor-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .medication-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .medication-item {
            padding: 1rem;
            background-color: var(--bg-light);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .medication-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        
        .medication-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .medication-detail {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .prescription-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
            gap: 0.75rem;
        }
        
        .refill-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: var(--warning-light);
            color: var(--warning);
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background-color: var(--bg-light);
            border-radius: 12px;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-light);
        }
        
        .empty-title {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        
        .filter-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active {
            background-color: var(--primary);
            color: white;
        }
        
        .filter-tab:not(.active) {
            background-color: var(--bg-light);
            color: var(--text-muted);
        }
        
        .filter-tab:not(.active):hover {
            background-color: var(--primary-lightest);
            color: var(--primary);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .alert-success {
            background-color: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success-lighter);
        }
        
        .alert-error {
            background-color: var(--danger-light);
            color: var(--danger);
            border: 1px solid var(--danger-lighter);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    
    <div class="teacher-dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1><i class="fas fa-prescription"></i> My Prescriptions</h1>
                <p>View and manage all your prescriptions and medication information.</p>
            </div>
            <div class="header-actions">
                <a href="/medical/src/modules/dashboard/patient/teacher/appointment.php" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> Schedule Appointment
                </a>
            </div>
        </div>
        
        <!-- Filter Controls -->
        <div class="filter-controls">
            <div class="filter-tabs">
                <div class="filter-tab active" data-filter="all">All Prescriptions</div>
                <div class="filter-tab" data-filter="active">Active</div>
                <div class="filter-tab" data-filter="expired">Expired</div>
            </div>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="prescription-search" placeholder="Search prescriptions...">
            </div>
        </div>
        
        <!-- Prescriptions List -->
        <div class="prescription-list">
            <?php if (count($prescriptions) > 0): ?>
                <?php foreach ($prescriptions as $index => $prescription): ?>
                    <?php 
                    $isActive = strtotime($prescription['expiry_date']) > time();
                    $status = $isActive ? 'active' : 'expired';
                    ?>
                    <div class="prescription-card animate-in filter-item <?= $status ?>" style="animation-delay: <?= 0.1 * $index ?>s;">
                        <div class="prescription-header">
                            <div class="prescription-title">
                                <div class="prescription-icon">
                                    <i class="fas fa-prescription-bottle-alt"></i>
                                </div>
                                <h3><?= htmlspecialchars($prescription['prescription_name'] ?? 'Prescription #' . $prescription['prescription_id']) ?></h3>
                            </div>
                            <div class="prescription-status status-<?= $status ?>">
                                <?= $isActive ? 'Active' : 'Expired' ?>
                            </div>
                        </div>
                        <div class="prescription-body">
                            <div class="prescription-meta">
                                <div class="doctor-info">
                                    <img src="<?= !empty($prescription['profile_image']) ? htmlspecialchars($prescription['profile_image']) : '/medical/assets/img/default-avatar.png' ?>" 
                                         alt="Doctor" class="doctor-avatar">
                                    <span>Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></span>
                                </div>
                                <div class="prescription-date">
                                    <span><i class="fas fa-calendar-alt"></i> Prescribed: <?= date('M d, Y', strtotime($prescription['created_at'])) ?></span>
                                    <span class="mx-2">|</span>
                                    <span><i class="fas fa-calendar-times"></i> Expires: <?= date('M d, Y', strtotime($prescription['expiry_date'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="prescription-notes">
                                <h4>Doctor's Notes:</h4>
                                <p><?= htmlspecialchars($prescription['notes'] ?? 'No specific notes provided.') ?></p>
                            </div>
                            
                            <div class="medication-list">
                                <h4>Prescribed Medications:</h4>
                                <?php if (!empty($prescription['medications'])): ?>
                                    <?php foreach ($prescription['medications'] as $medication): ?>
                                        <div class="medication-item">
                                            <div class="medication-name"><?= htmlspecialchars($medication['medication_name']) ?></div>
                                            <div class="medication-details">
                                                <div class="medication-detail">
                                                    <i class="fas fa-pills"></i>
                                                    <span>Dosage: <?= htmlspecialchars($medication['dosage']) ?></span>
                                                </div>
                                                <div class="medication-detail">
                                                    <i class="fas fa-clock"></i>
                                                    <span>Frequency: <?= htmlspecialchars($medication['frequency']) ?></span>
                                                </div>
                                                <div class="medication-detail">
                                                    <i class="fas fa-calendar-day"></i>
                                                    <span>Duration: <?= htmlspecialchars($medication['duration'] ?? 'As directed') ?></span>
                                                </div>
                                            </div>
                                            <?php if (!empty($medication['instructions'])): ?>
                                                <div class="medication-instructions mt-2">
                                                    <i class="fas fa-info-circle"></i>
                                                    <span><?= htmlspecialchars($medication['instructions']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="medication-item">
                                        <p>No medication details available.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($isActive): ?>
                                <div class="prescription-actions">
                                    <a href="/medical/src/modules/prescriptions/download.php?id=<?= $prescription['prescription_id'] ?>" class="btn btn-secondary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-prescription-bottle"></i>
                    <h4 class="empty-title">No Prescriptions Found</h4>
                    <p class="empty-description">You don't have any prescriptions in your record. Schedule an appointment with a doctor to discuss your health needs.</p>
                    <a href="/medical/src/modules/dashboard/patient/teacher/appointment.php" class="btn btn-primary mt-3">
                        <i class="fas fa-calendar-plus"></i> Schedule Appointment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter functionality
        const filterTabs = document.querySelectorAll('.filter-tab');
        const filterItems = document.querySelectorAll('.filter-item');
        
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                filterTabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                
                // Show/hide items based on filter
                filterItems.forEach(item => {
                    if (filter === 'all' || item.classList.contains(filter)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('prescription-search');
        
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            filterItems.forEach(item => {
                const prescriptionName = item.querySelector('.prescription-title h3').textContent.toLowerCase();
                const medicationNames = Array.from(item.querySelectorAll('.medication-name'))
                                          .map(el => el.textContent.toLowerCase());
                
                // Check if search term is in prescription name or any medication names
                const matchPrescription = prescriptionName.includes(searchTerm);
                const matchMedication = medicationNames.some(name => name.includes(searchTerm));
                
                if (matchPrescription || matchMedication) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    </script>
</body>
</html>