<?php
session_start();
require_once '../../../../../config/config.php';


// Check if user is logged in and has student role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get current user data
$user_id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // User not found in database
    session_destroy();
    header("Location: /medical/auth/login.php?error=user_not_found");
    exit;
}

// Get student data - using a try-catch to handle potential errors
try {
    $studentQuery = "SELECT * FROM students WHERE user_id = ?";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->bind_param("i", $user_id);
    $studentStmt->execute();
    $student = $studentStmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    // Log the error but continue execution
    error_log("Error fetching student data: " . $e->getMessage());
    $student = null;
}

// Get health record summary
try {
    $healthQuery = "SELECT * FROM medical_history WHERE user_id = ?";
    $healthStmt = $conn->prepare($healthQuery);
    $healthStmt->bind_param("i", $user_id);
    $healthStmt->execute();
    $healthRecords = $healthStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching health records: " . $e->getMessage());
    $healthRecords = [];
}

// Get allergies
try {
    $allergiesQuery = "SELECT * FROM allergies WHERE user_id = ?";
    $allergiesStmt = $conn->prepare($allergiesQuery);
    $allergiesStmt->bind_param("i", $user_id);
    $allergiesStmt->execute();
    $allergies = $allergiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching allergies: " . $e->getMessage());
    $allergies = [];
}

// Get upcoming appointments - Try with doctors table first
try {
    $appointmentsQuery = "SELECT c.*, u.first_name, u.last_name, d.specialization 
                         FROM consultations c
                         JOIN users u ON c.doctor_id = u.user_id
                         LEFT JOIN doctors d ON u.user_id = d.user_id
                         WHERE c.patient_id = ? AND c.status != 'Cancelled' AND c.consultation_date > NOW()
                         ORDER BY c.consultation_date ASC
                         LIMIT 5";
    $appointmentsStmt = $conn->prepare($appointmentsQuery);
    $appointmentsStmt->bind_param("i", $user_id);
    $appointmentsStmt->execute();
    $appointments = $appointmentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    $appointments = [];
}

// Get recent prescriptions
try {
    $prescriptionsQuery = "SELECT p.*, u.first_name, u.last_name, d.specialization 
                          FROM prescriptions p
                          JOIN users u ON p.doctor_id = u.user_id
                          LEFT JOIN doctors d ON u.user_id = d.user_id
                          WHERE p.user_id = ? 
                          ORDER BY p.issue_date DESC
                          LIMIT 5";
    $prescriptionsStmt = $conn->prepare($prescriptionsQuery);
    $prescriptionsStmt->bind_param("i", $user_id);
    $prescriptionsStmt->execute();
    $prescriptions = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching prescriptions: " . $e->getMessage());
    $prescriptions = [];
}

// Get counts for dashboard stats
try {
    $totalAppointmentsQuery = "SELECT COUNT(*) as count FROM consultations WHERE patient_id = ?";
    $totalAppointmentsStmt = $conn->prepare($totalAppointmentsQuery);
    $totalAppointmentsStmt->bind_param("i", $user_id);
    $totalAppointmentsStmt->execute();
    $totalAppointments = $totalAppointmentsStmt->get_result()->fetch_assoc()['count'];

    $upcomingAppointmentsQuery = "SELECT COUNT(*) as count FROM consultations 
                                 WHERE patient_id = ? AND status != 'Cancelled' AND consultation_date > NOW()";
    $upcomingAppointmentsStmt = $conn->prepare($upcomingAppointmentsQuery);
    $upcomingAppointmentsStmt->bind_param("i", $user_id);
    $upcomingAppointmentsStmt->execute();
    $upcomingAppointments = $upcomingAppointmentsStmt->get_result()->fetch_assoc()['count'];

    $totalPrescriptionsQuery = "SELECT COUNT(*) as count FROM prescriptions WHERE user_id = ?";
    $totalPrescriptionsStmt = $conn->prepare($totalPrescriptionsQuery);
    $totalPrescriptionsStmt->bind_param("i", $user_id);
    $totalPrescriptionsStmt->execute();
    $totalPrescriptions = $totalPrescriptionsStmt->get_result()->fetch_assoc()['count'];
} catch (Exception $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
    $totalAppointments = 0;
    $upcomingAppointments = 0;
    $totalPrescriptions = 0;
}

// Pass the role to be used in the sidebar
$role = 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Medical Management System</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/student/styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
</head>
<body>
    
