<?php
session_start();
require_once '../../../../config/db.php';
require_once '../../../includes/header.php';

// Check if user is logged in and has doctor role
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Doctor') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get current user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get medical staff ID for the current doctor
$staffQuery = "SELECT staff_id FROM medical_staff WHERE user_id = ?";
$staffStmt = $conn->prepare($staffQuery);
$staffStmt->bind_param("i", $user_id);
$staffStmt->execute();
$staffResult = $staffStmt->get_result();
$staff = $staffResult->fetch_assoc();

// Check if the doctor has a medical_staff record
if (!$staff) {
    // Create a medical_staff record for this doctor if it doesn't exist
    $insertStaffQuery = "INSERT INTO medical_staff (user_id, specialization, license_number) VALUES (?, 'General Medicine', 'MD-" . rand(10000, 99999) . "')";
    $insertStaffStmt = $conn->prepare($insertStaffQuery);
    $insertStaffStmt->bind_param("i", $user_id);
    
    if ($insertStaffStmt->execute()) {
        // Get the newly created staff_id
        $staff_id = $conn->insert_id;
    } else {
        // If we can't create a medical_staff record, set a default value
        $staff_id = 0;
        $_SESSION['error'] = "Unable to create medical staff record. Some features may be limited.";
    }
} else {
    $staff_id = $staff['staff_id'];
}

// Get counts for dashboard stats
$totalPatientsQuery = "SELECT COUNT(*) as count FROM users u 
                      JOIN roles r ON u.role_id = r.role_id 
                      WHERE r.role_name IN ('Student', 'Teacher')";
$totalPatients = $conn->query($totalPatientsQuery)->fetch_assoc()['count'];

// Only query prescriptions if we have a valid staff_id
$totalPrescriptions = 0;
if ($staff_id > 0) {
    $totalPrescriptionsQuery = "SELECT COUNT(*) as count FROM prescriptions WHERE staff_id = ?";
    $prescriptionStmt = $conn->prepare($totalPrescriptionsQuery);
    $prescriptionStmt->bind_param("i", $staff_id);
    $prescriptionStmt->execute();
    $totalPrescriptions = $prescriptionStmt->get_result()->fetch_assoc()['count'];
}

// Only query appointments if we have a valid staff_id
$todayAppointments = 0;
if ($staff_id > 0) {
    $totalAppointmentsQuery = "SELECT COUNT(*) as count FROM consultations WHERE staff_id = ? AND DATE(consultation_date) = CURDATE()";
    $appointmentStmt = $conn->prepare($totalAppointmentsQuery);
    $appointmentStmt->bind_param("i", $staff_id);
    $appointmentStmt->execute();
    $todayAppointments = $appointmentStmt->get_result()->fetch_assoc()['count'];
}

// Get today's appointments
$todayAppointmentsList = [];
if ($staff_id > 0) {
    $todayQuery = "SELECT c.*, u.first_name, u.last_name, u.school_id 
                  FROM consultations c
                  JOIN users u ON c.patient_id = u.user_id
                  WHERE c.staff_id = ? AND DATE(c.consultation_date) = CURDATE()
                  ORDER BY c.consultation_date ASC";
    $todayStmt = $conn->prepare($todayQuery);
    $todayStmt->bind_param("i", $staff_id);
    $todayStmt->execute();
    $todayAppointmentsList = $todayStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get recent patients
$recentPatients = [];
if ($staff_id > 0) {
    $recentPatientsQuery = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.profile_image, u.email, 
                           MAX(c.consultation_date) as last_visit
                           FROM consultations c
                           JOIN users u ON c.patient_id = u.user_id
                           WHERE c.staff_id = ?
                           GROUP BY u.user_id
                           ORDER BY last_visit DESC
                           LIMIT 5";
    $recentPatientsStmt = $conn->prepare($recentPatientsQuery);
    $recentPatientsStmt->bind_param("i", $staff_id);
    $recentPatientsStmt->execute();
    $recentPatients = $recentPatientsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Pass the role to be used in the sidebar
$role = 'Doctor';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Medical Management System</title>
    <link rel="stylesheet" href="styles/doctor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="doctor-dashboard">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Total Patients Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Patients</span>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $totalPatients ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>All time</span>
                </div>
            </div>

            <!-- Today's Appointments Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Today's Appointments</span>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $todayAppointments ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Scheduled</span>
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
            </div>
        </div>

        <!-- Today's Appointments Section -->
        <div class="appointments-section">
            <div class="section-header">
                <h2 class="section-title">Today's Appointments</h2>
                <a href="../appointments/schedule.php" class="btn btn-primary">Schedule New</a>
            </div>
            <div class="appointments-list">
                <?php if (!empty($todayAppointmentsList)): ?>
                    <?php foreach ($todayAppointmentsList as $appointment): ?>
                        <div class="appointment-item">
                            <div class="appointment-time">
                                <?= date('h:i A', strtotime($appointment['consultation_date'])) ?>
                            </div>
                            <div class="appointment-content">
                                <div class="appointment-title"><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></div>
                                <div class="appointment-patient">ID: <?= htmlspecialchars($appointment['school_id']) ?></div>
                            </div>
                            <div class="appointment-status <?= strtolower($appointment['status']) ?>"><?= $appointment['status'] ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="appointment-item">
                        <div class="appointment-content">
                            <div class="appointment-title">No appointments scheduled for today</div>
                            <div class="appointment-patient">Schedule a new appointment to get started</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Patients Section -->
        <div class="patients-section">
            <div class="section-header">
                <h2 class="section-title">Recent Patients</h2>
                <a href="../patients/index.php" class="btn btn-primary">View All</a>
            </div>
            <div class="patients-list">
                <?php if (!empty($recentPatients)): ?>
                    <?php foreach ($recentPatients as $patient): ?>
                        <div class="patient-item">
                            <img class="patient-avatar" src="<?= $patient['profile_image'] ?? 'https://via.placeholder.com/150' ?>" alt="Patient">
                            <div class="patient-content">
                                <div class="patient-name"><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></div>
                                <div class="patient-info">Last visit: <?= date('M d, Y', strtotime($patient['last_visit'])) ?></div>
                            </div>
                            <a href="../patients/view.php?id=<?= $patient['user_id'] ?>" class="patient-action">View</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="patient-item">
                        <div class="patient-content">
                            <div class="patient-name">No recent patients</div>
                            <div class="patient-info">Start seeing patients to build your list</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="quick-actions">
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h3 class="action-title">Schedule Appointment</h3>
                <p class="action-description">Book a new consultation</p>
            </div>

            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-file-medical"></i>
                </div>
                <h3 class="action-title">Patient Records</h3>
                <p class="action-description">Access medical histories</p>
            </div>

            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-prescription-bottle-alt"></i>
                </div>
                <h3 class="action-title">Prescriptions</h3>
                <p class="action-description">Manage medications</p>
            </div>

            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="action-title">Reports</h3>
                <p class="action-description">View analytics & reports</p>
            </div>
        </div>
    </div>

    <?php require_once '../../../includes/footer.php'; ?>
</body>
</html>
