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

// Get user's health record summary
$healthRecordQuery = "SELECT s.blood_type, mh.condition_name as medical_conditions, a.allergy_name as allergies, 
                      CONCAT(s.date_of_birth, ' ', s.gender) as demographic_data, mh.updated_at
                      FROM staff s 
                      LEFT JOIN medical_history mh ON s.user_id = mh.user_id 
                      LEFT JOIN allergies a ON s.user_id = a.user_id
                      WHERE s.user_id = ?
                      LIMIT 1";
$healthRecordStmt = $conn->prepare($healthRecordQuery);
$healthRecordStmt->bind_param("i", $user_id);
$healthRecordStmt->execute();
$healthRecord = $healthRecordStmt->get_result()->fetch_assoc();

// Get upcoming appointments
$appointmentsQuery = "SELECT c.*, u.first_name, u.last_name, u.profile_image 
                     FROM consultations c
                     JOIN users u ON c.doctor_id = u.user_id
                     WHERE c.patient_id = ? AND c.consultation_date >= CURDATE() AND c.status != 'Cancelled'
                     ORDER BY c.consultation_date ASC
                     LIMIT 5";
$appointmentsStmt = $conn->prepare($appointmentsQuery);
$appointmentsStmt->bind_param("i", $user_id);
$appointmentsStmt->execute();
$upcomingAppointments = $appointmentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent prescriptions
$prescriptionsQuery = "SELECT p.*, u.first_name, u.last_name, m.name as medication_name, 
                      pi.dosage, pi.frequency as dosage_instructions
                      FROM prescriptions p
                      JOIN users u ON p.doctor_id = u.user_id
                      LEFT JOIN prescription_items pi ON p.prescription_id = pi.prescription_id
                      LEFT JOIN medications m ON pi.medication_id = m.medication_id
                      WHERE p.user_id = ?
                      ORDER BY p.created_at DESC
                      LIMIT 3";
$prescriptionsStmt = $conn->prepare($prescriptionsQuery);
$prescriptionsStmt->bind_param("i", $user_id);
$prescriptionsStmt->execute();
$recentPrescriptions = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get available doctors for appointment booking
$doctorsQuery = "SELECT u.user_id, u.first_name, u.last_name, u.profile_image, d.specialization as specialty
                FROM users u
                JOIN doctors d ON u.user_id = d.user_id
                JOIN roles r ON u.role_id = r.role_id
                WHERE r.role_name = 'Doctor'
                ORDER BY u.last_name ASC";
$doctors = $conn->query($doctorsQuery)->fetch_all(MYSQLI_ASSOC);

// Get health announcements specific to staff
$announcementsQuery = "SELECT * FROM first_aid_tips 
                      WHERE keywords LIKE '%staff%' OR keywords LIKE '%all%'
                      ORDER BY created_at DESC
                      LIMIT 3";
