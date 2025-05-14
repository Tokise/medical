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

// Get user's medical history
$historyQuery = "SELECT mh.*, u.first_name, u.last_name
                FROM medical_history mh
                JOIN users u ON mh.doctor_id = u.user_id
                WHERE mh.user_id = ?
                ORDER BY mh.created_at DESC";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("i", $user_id);
$historyStmt->execute();
$medicalHistory = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get consultation history
$consultationsQuery = "SELECT c.*, u.first_name, u.last_name, u.profile_image
                      FROM consultations c
                      JOIN users u ON c.doctor_id = u.user_id
                      WHERE c.patient_id = ?
                      ORDER BY c.consultation_date DESC";
$consultationsStmt = $conn->prepare($consultationsQuery);
$consultationsStmt->bind_param("i", $user_id);
$consultationsStmt->execute();
$consultationHistory = $consultationsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get prescription history
$prescriptionsQuery = "SELECT p.*, u.first_name, u.last_name, GROUP_CONCAT(m.name SEPARATOR ', ') as medications
                      FROM prescriptions p
                      JOIN users u ON p.doctor_id = u.user_id
                      LEFT JOIN prescription_items pi ON p.prescription_id = pi.prescription_id
                      LEFT JOIN medications m ON pi.medication_id = m.medication_id
                      WHERE p.user_id = ?
                      GROUP BY p.prescription_id
                      ORDER BY p.created_at DESC";
$prescriptionsStmt = $conn->prepare($prescriptionsQuery);
$prescriptionsStmt->bind_param("i", $user_id);
$prescriptionsStmt->execute();
$prescriptionHistory = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get vaccination history
$vaccinationHistory = array();
// Check if vaccinations table exists before querying
$tableCheckQuery = "SHOW TABLES LIKE 'vaccinations'";
$tableExists = $conn->query($tableCheckQuery)->num_rows > 0;

