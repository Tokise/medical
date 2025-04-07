<?php
session_start();
require_once '../../../../config/db.php';
require_once '../../../includes/header.php';

// Check if user is logged in and has student role
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Student') {
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

// Get student data
$studentQuery = "SELECT * FROM students WHERE user_id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$student = $studentStmt->get_result()->fetch_assoc();

// Get health record summary
$healthQuery = "SELECT * FROM medical_history WHERE user_id = ?";
$healthStmt = $conn->prepare($healthQuery);
$healthStmt->bind_param("i", $user_id);
$healthStmt->execute();
$healthRecords = $healthStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get allergies
$allergiesQuery = "SELECT * FROM allergies WHERE user_id = ?";
$allergiesStmt = $conn->prepare($allergiesQuery);
$allergiesStmt->bind_param("i", $user_id);
$allergiesStmt->execute();
$allergies = $allergiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get upcoming appointments
$appointmentsQuery = "SELECT c.*, u.first_name, u.last_name, ms.specialization 
                     FROM consultations c
                     JOIN users u ON c.staff_id = u.user_id
                     JOIN medical_staff ms ON u.user_id = ms.user_id
                     WHERE c.patient_id = ? AND c.status != 'Cancelled' AND c.consultation_date > NOW()
                     ORDER BY c.consultation_date ASC
                     LIMIT 5";
$appointmentsStmt = $conn->prepare($appointmentsQuery);
$appointmentsStmt->bind_param("i", $user_id);
$appointmentsStmt->execute();
$appointments = $appointmentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent prescriptions
$prescriptionsQuery = "SELECT p.*, u.first_name, u.last_name, ms.specialization 
                      FROM prescriptions p
                      JOIN users u ON p.staff_id = u.user_id
                      JOIN medical_staff ms ON u.user_id = ms.user_id
                      WHERE p.user_id = ? 
                      ORDER BY p.issue_date DESC
                      LIMIT 5";
$prescriptionsStmt = $conn->prepare($prescriptionsQuery);
$prescriptionsStmt->bind_param("i", $user_id);
$prescriptionsStmt->execute();
$prescriptions = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get counts for dashboard stats
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

// Pass the role to be used in the sidebar
$role = 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Medical Management System</title>
    <link rel="stylesheet" href="styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="student-dashboard">
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

        <!-- Health Record Summary Section -->
        <div class="health-record-section">
            <div class="section-header">
                <h2 class="section-title">Health Record Summary</h2>
                <a href="../health-records/view.php" class="btn btn-primary">View Full Record</a>
            </div>
            <div class="health-record-content">
                <div class="health-info">
                    <div class="info-item">
                        <span class="info-label">Blood Type:</span>
                        <span class="info-value"><?= $student['blood_type'] ?? 'Not specified' ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Medical Conditions:</span>
                        <span class="info-value"><?= count($healthRecords) > 0 ? count($healthRecords) . ' conditions' : 'None recorded' ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Allergies:</span>
                        <span class="info-value"><?= count($allergies) > 0 ? count($allergies) . ' allergies' : 'None recorded' ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Emergency Contact:</span>
                        <span class="info-value"><?= $student['emergency_contact_name'] ?? 'Not specified' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments Section -->
        <div class="appointments-section">
            <div class="section-header">
                <h2 class="section-title">Upcoming Appointments</h2>
                <a href="../appointments/schedule.php" class="btn btn-primary">Schedule New</a>
            </div>
            <div class="appointments-list">
                <?php if (!empty($appointments)): ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <div class="appointment-item">
                            <div class="appointment-time">
                                <?= date('M d, Y h:i A', strtotime($appointment['consultation_date'])) ?>
                            </div>
                            <div class="appointment-content">
                                <div class="appointment-title">Dr. <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></div>
                                <div class="appointment-doctor"><?= htmlspecialchars($appointment['specialization']) ?></div>
                            </div>
                            <div class="appointment-status <?= strtolower($appointment['status']) ?>"><?= $appointment['status'] ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="appointment-item">
                        <div class="appointment-content">
                            <div class="appointment-title">No upcoming appointments</div>
                            <div class="appointment-doctor">Schedule a new appointment to get started</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Prescriptions Section -->
        <div class="prescriptions-section">
            <div class="section-header">
                <h2 class="section-title">Recent Prescriptions</h2>
                <a href="../prescriptions/index.php" class="btn btn-primary">View All</a>
            </div>
            <div class="prescriptions-list">
                <?php if (!empty($prescriptions)): ?>
                    <?php foreach ($prescriptions as $prescription): ?>
                        <div class="prescription-item">
                            <div class="prescription-date">
                                <?= date('M d, Y', strtotime($prescription['issue_date'])) ?>
                            </div>
                            <div class="prescription-content">
                                <div class="prescription-title"><?= htmlspecialchars($prescription['diagnosis']) ?></div>
                                <div class="prescription-doctor">Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></div>
                            </div>
                            <div class="prescription-status <?= strtolower($prescription['status']) ?>"><?= $prescription['status'] ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="prescription-item">
                        <div class="prescription-content">
                            <div class="prescription-title">No prescriptions</div>
                            <div class="prescription-doctor">Your prescriptions will appear here</div>
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
                <h3 class="action-title">Health Records</h3>
                <p class="action-description">View your medical history</p>
            </div>

            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-prescription-bottle-alt"></i>
                </div>
                <h3 class="action-title">Prescriptions</h3>
                <p class="action-description">Manage your medications</p>
            </div>

            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <h3 class="action-title">Update Profile</h3>
                <p class="action-description">Edit your information</p>
            </div>
        </div>
    </div>

    <?php require_once '../../../includes/footer.php'; ?>
</body>
</html>