$healthAnnouncements = $conn->query($announcementsQuery)->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Health Dashboard - MedMS</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr for date selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css"> 
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="../staff/styles/staff.css">
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    <br><br><br>
    <div class="staff-dashboard">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>Welcome, <?= htmlspecialchars($user['first_name'] ?? ($_SESSION['full_name'] ?? $_SESSION['username'])); ?>!</h1>
                <p>Access your health information, manage appointments, and stay updated with school health resources.</p>
            </div>
            <div class="welcome-image">
                <img src="/medical/assets/img/staff-health.svg" alt="Staff Health" onerror="this.src='/medical/assets/img/default-banner.png'">
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card animate-in" style="animation-delay: 0.1s;">
                <div class="stat-header">
                    <h4 class="stat-title">Health Status</h4>
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
                <h2 class="stat-value">Good</h2>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Overall Wellness</span>
                </div>
            </div>
            
            <div class="stat-card animate-in" style="animation-delay: 0.2s;">
                <div class="stat-header">
                    <h4 class="stat-title">Upcoming Appointments</h4>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?= count($upcomingAppointments) ?></h2>
                <div class="stat-change">
                    <i class="fas fa-calendar"></i>
                    <span>Next: <?= !empty($upcomingAppointments) ? date('M d, Y', strtotime($upcomingAppointments[0]['consultation_date'])) : 'None' ?></span>
                </div>
            </div>
            
            <div class="stat-card animate-in" style="animation-delay: 0.3s;">
                <div class="stat-header">
                    <h4 class="stat-title">Active Prescriptions</h4>
                    <div class="stat-icon">
                        <i class="fas fa-prescription"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?= count($recentPrescriptions) ?></h2>
                <div class="stat-change">
                    <i class="fas fa-pills"></i>
                    <span>Current Medications</span>
                </div>
            </div>
            
            <div class="stat-card animate-in" style="animation-delay: 0.4s;">
                <div class="stat-header">
                    <h4 class="stat-title">Staff Wellness</h4>
                    <div class="stat-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
                <h2 class="stat-value">Active</h2>
                <div class="stat-change positive">
                    <i class="fas fa-check-circle"></i>
                    <span>Program Enrolled</span>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="dashboard-column">
                <!-- Health Announcements -->
                <div class="announcements-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-bullhorn"></i>
                            Staff Health Announcements
                        </h3>
                        <a href="/medical/src/modules/announcements/list.php" class="btn btn-primary">View All</a>
                    </div>
                    
                    <?php if (count($healthAnnouncements) > 0): ?>
                        <?php foreach ($healthAnnouncements as $announcement): ?>
                            <div class="announcement-item">
                                <div class="announcement-header">
                                    <h4 class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></h4>
                                    <span class="announcement-level level-<?= strtolower($announcement['emergency_level']) ?>">
                                        <?= htmlspecialchars($announcement['emergency_level']) ?>
                                    </span>
                                </div>
                                <p class="announcement-content"><?= htmlspecialchars($announcement['description']) ?></p>
                                <span class="announcement-date">Posted: <?= date('M j, Y', strtotime($announcement['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-info-circle"></i>
                            <h4 class="empty-title">No Announcements</h4>
                            <p class="empty-description">There are no current health announcements for staff members.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Upcoming Appointments -->
                <div class="appointments-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-alt"></i>
                            Upcoming Appointments
                        </h3>
                        <a href="/medical/src/modules/appointment/book.php" class="btn btn-primary">Book New</a>
                    </div>
                    
                    <div class="appointments-list">
                        <?php if (!empty($upcomingAppointments)): ?>
                            <?php foreach ($upcomingAppointments as $index => $appointment): ?>
                                <div class="appointment-item animate-in" style="animation-delay: <?= 0.1 + ($index * 0.1) ?>s;">
                                    <div class="appointment-time">
                                        <span class="time-day"><?= date('d', strtotime($appointment['consultation_date'])) ?></span>
                                        <span class="time-month"><?= date('M', strtotime($appointment['consultation_date'])) ?></span>
                                        <span class="time-hour"><?= date('h:i A', strtotime($appointment['consultation_date'])) ?></span>
                                    </div>
                                    <div class="appointment-content">
                                        <h4 class="appointment-title"><?= htmlspecialchars($appointment['chief_complaint'] ?? 'Regular Checkup') ?></h4>
                                        <p class="appointment-doctor">With Dr. <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></p>
                                        <span class="appointment-status <?= strtolower($appointment['status']) ?>">
                                            <?= htmlspecialchars($appointment['status']) ?>
                                        </span>
                                    </div>
                                    <a href="/medical/src/modules/appointment/view.php?id=<?= $appointment['consultation_id'] ?>" class="btn btn-sm btn-primary">Details</a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="appointment-item empty">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h4 class="empty-title">No Upcoming Appointments</h4>
                                    <p class="empty-description">You don't have any scheduled appointments.</p>
                                    <a href="/medical/src/modules/appointment/book.php" class="btn btn-primary">Book Now</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="dashboard-column">
                <!-- Health Record Summary -->
                <div class="health-record-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-file-medical"></i>
                            Health Record Summary
                        </h3>
                        <a href="/medical/src/modules/health-records/viewStaff.php" class="btn btn-primary">View All</a>
                    </div>
                    
                    <div class="health-record-content">
                        <div class="health-info">
                            <div class="info-item animate-in" style="animation-delay: 0.1s;">
                                <div class="info-icon"><i class="fas fa-tint"></i></div>
                                <div class="info-details">
                                    <span class="info-label">Blood Type</span>
                                    <span class="info-value"><?= $healthRecord['blood_type'] ?? 'Not recorded' ?></span>
                                </div>
                            </div>
                            
                            <div class="info-item animate-in" style="animation-delay: 0.2s;">
                                <div class="info-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                <div class="info-details">
                                    <span class="info-label">Allergies</span>
                                    <span class="info-value"><?= $healthRecord['allergies'] ?? 'None recorded' ?></span>
                                </div>
                            </div>
                            
                            <div class="info-item animate-in" style="animation-delay: 0.3s;">
                                <div class="info-icon"><i class="fas fa-notes-medical"></i></div>
                                <div class="info-details">
                                    <span class="info-label">Medical Conditions</span>
                                    <span class="info-value"><?= $healthRecord['medical_conditions'] ?? 'None recorded' ?></span>
                                </div>
                            </div>
                            
                            <div class="info-item animate-in" style="animation-delay: 0.4s;">
                                <div class="info-icon"><i class="fas fa-sync-alt"></i></div>
                                <div class="info-details">
                                    <span class="info-label">Last Updated</span>
                                    <span class="info-value"><?= $healthRecord['updated_at'] ? date('M j, Y', strtotime($healthRecord['updated_at'])) : 'Not available' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Prescriptions -->
                <div class="prescriptions-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-prescription"></i>
                            Recent Prescriptions
                        </h3>
                        <a href="/medical/src/modules/prescription/list.php" class="btn btn-primary">View All</a>
                    </div>
                    
                    <div class="prescriptions-list">
                        <?php if (!empty($recentPrescriptions)): ?>
                            <?php foreach ($recentPrescriptions as $index => $prescription): ?>
                                <div class="prescription-item animate-in" style="animation-delay: <?= 0.1 + ($index * 0.1) ?>s;">
                                    <div class="prescription-date">
                                        <span class="date-day"><?= date('d', strtotime($prescription['issue_date'])) ?></span>
                                        <span class="date-month"><?= date('M', strtotime($prescription['issue_date'])) ?></span>
                                    </div>
                                    <div class="prescription-content">
                                        <h4 class="prescription-title"><?= htmlspecialchars($prescription['medication_name'] ?? 'Multiple Medications') ?></h4>
                                        <p class="prescription-doctor">Prescribed by Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></p>
                                        <span class="prescription-status active">Active</span>
                                    </div>
                                    <a href="/medical/src/modules/prescription/view.php?id=<?= $prescription['prescription_id'] ?>" class="btn btn-sm btn-primary">Details</a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="prescription-item empty">
                                <div class="empty-state">
                                    <i class="fas fa-prescription-bottle"></i>
                                    <h4 class="empty-title">No Recent Prescriptions</h4>
                                    <p class="empty-description">You don't have any recent prescriptions.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-bolt"></i>
                            Quick Actions
                        </h3>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="/medical/src/modules/appointment/book.php" class="action-card animate-in" style="animation-delay: 0.1s;">
                            <div class="action-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <h4 class="action-title">Book Appointment</h4>
                            <p class="action-description">Schedule a medical consultation</p>
                        </a>
                        
                        <a href="/medical/src/modules/consultation/request.php" class="action-card animate-in" style="animation-delay: 0.2s;">
                            <div class="action-icon">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                            <h4 class="action-title">Request Consultation</h4>
                            <p class="action-description">Request urgent medical advice</p>
                        </a>
                        
                        <a href="/medical/src/modules/health-records/update.php" class="action-card animate-in" style="animation-delay: 0.3s;">
                            <div class="action-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <h4 class="action-title">Update Health Info</h4>
                            <p class="action-description">Update your health information</p>
                        </a>
                        
                        <a href="/medical/src/modules/emergency/contacts.php" class="action-card animate-in" style="animation-delay: 0.4s;">
                            <div class="action-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <h4 class="action-title">Emergency Contacts</h4>
                            <p class="action-description">View emergency contact information</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flatpickr JS for date picking -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation classes
            const animateItems = document.querySelectorAll('.animate-in');
            animateItems.forEach(item => {
                item.classList.add('show');
            });
        });
    </script>
</body>
</html>