<?php include_once '../../../../../includes/header.php'; ?>
    <div class="student-dashboard">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>Welcome back, <?= isset($user['first_name']) ? htmlspecialchars($user['first_name']) : 'Student' ?>!</h1>
                <p>Track your health records, appointments, and prescriptions all in one place.</p>
            </div>
            <div class="welcome-image">
                <img src="/medical/assets/img/student-dashboard.svg" alt="Student Dashboard" />
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Total Appointments Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Appointments</span>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $totalAppointments ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>All time</span>
                </div>
                <div class="stat-progress">
                    <div class="progress-bar" style="width: <?= min(100, ($totalAppointments / 10) * 100) ?>%"></div>
                </div>
            </div>

            <!-- Upcoming Appointments Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Upcoming Appointments</span>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $upcomingAppointments ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Scheduled</span>
                </div>
                <div class="stat-progress">
                    <div class="progress-bar" style="width: <?= min(100, ($upcomingAppointments / 5) * 100) ?>%"></div>
                </div>
            </div>

            <!-- Prescriptions Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Prescriptions</span>
                    <div class="stat-icon">
                        <i class="fas fa-prescription-bottle-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $totalPrescriptions ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>All time</span>
                </div>
                <div class="stat-progress">
                    <div class="progress-bar" style="width: <?= min(100, ($totalPrescriptions / 10) * 100) ?>%"></div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="dashboard-column">
                <!-- Health Record Summary Section -->
                <div class="health-record-section">
                    <div class="section-header">
                        <h2 class="section-title">Health Record Summary</h2>
                        <a href="/medical/src/modules/health-records/viewStudent.php" class="btn btn-primary">View Full Record</a>
                    </div>
                    <div class="health-record-content">
                        <div class="health-info">
                            <div class="info-item">
                                <span class="info-label">Blood Type</span>
                                <span class="info-value"><?= $student['blood_type'] ?? 'Not specified' ?></span>
                                <div class="info-icon">
                                    <i class="fas fa-tint"></i>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Medical Conditions</span>
                                <span class="info-value"><?= count($healthRecords) > 0 ? count($healthRecords) . ' conditions' : 'None recorded' ?></span>
                                <div class="info-icon">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Allergies</span>
                                <span class="info-value"><?= count($allergies) > 0 ? count($allergies) . ' allergies' : 'None recorded' ?></span>
                                <div class="info-icon">
                                    <i class="fas fa-allergies"></i>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Emergency Contact</span>
                                <span class="info-value"><?= $student['emergency_contact_name'] ?? 'Not specified' ?></span>
                                <div class="info-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prescriptions Section -->
                <div class="prescriptions-section">
                    <div class="section-header">
                        <h2 class="section-title">Recent Prescriptions</h2>
                        <a href="/medical/src/modules/dashboard/patient/prescriptions/index.php" class="btn btn-primary">View All</a>
                    </div>
                    <div class="prescriptions-list">
                        <?php if (!empty($prescriptions)): ?>
                            <?php foreach ($prescriptions as $prescription): ?>
                                <div class="prescription-item">
                                    <div class="prescription-date">
                                        <span class="date-day"><?= date('d', strtotime($prescription['issue_date'])) ?></span>
                                        <span class="date-month"><?= date('M', strtotime($prescription['issue_date'])) ?></span>
                                    </div>
                                    <div class="prescription-content">
                                        <div class="prescription-title"><?= htmlspecialchars($prescription['diagnosis'] ?? 'Prescription') ?></div>
                                        <div class="prescription-doctor">Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?><?= isset($prescription['specialization']) ? ' (' . htmlspecialchars($prescription['specialization']) . ')' : '' ?></div>
                                    </div>
                                    <div class="prescription-status <?= strtolower($prescription['status']) ?>"><?= $prescription['status'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="prescription-item empty">
                                <div class="empty-state">
                                    <i class="fas fa-prescription-bottle"></i>
                                    <div class="empty-message">
                                        <div class="empty-title">No prescriptions</div>
                                        <div class="empty-description">Your prescriptions will appear here</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="dashboard-column">
                <!-- Appointments Section -->
                <div class="appointments-section">
                    <div class="section-header">
                        <h2 class="section-title">Upcoming Appointments</h2>
                        <a href="/medical/src/modules/dashboard/patient/appointments/schedule.php" class="btn btn-primary">Schedule New</a>
                    </div>
                    <div class="appointments-list">
                        <?php if (!empty($appointments)): ?>
                            <?php foreach ($appointments as $appointment): ?>
                                <div class="appointment-item">
                                    <div class="appointment-time">
                                        <span class="time-day"><?= date('d', strtotime($appointment['consultation_date'])) ?></span>
                                        <span class="time-month"><?= date('M', strtotime($appointment['consultation_date'])) ?></span>
                                        <span class="time-hour"><?= date('h:i A', strtotime($appointment['consultation_date'])) ?></span>
                                    </div>
                                    <div class="appointment-content">
                                        <div class="appointment-title">Dr. <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></div>
                                        <div class="appointment-doctor"><?= htmlspecialchars($appointment['specialization'] ?? 'General Practitioner') ?></div>
                                    </div>
                                    <div class="appointment-status <?= strtolower($appointment['status']) ?>"><?= $appointment['status'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="appointment-item empty">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <div class="empty-message">
                                        <div class="empty-title">No upcoming appointments</div>
                                        <div class="empty-description">Schedule a new appointment to get started</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions Section -->
                <div class="quick-actions-section">
                    <div class="section-header">
                        <h2 class="section-title">Quick Actions</h2>
                    </div>
                    <div class="quick-actions">
                        <a href="/medical/src/modules/dashboard/patient/appointments/schedule.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <h3 class="action-title">Schedule Appointment</h3>
                            <p class="action-description">Book a new consultation</p>
                        </a>

                        <a href="/medical/src/modules/health-records/viewStudent.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-file-medical"></i>
                            </div>
                            <h3 class="action-title">Health Records</h3>
                            <p class="action-description">View your medical history</p>
                        </a>

                        <a href="/medical/src/modules/dashboard/patient/prescriptions/index.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-prescription-bottle-alt"></i>
                            </div>
                            <h3 class="action-title">Prescriptions</h3>
                            <p class="action-description">Manage your medications</p>
                        </a>

                        <a href="/medical/src/modules/dashboard/settings/profile.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <h3 class="action-title">Update Profile</h3>
                            <p class="action-description">Edit your information</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add animation classes after DOM is loaded
        setTimeout(() => {
            document.querySelectorAll('.stat-card').forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('animate-in');
                }, index * 100);
            });
            
            document.querySelectorAll('.info-item').forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('animate-in');
                }, index * 100 + 300);
            });
            
            document.querySelectorAll('.appointment-item, .prescription-item').forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('animate-in');
                }, index * 100 + 500);
            });
            
            document.querySelectorAll('.action-card').forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('animate-in');
                }, index * 100 + 700);
            });
        }, 300);
    });
    </script>
</body>
</html>
