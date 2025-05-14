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

// Get user's prescriptions with details
$prescriptionsQuery = "SELECT p.*, u.first_name as doctor_first_name, u.last_name as doctor_last_name, 
                      u.profile_image as doctor_image, d.specialization as doctor_specialty,
                      DATE_FORMAT(p.issue_date, '%M %d, %Y') as formatted_issue_date,
                      DATE_FORMAT(p.expiry_date, '%M %d, %Y') as formatted_expiry_date
                      FROM prescriptions p
                      JOIN users u ON p.doctor_id = u.user_id
                      JOIN doctors d ON u.user_id = d.user_id
                      WHERE p.user_id = ?
                      ORDER BY p.issue_date DESC";
$prescriptionsStmt = $conn->prepare($prescriptionsQuery);
$prescriptionsStmt->bind_param("i", $user_id);
$prescriptionsStmt->execute();
$prescriptions = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to get medication details for a prescription
function getMedicationDetails($conn, $prescription_id) {
    $query = "SELECT pi.*, m.name as medication_name, m.description as medication_description 
              FROM prescription_items pi
              JOIN medications m ON pi.medication_id = m.medication_id
              WHERE pi.prescription_id = ?
              ORDER BY pi.order_index ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Process prescription refill request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refill_prescription'])) {
    $prescription_id = $_POST['prescription_id'];
    
    // Create refill request
    $refillQuery = "INSERT INTO prescription_refills (prescription_id, user_id, request_date, status, notes) 
                   VALUES (?, ?, NOW(), 'Pending', ?)";
    $refillStmt = $conn->prepare($refillQuery);
    $notes = $_POST['refill_notes'] ?? '';
    $refillStmt->bind_param("iis", $prescription_id, $user_id, $notes);
    
    if ($refillStmt->execute()) {
        $success_message = "Refill request submitted successfully. The clinic will contact you shortly.";
    } else {
        $error_message = "Failed to submit refill request. Please try again.";
    }
    
    // Redirect to avoid form resubmission
    header("Location: prescription.php?success=" . urlencode($success_message));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions - MedMS</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css"> 
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="styles/teacher.css">
    <style>
        .prescription-container {
            margin-bottom: 2rem;
        }
        
        .prescription-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .prescription-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .prescription-header {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            background-color: var(--primary-color-light);
            border-bottom: 1px solid var(--border-color);
        }
        
        .prescription-header .doctor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 1rem;
            object-fit: cover;
            background-color: #f0f0f0;
        }
        
        .prescription-info {
            flex: 1;
        }
        
        .prescription-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-dark);
        }
        
        .prescription-doctor {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .prescription-status {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: var(--success-bg);
            color: var(--success-color);
        }
        
        .status-expired {
            background-color: var(--danger-bg);
            color: var(--danger-color);
        }
        
        .prescription-body {
            padding: 1.5rem;
        }
        
        .prescription-dates {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed var(--border-color);
        }
        
        .date-item {
            text-align: center;
        }
        
        .date-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .date-value {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .medication-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .medication-item {
            padding: 1rem;
            border-left: 3px solid var(--primary-color);
            background-color: rgba(var(--primary-color-rgb), 0.05);
            border-radius: 0 8px 8px 0;
            margin-bottom: 1rem;
        }
        
        .medication-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .medication-details {
            color: var(--text-body);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
        
        .medication-instructions {
            color: var(--text-dark);
            font-size: 0.9rem;
            background-color: #f9f9f9;
            padding: 0.75rem;
            border-radius: 4px;
            border-left: 3px solid var(--warning-color);
        }
        
        .prescription-footer {
            padding: 1rem 1.5rem;
            background-color: var(--bg-light);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .prescription-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .prescription-notes {
            background-color: rgba(var(--warning-color-rgb), 0.1);
            border-left: 3px solid var(--warning-color);
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 0 8px 8px 0;
        }
        
        .notes-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background-color: var(--bg-light);
            border-radius: 12px;
            margin: 2rem 0;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        .empty-title {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .empty-description {
            color: var(--text-muted);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .refill-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            width: 500px;
            max-width: 90%;
            box-shadow: var(--card-shadow);
            animation: modalFadeIn 0.3s ease;
        }
        
        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-color);
            border: 1px solid rgba(var(--success-color-rgb), 0.3);
        }
        
        .alert-error {
            background-color: var(--danger-bg);
            color: var(--danger-color);
            border: 1px solid rgba(var(--danger-color-rgb), 0.3);
        }
    </style>
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    
    <div class="teacher-dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="fas fa-prescription"></i> My Prescriptions
                </h1>
                <p class="page-description">View, download, and request refills for your prescriptions</p>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content-container">
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Prescriptions List -->
            <div class="prescription-container">
                <?php if (count($prescriptions) > 0): ?>
                    <?php foreach ($prescriptions as $index => $prescription): ?>
                        <?php 
                        $medications = getMedicationDetails($conn, $prescription['prescription_id']);
                        $is_active = strtotime($prescription['expiry_date']) > time();
                        ?>
                        <div class="prescription-card animate-in" style="animation-delay: <?= 0.1 * $index ?>s;">
                            <div class="prescription-header">
                                <img 
                                    src="<?= !empty($prescription['doctor_image']) ? htmlspecialchars($prescription['doctor_image']) : '/medical/assets/img/default-avatar.png' ?>" 
                                    alt="Doctor" 
                                    class="doctor-avatar"
                                    onerror="this.src='/medical/assets/img/default-avatar.png'"
                                >
                                <div class="prescription-info">
                                    <h3 class="prescription-title">Prescription #<?= htmlspecialchars($prescription['prescription_id']) ?></h3>
                                    <div class="prescription-doctor">
                                        Dr. <?= htmlspecialchars($prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']) ?> 
                                        (<?= htmlspecialchars($prescription['doctor_specialty']) ?>)
                                    </div>
                                </div>
                                <div class="prescription-status <?= $is_active ? 'status-active' : 'status-expired' ?>">
                                    <?= $is_active ? 'Active' : 'Expired' ?>
                                </div>
                            </div>
                            
                            <div class="prescription-body">
                                <div class="prescription-dates">
                                    <div class="date-item">
                                        <div class="date-label">Issue Date</div>
                                        <div class="date-value"><?= htmlspecialchars($prescription['formatted_issue_date']) ?></div>
                                    </div>
                                    <div class="date-item">
                                        <div class="date-label">Valid Until</div>
                                        <div class="date-value"><?= htmlspecialchars($prescription['formatted_expiry_date']) ?></div>
                                    </div>
                                    <div class="date-item">
                                        <div class="date-label">Refills Remaining</div>
                                        <div class="date-value"><?= htmlspecialchars($prescription['refills_remaining']) ?></div>
                                    </div>
                                </div>
                                
                                <h4><i class="fas fa-pills"></i> Medications (<?= count($medications) ?>)</h4>
                                
                                <ul class="medication-list">
                                    <?php foreach ($medications as $medication): ?>
                                        <li class="medication-item">
                                            <div class="medication-name"><?= htmlspecialchars($medication['medication_name']) ?></div>
                                            <div class="medication-details">
                                                <strong>Dosage:</strong> <?= htmlspecialchars($medication['dosage']) ?>
                                            </div>
                                            <div class="medication-details">
                                                <strong>Frequency:</strong> <?= htmlspecialchars($medication['frequency']) ?>
                                            </div>
                                            <div class="medication-details">
                                                <strong>Duration:</strong> <?= htmlspecialchars($medication['duration']) ?>
                                            </div>
                                            <div class="medication-instructions">
                                                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($medication['instructions'] ?? 'Take as directed by your doctor.') ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <?php if (!empty($prescription['notes'])): ?>
                                <div class="prescription-notes">
                                    <div class="notes-title">
                                        <i class="fas fa-sticky-note"></i> Doctor's Notes
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($prescription['notes'])) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="prescription-footer">
                                <div class="prescription-id">
                                    <span class="text-muted">Issued by School Health Clinic</span>
                                </div>
                                <div class="prescription-actions">
                                    <a href="/medical/src/modules/prescriptions/download.php?id=<?= $prescription['prescription_id'] ?>" class="btn btn-secondary">
                                        <i class="fas fa-download"></i> Download PDF
                                    </a>
                                    
                                    <?php if ($is_active && $prescription['refills_remaining'] > 0): ?>
                                    <button class="btn btn-primary" onclick="openRefillModal(<?= $prescription['prescription_id'] ?>)">
                                        <i class="fas fa-sync-alt"></i> Request Refill
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-prescription-bottle"></i>
                        <h3 class="empty-title">No Prescriptions Found</h3>
                        <p class="empty-description">You don't have any prescriptions in your medical record. If you believe this is an error, please contact the school clinic.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Refill Request Modal -->
    <div id="refillModal" class="refill-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Request Prescription Refill</h3>
                <button class="modal-close" onclick="closeRefillModal()">&times;</button>
            </div>
            <form action="prescription.php" method="POST">
                <input type="hidden" name="prescription_id" id="refill_prescription_id" value="">
                <div class="modal-body">
                    <p>Are you requesting a refill for this prescription? The school clinic will review your request and contact you.</p>
                    <div class="form-group">
                        <label for="refill_notes">Additional Notes (Optional)</label>
                        <textarea id="refill_notes" name="refill_notes" class="form-control" rows="3" placeholder="Any special instructions or information for the clinic staff..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeRefillModal()">Cancel</button>
                    <button type="submit" name="refill_prescription" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Modal functions
    function openRefillModal(prescriptionId) {
        document.getElementById('refillModal').style.display = 'flex';
        document.getElementById('refill_prescription_id').value = prescriptionId;
    }
    
    function closeRefillModal() {
        document.getElementById('refillModal').style.display = 'none';
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('refillModal');
        if (event.target === modal) {
            closeRefillModal();
        }
    }
    
    // Animate in elements
    document.addEventListener('DOMContentLoaded', function() {
        const animateItems = document.querySelectorAll('.prescription-card');
        animateItems.forEach(item => {
            item.classList.add('animate-in');
        });
    });
    </script>

</body>
</html>