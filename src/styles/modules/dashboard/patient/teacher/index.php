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

// Get user's health record summary
$healthRecordQuery = "SELECT t.blood_type, mh.condition_name as medical_conditions, a.allergy_name as allergies, 
                      CONCAT(t.date_of_birth, ' ', t.gender) as demographic_data, mh.updated_at
                      FROM teachers t 
                      LEFT JOIN medical_history mh ON t.user_id = mh.user_id 
                      LEFT JOIN allergies a ON t.user_id = a.user_id
                      WHERE t.user_id = ?
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

// Get health announcements specific to teachers
$announcementsQuery = "SELECT * FROM first_aid_tips 
                      WHERE keywords LIKE '%teacher%' OR keywords LIKE '%all%'
                      ORDER BY created_at DESC
                      LIMIT 3";
$healthAnnouncements = $conn->query($announcementsQuery)->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Health Dashboard - MedMS</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr for date selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css"> 
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="styles/teacher.css">
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    
    <div class="teacher-dashboard">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>Welcome, <?= htmlspecialchars($user['first_name']) ?>!</h1>
                <p>Track your health records, manage appointments, and access faculty wellness programs all in one place.</p>
            </div>
            <div class="welcome-image">
                <img src="/medical/assets/img/teacher-health.svg" alt="Teacher Health" onerror="this.src='/medical/assets/img/default-banner.png'">
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
                    <h4 class="stat-title">Faculty Wellness</h4>
                    <div class="stat-icon">
                        <i class="fas fa-spa"></i>
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
                            Faculty Health Announcements
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
                            <p class="empty-description">There are no current health announcements for faculty members.</p>
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
                        <a href="/medical/src/modules/appointments/book.php" class="btn btn-primary">Book New</a>
                    </div>
                    
                    <div class="appointments-list">
                        <?php if (count($upcomingAppointments) > 0): ?>
                            <?php foreach ($upcomingAppointments as $index => $appointment): ?>
                                <?php 
                                $appointmentDate = new DateTime($appointment['consultation_date']);
                                $status = $appointment['status'];
                                ?>
                                <div class="appointment-item animate-in" style="animation-delay: <?= 0.1 * $index ?>s;">
                                    <div class="appointment-time">
                                        <div class="time-month"><?= $appointmentDate->format('M') ?></div>
                                        <div class="time-day"><?= $appointmentDate->format('d') ?></div>
                                        <div class="time-hour"><?= $appointmentDate->format('h:i A') ?></div>
                                    </div>
                                    <div class="appointment-content">
                                        <h4 class="appointment-title">Consultation with Dr. <?= htmlspecialchars($appointment['last_name']) ?></h4>
                                        <p class="appointment-doctor"><?= htmlspecialchars($appointment['consultation_type'] ?? 'General Checkup') ?></p>
                                    </div>
                                    <div class="appointment-status <?= strtolower($status) ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <h4 class="empty-title">No Upcoming Appointments</h4>
                                <p class="empty-description">You don't have any scheduled appointments.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="dashboard-column">
                <!-- Health Record Section -->
                <div class="health-record-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-notes-medical"></i>
                            Health Record Summary
                        </h3>
                        <a href="/medical/src/modules/health-records/viewTeacher.php" class="btn btn-primary">View Complete</a>
                    </div>
                    
                    <div class="health-record-content">
                        <div class="health-info">
                            <div class="info-item animate-in" style="animation-delay: 0.1s;">
                                <div class="info-label">Blood Type</div>
                                <div class="info-value"><?= !empty($healthRecord['blood_type']) ? htmlspecialchars($healthRecord['blood_type']) : 'Not recorded' ?></div>
                                <div class="info-icon"><i class="fas fa-tint"></i></div>
                            </div>
                            
                            <div class="info-item animate-in" style="animation-delay: 0.2s;">
                                <div class="info-label">Medical Conditions</div>
                                <div class="info-value"><?= !empty($healthRecord['medical_conditions']) ? htmlspecialchars($healthRecord['medical_conditions']) : 'None recorded' ?></div>
                                <div class="info-icon"><i class="fas fa-heartbeat"></i></div>
                            </div>
                            
                            <div class="info-item animate-in" style="animation-delay: 0.3s;">
                                <div class="info-label">Allergies</div>
                                <div class="info-value"><?= !empty($healthRecord['allergies']) ? htmlspecialchars($healthRecord['allergies']) : 'None recorded' ?></div>
                                <div class="info-icon"><i class="fas fa-allergies"></i></div>
                            </div>
                            
                            <div class="info-item animate-in" style="animation-delay: 0.4s;">
                                <div class="info-label">Demographics</div>
                                <div class="info-value"><?= !empty($healthRecord['demographic_data']) ? htmlspecialchars($healthRecord['demographic_data']) : 'Not available' ?></div>
                                <div class="info-icon"><i class="fas fa-id-card"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Faculty Wellness Program -->
                <div class="wellness-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-spa"></i>
                            Faculty Wellness Program
                        </h3>
                        <a href="/medical/src/modules/wellness/teacher-program.php" class="btn btn-primary">Learn More</a>
                    </div>
                    
                    <div class="wellness-content">
                        <div class="wellness-program">
                            <h3>Stress Management Workshop</h3>
                            <p class="wellness-description">Weekly sessions on mindfulness and stress reduction techniques specifically designed for teaching professionals.</p>
                            <div class="wellness-badge">
                                <i class="fas fa-calendar-check"></i>
                                Every Wednesday at 4:00 PM
                            </div>
                        </div>
                        
                        <div class="wellness-program">
                            <h3>Faculty Fitness Group</h3>
                            <p class="wellness-description">Join other faculty members for group exercise sessions tailored to improve physical health and wellness.</p>
                            <div class="wellness-badge">
                                <i class="fas fa-calendar-check"></i>
                                Mondays and Fridays at 5:30 PM
                            </div>
                        </div>
                        
                        <div class="wellness-program">
                            <h3>Work-Life Balance Coaching</h3>
                            <p class="wellness-description">One-on-one sessions with wellness coaches to help establish healthy boundaries and reduce burnout.</p>
                            <div class="wellness-badge">
                                <i class="fas fa-calendar-check"></i>
                                By appointment
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate elements when they come into view
        const animateItems = document.querySelectorAll('.stat-card, .info-item, .appointment-item, .prescription-item');
        animateItems.forEach(item => {
            item.classList.add('animate-in');
        });
        
        // Initialize date picker if needed
        if (typeof flatpickr !== 'undefined') {
            flatpickr(".date-picker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today"
            });
        }
    });
    </script>
    

</body>
</html>
