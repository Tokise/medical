<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in and has doctor role
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Doctor') {
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

// Get counts for dashboard stats
$totalPatientsQuery = "SELECT COUNT(*) as count FROM users u 
                      JOIN roles r ON u.role_id = r.role_id 
                      WHERE r.role_name IN ('Student', 'Teacher')";
$totalPatients = $conn->query($totalPatientsQuery)->fetch_assoc()['count'];

$totalPrescriptionsQuery = "SELECT COUNT(*) as count FROM prescriptions WHERE doctor_id = ?";
$prescriptionStmt = $conn->prepare($totalPrescriptionsQuery);
$prescriptionStmt->bind_param("i", $user_id);
$prescriptionStmt->execute();
$totalPrescriptions = $prescriptionStmt->get_result()->fetch_assoc()['count'];

// Get today's appointments
$todayQuery = "SELECT a.*, u.first_name, u.last_name, u.school_id 
              FROM appointments a
              JOIN users u ON a.patient_id = u.user_id
              WHERE a.doctor_id = ? AND DATE(a.appointment_date) = CURDATE()
              ORDER BY a.appointment_date ASC";
$todayStmt = $conn->prepare($todayQuery);
$todayStmt->bind_param("i", $user_id);
$todayStmt->execute();
$todayAppointments = $todayStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Pass the role to be used in the sidebar
$role = 'Doctor';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - MedMS</title>
   
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
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
                    <h1 class="h2" data-intro="Welcome to your dashboard! This is where you can see an overview of your patient appointments.">Doctor Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="startTutorial">
                                <i class="fas fa-question-circle"></i> Help
                            </button>
                            <a href="/MedMS/src/modules/prescription/create.php" class="btn btn-sm btn-success">
                                <i class="fas fa-prescription"></i> New Prescription
                            </a>
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
                                        <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/150' ?>" class="rounded-circle doctor-profile-img" alt="Doctor Profile" width="80" height="80">
                                    </div>
                                    <div class="col">
                                        <h4>Welcome, Dr. <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                                        <p class="text-muted mb-0"><?= date('l, F j, Y') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4" data-intro="These cards show key statistics about your patients and activities">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card total-patients">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="text-muted">Total Patients</h6>
                                        <h3 class="fw-bold mt-2"><?= $totalPatients ?></h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card total-appointments">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="text-muted">Today's Appointments</h6>
                                        <h3 class="fw-bold mt-2"><?= count($todayAppointments) ?></h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card total-prescriptions">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="text-muted">Total Prescriptions</h6>
                                        <h3 class="fw-bold mt-2"><?= $totalPrescriptions ?></h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-prescription fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card total-available">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="text-muted">Availability Status</h6>
                                        <h3 class="fw-bold mt-2 text-success">Available</h3>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Today's Appointments -->
                <div class="row mb-4" data-intro="Here you can see all your appointments for today">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-calendar-day me-1"></i>
                                Today's Appointments (<?= count($todayAppointments) ?>)
                            </div>
                            <div class="card-body">
                                <?php if (count($todayAppointments) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Patient ID</th>
                                                    <th>Patient Name</th>
                                                    <th>Status</th>
                                                    <th>Reason</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($todayAppointments as $appointment): ?>
                                                    <tr>
                                                        <td><?= date('h:i A', strtotime($appointment['appointment_date'])) ?></td>
                                                        <td><?= htmlspecialchars($appointment['school_id']) ?></td>
                                                        <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></td>
                                                        <td>
                                                            <?php
                                                            switch ($appointment['status']) {
                                                                case 'scheduled':
                                                                    echo '<span class="badge bg-primary">Scheduled</span>';
                                                                    break;
                                                                case 'completed':
                                                                    echo '<span class="badge bg-success">Completed</span>';
                                                                    break;
                                                                case 'cancelled':
                                                                    echo '<span class="badge bg-danger">Cancelled</span>';
                                                                    break;
                                                                case 'in-progress':
                                                                    echo '<span class="badge bg-warning">In Progress</span>';
                                                                    break;
                                                                default:
                                                                    echo '<span class="badge bg-secondary">Unknown</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($appointment['reason']) ?></td>
                                                        <td>
                                                            <a href="/MedMS/src/modules/consultation/view.php?id=<?= $appointment['appointment_id'] ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="/MedMS/src/modules/consultation/start.php?id=<?= $appointment['appointment_id'] ?>" class="btn btn-sm btn-success">
                                                                <i class="fas fa-stethoscope"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        You have no appointments scheduled for today.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Patient Stats Chart -->
                <div class="row mb-4" data-intro="This chart shows your patient statistics">
                    <div class="col-xl-8 col-lg-7">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-bar me-1"></i>
                                Patient Statistics
                            </div>
                            <div class="card-body">
                                <canvas id="patientStatsChart" width="100%" height="40"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-lg-5">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-clipboard-list me-1"></i>
                                Recent Activities
                            </div>
                            <div class="card-body">
                                <ul class="timeline">
                                    <li class="timeline-item">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <h5 class="timeline-title">Prescription created</h5>
                                            <p class="timeline-text">For John Smith (ID: S12345)</p>
                                            <p class="timeline-date">Today, 9:30 AM</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <h5 class="timeline-title">Consultation completed</h5>
                                            <p class="timeline-text">With Jane Doe (ID: S67890)</p>
                                            <p class="timeline-date">Yesterday, 2:15 PM</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <h5 class="timeline-title">New appointment</h5>
                                            <p class="timeline-text">With Robert Brown (ID: T12345)</p>
                                            <p class="timeline-date">May 15, 10:00 AM</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Tutorial Modal -->
    <div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tutorialModalLabel">Welcome to Doctor Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Welcome to the Doctor Dashboard</h6>
                    <p>As a medical professional, you can:</p>
                    <ul>
                        <li>View your patient appointments</li>
                        <li>Create and manage prescriptions</li>
                        <li>Search for patients by school ID</li>
                        <li>Record consultation notes</li>
                        <li>Manage your availability schedule</li>
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
    
    <!-- Custom JS for Charts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts
        // Patient Stats Chart
        var patientStatsCtx = document.getElementById('patientStatsChart').getContext('2d');
        var patientStatsChart = new Chart(patientStatsCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Appointments',
                    data: [12, 19, 14, 15, 10, 7],
                    backgroundColor: 'rgba(74, 222, 128, 0.5)',
                    borderColor: 'rgba(74, 222, 128, 1)',
                    borderWidth: 2
                }, {
                    label: 'Prescriptions',
                    data: [8, 12, 9, 10, 7, 4],
                    backgroundColor: 'rgba(96, 165, 250, 0.5)',
                    borderColor: 'rgba(96, 165, 250, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Display the tutorial modal if it's the user's first login
        <?php if (isset($_SESSION['show_tutorial']) && $_SESSION['show_tutorial']): ?>
            var tutorialModal = new bootstrap.Modal(document.getElementById('tutorialModal'));
            tutorialModal.show();
            <?php $_SESSION['show_tutorial'] = false; ?>
        <?php endif; ?>
    });

    function startDoctorDashboardTour() {
        const existingOverlay = document.querySelector('.introjs-overlay');
        if (existingOverlay) existingOverlay.remove();

        introJs().setOptions({
            steps: [
                {
                    title: 'Doctor Dashboard',
                    intro: 'Welcome to your Medical Practice Portal! Let\'s explore your tools and features.',
                    position: 'center'
                },
                {
                    element: '.welcome-card',
                    intro: 'Your profile and today\'s overview.',
                    position: 'bottom'
                },
                {
                    element: '.stats-card',
                    intro: 'Quick statistics about your patients and appointments.',
                    position: 'bottom'
                },
                {
                    element: '.appointments-section',
                    intro: 'Manage your daily appointments and consultations.',
                    position: 'left'
                },
                {
                    element: '.patient-stats',
                    intro: 'View patient statistics and treatment progress.',
                    position: 'right'
                }
            ],
            showProgress: true,
            showBullets: true,
            exitOnOverlayClick: false,
            exitOnEsc: false,
            doneLabel: 'Finish Tour',
            tooltipClass: 'customTooltip',
            overlayOpacity: 0.7,
            scrollToElement: true,
            highlightClass: 'introjs-custom-highlight'
        }).start();
    }
    </script>
</body>
</html>
