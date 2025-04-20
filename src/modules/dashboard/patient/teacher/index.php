<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in and has teacher role
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Teacher') {
    header("Location: /MedMS/auth/login.php");
    exit;
}

// Get current user data
$user_id = $_SESSION['user_id'];
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
                     JOIN users u ON c.staff_id = u.user_id
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
                      JOIN users u ON p.staff_id = u.user_id
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
$doctorsQuery = "SELECT u.user_id, u.first_name, u.last_name, u.profile_image, ms.specialization as specialty
                FROM users u
                JOIN medical_staff ms ON u.user_id = ms.user_id
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

// Pass the role to be used in the sidebar
$role = 'Teacher';
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
    <link rel="stylesheet" href="/MedMS/styles/variables.css">
    <link rel="stylesheet" href="/MedMS/styles/dashboard.css">
    <!-- Intro.js for guided tour -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/6.0.0/introjs.min.css">
</head>
<body class="dark-theme">
    <?php include_once '../../../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Dashboard Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2" data-intro="Welcome to your health dashboard! This is your central hub for managing health services.">Teacher Health Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="startTutorial">
                                <i class="fas fa-question-circle"></i> Help
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#appointmentModal">
                                <i class="fas fa-calendar-plus"></i> Book Appointment
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card welcome-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/150' ?>" class="rounded-circle teacher-profile-img" alt="Teacher Profile" width="80" height="80">
                                    </div>
                                    <div class="col">
                                        <h4>Welcome, <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                                        <p class="text-muted mb-0">Faculty ID: <?= htmlspecialchars($user['school_id']) ?> | <?= date('l, F j, Y') ?></p>
                                    </div>
                                    <div class="col-auto">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Reminder:</strong> Faculty wellness program sessions every Wednesday.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row mb-4" data-intro="These cards show important health information and upcoming appointments">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card health-status">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="text-muted">Health Status</h6>
                                        <h3 class="fw-bold mt-2 text-success">Good</h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-heart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card upcoming-appointments">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="text-muted">Upcoming Appointments</h6>
                                        <h3 class="fw-bold mt-2"><?= count($upcomingAppointments) ?></h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card active-prescriptions">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="text-muted">Active Prescriptions</h6>
                                        <h3 class="fw-bold mt-2"><?= count($recentPrescriptions) ?></h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-prescription fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card wellness-program">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="text-muted">Faculty Wellness</h6>
                                        <h3 class="fw-bold mt-2">
                                            <span class="badge bg-success">Active</span>
                                        </h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-spa fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Health Announcements -->
                <div class="row mb-4" data-intro="Important health announcements for faculty members">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-bullhorn me-1"></i>
                                Faculty Health Announcements
                            </div>
                            <div class="card-body">
                                <?php if (count($healthAnnouncements) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($healthAnnouncements as $announcement): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h5 class="mb-1"><?= htmlspecialchars($announcement['title']) ?></h5>
                                                    <small class="badge bg-<?= $announcement['emergency_level'] == 'High' ? 'danger' : ($announcement['emergency_level'] == 'Medium' ? 'warning' : 'info') ?>"><?= htmlspecialchars($announcement['emergency_level']) ?></small>
                                                </div>
                                                <p class="mb-1"><?= htmlspecialchars($announcement['description']) ?></p>
                                                <small class="text-muted">Created: <?= date('M j, Y', strtotime($announcement['created_at'])) ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No current health announcements.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Two-column layout for appointments and health record -->
                <div class="row mb-4">
                    <!-- Upcoming Appointments -->
                    <div class="col-lg-6 mb-4">
                        <div class="card" data-intro="Here you can see your upcoming medical appointments">
                            <div class="card-header">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Upcoming Appointments
                            </div>
                            <div class="card-body">
                                <?php if (count($upcomingAppointments) > 0): ?>
                                    <div class="list-group mb-3">
                                        <?php foreach ($upcomingAppointments as $appointment): ?>
                                            <div class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h5 class="mb-1">
                                                        <i class="fas fa-stethoscope me-2"></i>
                                                        Dr. <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?>
                                                    </h5>
                                                    <small class="badge bg-primary">
                                                        <?= date('M j, Y', strtotime($appointment['consultation_date'])) ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('h:i A', strtotime($appointment['consultation_date'])) ?>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-comment-medical me-1"></i>
                                                    Reason: <?= htmlspecialchars($appointment['reason']) ?>
                                                </p>
                                                <div class="d-flex mt-2">
                                                    <a href="/MedMS/src/modules/consultation/view.php?id=<?= $appointment['consultation_id'] ?>" class="btn btn-sm btn-outline-info me-2">
                                                        <i class="fas fa-eye"></i> Details
                                                    </a>
                                                    <?php if ($appointment['status'] === 'scheduled'): ?>
                                                    <a href="/MedMS/src/modules/consultation/cancel.php?id=<?= $appointment['consultation_id'] ?>" class="btn btn-sm btn-outline-danger me-2">
                                                        <i class="fas fa-times-circle"></i> Cancel
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <a href="/MedMS/src/modules/consultation/history.php" class="btn btn-outline-primary">
                                        <i class="fas fa-history"></i> View All Appointments
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        You have no upcoming appointments.
                                        <button type="button" class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#appointmentModal">
                                            Book Now
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Health Record Summary -->
                    <div class="col-lg-6">
                        <div class="card mb-4" data-intro="This section shows a summary of your health records">
                            <div class="card-header">
                                <i class="fas fa-file-medical me-1"></i>
                                Health Record Summary
                            </div>
                            <div class="card-body">
                                <?php if ($healthRecord): ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted">Blood Type</h6>
                                            <p class="fw-bold"><?= htmlspecialchars($healthRecord['blood_type'] ?? 'Not recorded') ?></p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted">Allergies</h6>
                                            <p class="fw-bold"><?= htmlspecialchars($healthRecord['allergies'] ?? 'None') ?></p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted">Weight</h6>
                                            <p class="fw-bold"><?= htmlspecialchars($healthRecord['weight'] ?? 'Not recorded') ?> kg</p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted">Height</h6>
                                            <p class="fw-bold"><?= htmlspecialchars($healthRecord['height'] ?? 'Not recorded') ?> cm</p>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <h6 class="text-muted">Medical Conditions</h6>
                                            <p class="fw-bold"><?= htmlspecialchars($healthRecord['medical_conditions'] ?? 'None recorded') ?></p>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <h6 class="text-muted">Last Updated</h6>
                                            <p class="fw-bold"><?= $healthRecord['updated_at'] ? date('F j, Y', strtotime($healthRecord['updated_at'])) : 'Never' ?></p>
                                        </div>
                                    </div>
                                    
                                    <a href="/MedMS/src/modules/medical_history/view.php?id=<?= $user_id ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-file-medical-alt"></i> View Complete Health Record
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No health record found. Please visit the clinic to complete your health record.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Recent Prescriptions -->
                        <div class="card" data-intro="Your most recent prescriptions are shown here">
                            <div class="card-header">
                                <i class="fas fa-prescription-bottle-alt me-1"></i>
                                Recent Prescriptions
                            </div>
                            <div class="card-body">
                                <?php if (count($recentPrescriptions) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($recentPrescriptions as $prescription): ?>
                                            <a href="/MedMS/src/modules/prescription/view.php?id=<?= $prescription['prescription_id'] ?>" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($prescription['medication_name']) ?></h6>
                                                    <small><?= date('M j, Y', strtotime($prescription['created_at'])) ?></small>
                                                </div>
                                                <p class="mb-1">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user-md me-1"></i> Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?>
                                                    </small>
                                                </p>
                                                <p class="mb-1">
                                                    <small>
                                                        <i class="fas fa-pills me-1"></i>
                                                        <?= htmlspecialchars($prescription['dosage_instructions']) ?>
                                                    </small>
                                                </p>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No recent prescriptions.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Faculty Wellness Program -->
                <div class="row mb-4" data-intro="Faculty-specific wellness programs and resources">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-spa me-1"></i>
                                Faculty Wellness Program
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><i class="fas fa-heartbeat me-2"></i> Fitness Sessions</h5>
                                                <p class="card-text">Weekly faculty fitness sessions every Tuesday and Thursday from 4:30 PM to 5:30 PM at the gym.</p>
                                                <a href="/MedMS/src/modules/wellness/fitness.php" class="btn btn-sm btn-outline-primary">Register</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><i class="fas fa-brain me-2"></i> Mental Wellness</h5>
                                                <p class="card-text">Stress management workshops and confidential counseling services for faculty members.</p>
                                                <a href="/MedMS/src/modules/wellness/mental.php" class="btn btn-sm btn-outline-primary">Learn More</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><i class="fas fa-users me-2"></i> Health Screening</h5>
                                                <p class="card-text">Annual faculty health screening program scheduled for next month. Don't miss it!</p>
                                                <a href="/MedMS/src/modules/wellness/screening.php" class="btn btn-sm btn-outline-primary">Schedule</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Appointment Booking Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentModalLabel">Book an Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/MedMS/src/modules/consultation/create.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="doctor" class="form-label">Select Doctor</label>
                            <select class="form-select" id="doctor" name="staff_id" required>
                                <option value="">Select a doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?= $doctor['user_id'] ?>">
                                        Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> 
                                        (<?= htmlspecialchars($doctor['specialty']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="consultation_date" class="form-label">Appointment Date & Time</label>
                            <input type="text" class="form-control" id="consultation_date" name="consultation_date" placeholder="Select date and time" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Visit</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Please describe your symptoms or reason for the appointment" required></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="urgent" name="urgent" value="1">
                            <label class="form-check-label" for="urgent">
                                This is urgent
                            </label>
                        </div>
                        <input type="hidden" name="patient_id" value="<?= $user_id ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Book Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Tutorial Modal -->
    <div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tutorialModalLabel">Welcome to Teacher Health Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Welcome to your Faculty Health Dashboard</h6>
                    <p>This dashboard helps you manage your health at our institution. Here you can:</p>
                    <ul>
                        <li>View your health record and medical history</li>
                        <li>Schedule appointments with healthcare providers</li>
                        <li>Access faculty wellness programs</li>
                        <li>View prescriptions and medication instructions</li>
                        <li>Stay updated with health announcements for faculty</li>
                    </ul>
                    <p>Would you like to take a quick tour of the system?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Skip Tour</button>
                    <button type="button" class="btn btn-primary" id="startTutorialFromModal">Start Tour</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once '../../../includes/footer.php'; ?>
    
    <!-- FlatPickr for date selection -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Custom JS for Dashboard -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date picker for appointment booking
        flatpickr("#consultation_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            minTime: "09:00",
            maxTime: "17:00",
            time_24hr: true,
            disable: [
                function(date) {
                    // Disable weekends
                    return (date.getDay() === 0 || date.getDay() === 6);
                }
            ]
        });
        
        // Display the tutorial modal if it's the user's first login
        <?php if (isset($_SESSION['show_tutorial']) && $_SESSION['show_tutorial']): ?>
            var tutorialModal = new bootstrap.Modal(document.getElementById('tutorialModal'));
            tutorialModal.show();
            <?php $_SESSION['show_tutorial'] = false; ?>
        <?php endif; ?>
        
        // Start tutorial when button is clicked
        document.getElementById('startTutorial').addEventListener('click', function() {
            introJs().start();
        });
        
        document.getElementById('startTutorialFromModal').addEventListener('click', function() {
            var tutorialModal = bootstrap.Modal.getInstance(document.getElementById('tutorialModal'));
            tutorialModal.hide();
            setTimeout(function() {
                introJs().start();
            }, 500);
        });
    });
    </script>
</body>
</html>
