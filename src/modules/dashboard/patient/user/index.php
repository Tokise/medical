<?php
session_start();
require_once '../../../../../config/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'teacher', 'staff'])) {
    header('Location: /medical/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get role-specific profile data
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
} elseif ($role === 'teacher') {
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
} elseif ($role === 'staff') {
    $stmt = $conn->prepare("SELECT * FROM staff WHERE user_id = ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get health record summary (example fields, adjust as needed)
$blood_type = $profile['blood_type'] ?? 'Not specified';
$emergency_contact = $profile['emergency_contact_name'] ?? 'Not specified';

// Get medical conditions
$stmt = $conn->prepare("SELECT * FROM medical_history WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$healthRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get allergies
$stmt = $conn->prepare("SELECT * FROM allergies WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$allergies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent prescriptions
$stmt = $conn->prepare("
    SELECT p.*, u.first_name, u.last_name, d.specialization, pm.patient_status, pm.patient_updated_at
    FROM prescriptions p
    JOIN users u ON p.doctor_id = u.user_id
    LEFT JOIN doctors d ON u.user_id = d.user_id
    JOIN prescription_medications pm ON p.prescription_id = pm.prescription_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent consultations
$stmt = $conn->prepare("
    SELECT c.*, u.first_name, u.last_name, d.specialization, ct.name as consultation_type
    FROM consultations c
    JOIN users u ON c.doctor_id = u.user_id
    LEFT JOIN doctors d ON u.user_id = d.user_id
    JOIN consultation_types ct ON c.consultation_type_id = ct.consultation_type_id
    WHERE c.patient_id = ?
    ORDER BY c.consultation_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get counts for dashboard stats
try {
    // Total consultations
    $totalAppointmentsQuery = "SELECT COUNT(*) as count FROM consultations WHERE patient_id = ?";
    $totalAppointmentsStmt = $conn->prepare($totalAppointmentsQuery);
    $totalAppointmentsStmt->bind_param("i", $user_id);
    $totalAppointmentsStmt->execute();
    $totalAppointments = $totalAppointmentsStmt->get_result()->fetch_assoc()['count'];

    // Completed consultations
    $completedAppointmentsQuery = "SELECT COUNT(*) as count FROM consultations 
                                 WHERE patient_id = ? AND status = 'Completed'";
    $completedAppointmentsStmt = $conn->prepare($completedAppointmentsQuery);
    $completedAppointmentsStmt->bind_param("i", $user_id);
    $completedAppointmentsStmt->execute();
    $completedAppointments = $completedAppointmentsStmt->get_result()->fetch_assoc()['count'];

    // Upcoming consultations (Scheduled or Confirmed)
    $upcomingAppointmentsQuery = "SELECT COUNT(*) as count FROM consultations 
                                 WHERE patient_id = ? AND status IN ('Scheduled', 'Confirmed')";
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
    $completedAppointments = 0;
    $upcomingAppointments = 0;
    $totalPrescriptions = 0;
}

// Pass the role to be used in the sidebar
$role = $_SESSION['role'];
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
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/user/styles/student.css">
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
            <!-- Total Consultations Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Consultations</span>
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

            <!-- Completed Consultations Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Completed Consultations</span>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $completedAppointments ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Done</span>
                </div>
                <div class="stat-progress">
                    <div class="progress-bar" style="width: <?= min(100, ($completedAppointments / 5) * 100) ?>%"></div>
                </div>
            </div>

            <!-- Upcoming Consultations Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Upcoming Consultations</span>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
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
                                <span class="info-value"><?= $blood_type ?></span>
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
                                <span class="info-value"><?= $emergency_contact ?></span>
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
                        <a href="/medical/src/modules/dashboard/patient/user/prescription.php" class="btn btn-primary">View All</a>
                    </div>
                    <div class="prescriptions-list">
                        <?php if (!empty($prescriptions)): ?>
                            <?php foreach ($prescriptions as $prescription): ?>
                                <div class="prescription-item">
                                    <div class="prescription-date">
                                        <span class="date-day"><?= date('d', strtotime($prescription['created_at'])) ?></span>
                                        <span class="date-month"><?= date('M', strtotime($prescription['created_at'])) ?></span>
                                    </div>
                                    <div class="prescription-content">
                                        <div class="prescription-title"><?= htmlspecialchars($prescription['diagnosis'] ?? 'Prescription') ?></div>
                                        <div class="prescription-doctor">Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?><?= isset($prescription['specialization']) ? ' (' . htmlspecialchars($prescription['specialization']) . ')' : '' ?></div>
                                        <div class="specialization"><?= htmlspecialchars($prescription['specialization'] ?? 'General Medicine') ?></div>
                                    </div>
                                    <span class="status-indicator <?= strtolower($prescription['patient_status'] ?? '') ?>"><?= htmlspecialchars($prescription['patient_status'] ?? 'N/A') ?></span>
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
                        <h2 class="section-title">Consultations</h2>
                        <a href="/medical/src/modules/dashboard/patient/student/appointments.php" class="btn btn-primary">Schedule New</a>
                    </div>
                    <div class="appointments-list">
                        <?php if (!empty($consultations)): ?>
                            <?php foreach ($consultations as $consultation): ?>
                                <div class="appointment-item">
                                    <div class="appointment-time">
                                        <span class="time-day"><?= date('d', strtotime($consultation['consultation_date'])) ?></span>
                                        <span class="time-month"><?= date('M', strtotime($consultation['consultation_date'])) ?></span>
                                        <span class="time-hour">
                                            <?php
                                            if (!empty($consultation['consultation_time'])) {
                                                echo date('h:i A', strtotime($consultation['consultation_time']));
                                            } else {
                                                // fallback: extract time from consultation_date
                                                echo date('h:i A', strtotime($consultation['consultation_date']));
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="appointment-content">
                                        <div class="appointment-title"><?= htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']) ?></div>
                                        <div class="appointment-doctor"><?= htmlspecialchars($consultation['specialization'] ?? 'General Practitioner') ?></div>
                                    </div>
                                    <div class="appointment-status <?= strtolower($consultation['status']) ?>"><?= $consultation['status'] ?></div>
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