if ($tableExists) {
    $vaccinationQuery = "SELECT v.*, vt.name as vaccine_name
                        FROM vaccinations v
                        JOIN vaccine_types vt ON v.vaccine_type_id = vt.vaccine_type_id
                        WHERE v.user_id = ?
                        ORDER BY v.vaccination_date DESC";
    $vaccinationStmt = $conn->prepare($vaccinationQuery);
    $vaccinationStmt->bind_param("i", $user_id);
    $vaccinationStmt->execute();
    $vaccinationHistory = $vaccinationStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Apply filters if submitted
$active_filter = 'all'; // Default view is all records
$start_date = '';
$end_date = '';

if (isset($_GET['filter'])) {
    $active_filter = $_GET['filter'];
    
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $start_date = $_GET['start_date'];
    }
    
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $end_date = $_GET['end_date'];
    }
    
    // Apply date filters if provided
    if (!empty($start_date) && !empty($end_date)) {
        // Filter consultations
        if ($active_filter == 'all' || $active_filter == 'consultations') {
            $consultationsQuery = "SELECT c.*, u.first_name, u.last_name, u.profile_image
                              FROM consultations c
                              JOIN users u ON c.doctor_id = u.user_id
                              WHERE c.patient_id = ? AND c.consultation_date BETWEEN ? AND ?
                              ORDER BY c.consultation_date DESC";
            $consultationsStmt = $conn->prepare($consultationsQuery);
            $consultationsStmt->bind_param("iss", $user_id, $start_date, $end_date);
            $consultationsStmt->execute();
            $consultationHistory = $consultationsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Filter prescriptions
        if ($active_filter == 'all' || $active_filter == 'prescriptions') {
            $prescriptionsQuery = "SELECT p.*, u.first_name, u.last_name, GROUP_CONCAT(m.name SEPARATOR ', ') as medications
                              FROM prescriptions p
                              JOIN users u ON p.doctor_id = u.user_id
                              LEFT JOIN prescription_items pi ON p.prescription_id = pi.prescription_id
                              LEFT JOIN medications m ON pi.medication_id = m.medication_id
                              WHERE p.user_id = ? AND p.issue_date BETWEEN ? AND ?
                              GROUP BY p.prescription_id
                              ORDER BY p.created_at DESC";
            $prescriptionsStmt = $conn->prepare($prescriptionsQuery);
            $prescriptionsStmt->bind_param("iss", $user_id, $start_date, $end_date);
            $prescriptionsStmt->execute();
            $prescriptionHistory = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Filter vaccinations - only if the table exists
        if (($active_filter == 'all' || $active_filter == 'vaccinations') && $tableExists) {
            $vaccinationQuery = "SELECT v.*, vt.name as vaccine_name
                            FROM vaccinations v
                            JOIN vaccine_types vt ON v.vaccine_type_id = vt.vaccine_type_id
                            WHERE v.user_id = ? AND v.vaccination_date BETWEEN ? AND ?
                            ORDER BY v.vaccination_date DESC";
            $vaccinationStmt = $conn->prepare($vaccinationQuery);
            $vaccinationStmt->bind_param("iss", $user_id, $start_date, $end_date);
            $vaccinationStmt->execute();
            $vaccinationHistory = $vaccinationStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History - Staff Portal - MedMS</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr for date selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css"> 
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="../staff/styles/staff.css">
    <style>
        /* Medical History Styles */

/* Layout and General Styles */
.staff-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.page-header {
  margin-bottom: 30px;
  border-bottom: 2px solid var(--primary-color);
  padding-bottom: 15px;
}

.page-header h1 {
  font-size: 2.2rem;
  color: var(--primary-color);
  display: flex;
  align-items: center;
  gap: 10px;
}

.page-header p {
  color: var(--text-color-secondary);
  margin-top: 5px;
}

/* Filter Section */
.filter-section {
  background-color: var(--bg-color-light);
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 30px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.filter-form {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  align-items: flex-end;
}

.filter-group {
  flex: 1;
  min-width: 150px;
}

.filter-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: var(--text-color);
}

.form-select, .form-input {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid var(--border-color);
  font-size: 1rem;
}

.date-input {
  cursor: pointer;
}

/* Tabs */
.history-tabs {
  margin-bottom: 30px;
}

.tab-header {
  display: flex;
  border-bottom: 2px solid var(--border-color);
  margin-bottom: 20px;
  overflow-x: auto;
}

.tab-btn {
  padding: 12px 20px;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-weight: 600;
  color: var(--text-color-secondary);
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.tab-btn:hover {
  color: var(--primary-color);
}

.tab-btn.active {
  color: var(--primary-color);
  border-bottom-color: var(--primary-color);
}

.tab-btn i {
  font-size: 1.1rem;
}

.tab-content {
  min-height: 300px;
}

.tab-pane {
  display: none;
}

.tab-pane.active {
  display: block;
  animation: fadeIn 0.5s ease;
}

/* History Items */
.history-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.history-item {
  display: flex;
  background-color: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-left: 4px solid var(--primary-color);
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.5s ease;
}

.history-item.show {
  opacity: 1;
  transform: translateY(0);
}

.history-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 80px;
  padding-right: 20px;
  border-right: 1px solid var(--border-color);
  margin-right: 20px;
}

.date-day {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--primary-color);
}

.date-month {
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-top: -5px;
}

.date-year {
  font-size: 0.8rem;
  color: var(--text-color-secondary);
}

.history-content {
  flex: 1;
}

.history-title {
  font-size: 1.2rem;
  margin-bottom: 5px;
  color: var(--text-color);
}

.history-doctor,
.history-provider {
  color: var(--text-color-secondary);
  font-size: 0.95rem;
  margin-bottom: 10px;
}

.history-details {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 10px;
  font-size: 0.9rem;
}

.history-details span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.history-notes {
  margin-top: 10px;
  font-style: italic;
  color: var(--text-color-secondary);
  background-color: var(--bg-color-light);
  padding: 10px;
  border-radius: 6px;
  font-size: 0.9rem;
}

.history-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Status Indicators */
.history-status {
  padding: 4px 10px;
  border-radius: 20px;
  font-weight: 500;
  font-size: 0.8rem;
  text-transform: uppercase;
}

.history-status.completed,
.history-status.resolved {
  background-color: rgba(40, 167, 69, 0.1);
  color: #28a745;
}

.history-status.pending {
  background-color: rgba(255, 193, 7, 0.1);
  color: #ffc107;
}

.history-status.active {
  background-color: rgba(0, 123, 255, 0.1);
  color: #007bff;
}

.history-status.cancelled {
  background-color: rgba(220, 53, 69, 0.1);
  color: #dc3545;
}

/* Status Icons */
.history-status-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-right: 20px;
}

.text-warning {
  color: #ffc107;
}

.text-success {
  color: #28a745;
}

.text-info {
  color: #17a2b8;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 50px 20px;
  background-color: var(--bg-color-light);
  border-radius: 8px;
  color: var(--text-color-secondary);
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 20px;
  color: var(--border-color);
}

.empty-title {
  font-size: 1.5rem;
  margin-bottom: 10px;
  color: var(--text-color);
}

.empty-description {
  margin-bottom: 20px;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

/* Export Section */
.export-section {
  background-color: var(--bg-color-light);
  border-radius: 8px;
  padding: 20px;
  margin-top: 40px;
}

.export-section h3 {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  color: var(--text-color);
}

.export-options {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 20px;
}

/* Buttons */
.btn {
  padding: 10px 15px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  border: none;
  text-decoration: none;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.9rem;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: var(--primary-color-dark);
}

.btn-secondary {
  background-color: var(--secondary-color);
  color: white;
}

.btn-secondary:hover {
  background-color: var(--secondary-color-dark);
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .filter-form {
    flex-direction: column;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .history-item {
    flex-direction: column;
  }
  
  .history-date {
    flex-direction: row;
    border-right: none;
    border-bottom: 1px solid var(--border-color);
    padding-right: 0;
    padding-bottom: 15px;
    margin-right: 0;
    margin-bottom: 15px;
    gap: 10px;
  }
  
  .history-actions {
    margin-top: 15px;
    justify-content: flex-end;
  }
  
  .tab-header {
    overflow-x: auto;
  }
}
    </style>
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    <br><br><br>
    <div class="staff-dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-history"></i> Medical History</h1>
            <p>View and manage your complete medical history records</p>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form action="" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="filter">View:</label>
                    <select name="filter" id="filter" class="form-select">
                        <option value="all" <?= $active_filter == 'all' ? 'selected' : '' ?>>All Records</option>
                        <option value="consultations" <?= $active_filter == 'consultations' ? 'selected' : '' ?>>Consultations</option>
                        <option value="prescriptions" <?= $active_filter == 'prescriptions' ? 'selected' : '' ?>>Prescriptions</option>
                        <option value="vaccinations" <?= $active_filter == 'vaccinations' ? 'selected' : '' ?>>Vaccinations</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="start_date">From:</label>
                    <input type="date" id="start_date" name="start_date" class="form-input date-input" value="<?= $start_date ?>">
                </div>
                
                <div class="filter-group">
                    <label for="end_date">To:</label>
                    <input type="date" id="end_date" name="end_date" class="form-input date-input" value="<?= $end_date ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <a href="history.php" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Reset
                </a>
            </form>
        </div>
        
        <!-- History Tabs -->
        <div class="history-tabs">
            <div class="tab-header">
                <button class="tab-btn active" data-tab="consultations">
                    <i class="fas fa-stethoscope"></i> Consultations
                </button>
                <button class="tab-btn" data-tab="prescriptions">
                    <i class="fas fa-prescription"></i> Prescriptions
                </button>
                <button class="tab-btn" data-tab="vaccinations">
                    <i class="fas fa-syringe"></i> Vaccinations
                </button>
                <button class="tab-btn" data-tab="conditions">
                    <i class="fas fa-heartbeat"></i> Medical Conditions
                </button>
            </div>
            
            <div class="tab-content">
                <!-- Consultations Tab -->
                <div class="tab-pane active" id="consultations">
                    <div class="history-list">
                        <?php if (!empty($consultationHistory)): ?>
                            <?php foreach ($consultationHistory as $index => $consultation): ?>
                                <div class="history-item animate-in" style="animation-delay: <?= 0.1 + ($index * 0.1) ?>s;">
                                    <div class="history-date">
                                        <span class="date-day"><?= date('d', strtotime($consultation['consultation_date'])) ?></span>
                                        <span class="date-month"><?= date('M', strtotime($consultation['consultation_date'])) ?></span>
                                        <span class="date-year"><?= date('Y', strtotime($consultation['consultation_date'])) ?></span>
                                    </div>
                                    <div class="history-content">
                                        <h4 class="history-title"><?= htmlspecialchars($consultation['chief_complaint'] ?? 'Medical Consultation') ?></h4>
                                        <p class="history-doctor">With Dr. <?= htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']) ?></p>
                                        <div class="history-details">
                                            <span class="history-status <?= strtolower($consultation['status']) ?>">
                                                <?= htmlspecialchars($consultation['status']) ?>
                                            </span>
                                            <?php if (!empty($consultation['diagnosis'])): ?>
                                                <span class="history-diagnosis">
                                                    <i class="fas fa-clipboard-check"></i> 
                                                    Diagnosis: <?= htmlspecialchars($consultation['diagnosis']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="history-actions">
                                        <a href="/medical/src/modules/consultation/view.php?id=<?= $consultation['consultation_id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <h4 class="empty-title">No Consultation Records</h4>
                                <p class="empty-description">You don't have any consultation records in the system.</p>
                                <a href="/medical/src/modules/appointment/book.php" class="btn btn-primary">Book a Consultation</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Prescriptions Tab -->
                <div class="tab-pane" id="prescriptions">
                    <div class="history-list">
                        <?php if (!empty($prescriptionHistory)): ?>
                            <?php foreach ($prescriptionHistory as $index => $prescription): ?>
                                <div class="history-item animate-in" style="animation-delay: <?= 0.1 + ($index * 0.1) ?>s;">
                                    <div class="history-date">
                                        <span class="date-day"><?= date('d', strtotime($prescription['issue_date'])) ?></span>
                                        <span class="date-month"><?= date('M', strtotime($prescription['issue_date'])) ?></span>
                                        <span class="date-year"><?= date('Y', strtotime($prescription['issue_date'])) ?></span>
                                    </div>
                                    <div class="history-content">
                                        <h4 class="history-title">
                                            <?= htmlspecialchars($prescription['medications'] ?? 'Prescription') ?>
                                        </h4>
                                        <p class="history-doctor">Prescribed by Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></p>
                                        <div class="history-details">
                                            <span class="history-status active">
                                                <?= strtotime($prescription['end_date']) > time() ? 'Active' : 'Completed' ?>
                                            </span>
                                            <span class="history-duration">
                                                <i class="fas fa-calendar-day"></i> 
                                                Valid until: <?= date('M j, Y', strtotime($prescription['end_date'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="history-actions">
                                        <a href="/medical/src/modules/prescription/view.php?id=<?= $prescription['prescription_id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-prescription-bottle"></i>
                                <h4 class="empty-title">No Prescription Records</h4>
                                <p class="empty-description">You don't have any prescription records in the system.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Vaccinations Tab -->
                <div class="tab-pane" id="vaccinations">
                    <div class="history-list">
                        <?php if (!empty($vaccinationHistory)): ?>
                            <?php foreach ($vaccinationHistory as $index => $vaccination): ?>
                                <div class="history-item animate-in" style="animation-delay: <?= 0.1 + ($index * 0.1) ?>s;">
                                    <div class="history-date">
                                        <span class="date-day"><?= date('d', strtotime($vaccination['vaccination_date'])) ?></span>
                                        <span class="date-month"><?= date('M', strtotime($vaccination['vaccination_date'])) ?></span>
                                        <span class="date-year"><?= date('Y', strtotime($vaccination['vaccination_date'])) ?></span>
                                    </div>
                                    <div class="history-content">
                                        <h4 class="history-title"><?= htmlspecialchars($vaccination['vaccine_name']) ?></h4>
                                        <p class="history-provider">Provider: <?= htmlspecialchars($vaccination['provider']) ?></p>
                                        <div class="history-details">
                                            <span class="history-status completed">Administered</span>
                                            <?php if (!empty($vaccination['batch_number'])): ?>
                                                <span class="history-batch">
                                                    <i class="fas fa-vial"></i> 
                                                    Batch: <?= htmlspecialchars($vaccination['batch_number']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($vaccination['next_dose_date'])): ?>
                                                <span class="history-next-dose">
                                                    <i class="fas fa-calendar-plus"></i> 
                                                    Next dose: <?= date('M j, Y', strtotime($vaccination['next_dose_date'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="history-actions">
                                        <a href="/medical/src/modules/vaccination/view.php?id=<?= $vaccination['vaccination_id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-syringe"></i>
                                <h4 class="empty-title">No Vaccination Records</h4>
                                <p class="empty-description">You don't have any vaccination records in the system.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Medical Conditions Tab -->
                <div class="tab-pane" id="conditions">
                    <div class="history-list">
                        <?php if (!empty($medicalHistory)): ?>
                            <?php foreach ($medicalHistory as $index => $condition): ?>
                                <div class="history-item animate-in" style="animation-delay: <?= 0.1 + ($index * 0.1) ?>s;">
                                    <div class="history-status-icon">
                                        <?php if ($condition['status'] === 'Active'): ?>
                                            <i class="fas fa-circle-exclamation text-warning"></i>
                                        <?php elseif ($condition['status'] === 'Resolved'): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-notes-medical text-info"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="history-content">
                                        <h4 class="history-title"><?= htmlspecialchars($condition['condition_name']) ?></h4>
                                        <p class="history-doctor">
                                            <?php if (!empty($condition['first_name'])): ?>
                                                Diagnosed by Dr. <?= htmlspecialchars($condition['first_name'] . ' ' . $condition['last_name']) ?>
                                            <?php else: ?>
                                                Self-reported
                                            <?php endif; ?>
                                        </p>
                                        <div class="history-details">
                                            <span class="history-status <?= strtolower($condition['status']) ?>">
                                                <?= htmlspecialchars($condition['status']) ?>
                                            </span>
                                            <span class="history-date-diagnosed">
                                                <i class="fas fa-calendar-check"></i> 
                                                Diagnosed: <?= date('M j, Y', strtotime($condition['diagnosis_date'])) ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($condition['notes'])): ?>
                                            <p class="history-notes"><?= htmlspecialchars($condition['notes']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-heartbeat"></i>
                                <h4 class="empty-title">No Medical Conditions</h4>
                                <p class="empty-description">You don't have any recorded medical conditions.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="export-section">
            <h3><i class="fas fa-file-export"></i> Export Health Records</h3>
            <p>Download your medical history records for personal records or to share with healthcare providers.</p>
            
            <div class="export-options">
                <a href="/medical/src/modules/health-records/export.php?format=pdf&type=<?= $active_filter ?>" class="btn btn-secondary">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </a>
                <a href="/medical/src/modules/health-records/export.php?format=csv&type=<?= $active_filter ?>" class="btn btn-secondary">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </a>
                <a href="/medical/src/modules/health-records/export.php?format=print&type=<?= $active_filter ?>" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Records
                </a>
            </div>
        </div>
    </div>

    <!-- Flatpickr JS for date picking -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tabs
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and panes
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding pane
                    button.classList.add('active');
                    const tabId = button.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Initialize date pickers
            flatpickr('.date-input', {
                dateFormat: 'Y-m-d',
                allowInput: true
            });
            
            // Add animation classes
            const animateItems = document.querySelectorAll('.animate-in');
            animateItems.forEach(item => {
                item.classList.add('show');
            });
        });
    </script>
</body>
</html>