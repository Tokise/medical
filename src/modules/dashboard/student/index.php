<?php
session_start();
require_once '../../../../config/db.php';

// Check if user is logged in and has student role
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Student') {
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
$healthRecordQuery = "SELECT s.blood_type, mh.condition_name as medical_conditions, a.allergy_name as allergies, 
                      CONCAT(s.date_of_birth, ' ', s.gender) as demographic_data, mh.updated_at
                      FROM students s 
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

// Pass the role to be used in the sidebar
$role = 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Health Dashboard - MedMS</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr for date selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../../../styles/variables.css">
    <link rel="stylesheet" href="../student/styles/student.css">
    
</head>
<body class="dark-theme">
    <?php include_once '../../../includes/header.php'; ?>
    <?php include_once '../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-grid">
            <!-- Quick Actions Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                    <div class="card-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                </div>
                <div class="quick-actions-grid">
                    <button class="action-btn">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Book Appointment</span>
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-notes-medical"></i>
                        <span>View Records</span>
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-robot"></i>
                        <span>AI Consultation</span>
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-pills"></i>
                        <span>Medications</span>
                    </button>
                </div>
            </div>

            <!-- Health Summary Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Health Summary</h3>
                    <div class="card-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Blood Type</div>
                        <div class="stat-value"><?= $healthRecord['blood_type'] ?? 'N/A' ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Medical Conditions</div>
                        <div class="stat-value"><?= $healthRecord['medical_conditions'] ? substr_count($healthRecord['medical_conditions'], ',') + 1 : '0' ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Allergies</div>
                        <div class="stat-value"><?= $healthRecord['allergies'] ? substr_count($healthRecord['allergies'], ',') + 1 : '0' ?></div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Appointments</h3>
                    <div class="card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="list-group">
                    <?php foreach ($upcomingAppointments as $appointment): ?>
                    <div class="list-item">
                        <img class="list-item-avatar" src="<?= $appointment['profile_image'] ?? 'https://via.placeholder.com/150' ?>" alt="Doctor">
                        <div class="list-item-content">
                            <div class="list-item-title">Dr. <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></div>
                            <div class="list-item-subtitle"><?= date('M d, Y H:i', strtotime($appointment['consultation_date'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Health Statistics Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Health Statistics</h3>
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-weight"></i>
                        </div>
                        <div class="stat-value">65</div>
                        <div class="stat-label">Weight (kg)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-ruler-vertical"></i>
                        </div>
                        <div class="stat-value">170</div>
                        <div class="stat-label">Height (cm)</div>
                    </div>
                </div>
            </div>

            <!-- Recent Prescriptions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Prescriptions</h3>
                    <div class="card-icon">
                        <i class="fas fa-prescription"></i>
                    </div>
                </div>
                <div class="list-group">
                    <?php foreach ($recentPrescriptions as $prescription): ?>
                    <div class="list-item">
                        <div class="card-icon">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="list-item-content">
                            <div class="list-item-title"><?= htmlspecialchars($prescription['medication_name']) ?></div>
                            <div class="list-item-subtitle"><?= htmlspecialchars($prescription['dosage_instructions']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Activities Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Activities</h3>
                    <div class="card-icon">
                        <i class="fas fa-clock-rotate-left"></i>
                    </div>
                </div>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-capsules"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Medication Updated</h4>
                            <p>New prescription added by Dr. Smith</p>
                            <span class="timeline-date">2 hours ago</span>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <div class="timeline-content">
                            <h4>Health Record Updated</h4>
                            <p>Blood test results uploaded</p>
                            <span class="timeline-date">Yesterday</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
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

        // Check if demo is active from previous page or first login
        const isDemoActive = sessionStorage.getItem('demo_active') === 'true';
        if (isDemoActive || <?= isset($_SESSION['first_login']) && $_SESSION['first_login'] ? 'true' : 'false' ?>) {
            startStudentDashboardTour();
        }

        // Add jQuery UI tooltip for better UX
        $('.action-btn').tooltip();
        
        // Fix event propagation issues
        $('.dashboard-card').on('click', function(e) {
            e.stopPropagation();
        });
    });

    function startStudentDashboardTour() {
        // Remove any existing intro.js elements
        const existingOverlay = document.querySelector('.introjs-overlay');
        const existingTooltip = document.querySelector('.introjs-tooltipReferenceLayer');
        if (existingOverlay) existingOverlay.remove();
        if (existingTooltip) existingTooltip.remove();

        introJs().setOptions({
            steps: [
                {
                    title: 'Welcome',
                    intro: 'Welcome to your Student Health Dashboard! Let\'s explore all features available to you.',
                    position: 'center'
                },
                {
                    element: '.header-nav',
                    intro: 'This is your main navigation bar. Access notifications, messages, and your profile settings here.',
                    position: 'center'
                },
                {
                    element: '.sidebar',
                    intro: 'Your sidebar menu contains quick access to all major sections of the system.',
                    position: 'right'
                },
                {
                    element: '.quick-actions-grid',
                    intro: 'Quick actions allow you to perform common tasks like booking appointments or checking records.',
                    position: 'bottom'
                },
                {
                    element: '.dashboard-card:nth-child(2)',
                    intro: 'View your health summary including blood type, medical conditions, and allergies.',
                    position: 'bottom',  // Changed from 'right' to 'bottom'
                    tooltipPosition: 'bottom-middle', // Added specific positioning
                    positionPrecedence: ['bottom', 'top', 'right', 'left'] // Prioritize bottom position
                },
                {
                    element: '.dashboard-card:nth-child(3)',
                    intro: 'Keep track of your upcoming medical appointments here.',
                    position: 'left'
                },
                {
                    element: '.dashboard-card:nth-child(4)',
                    intro: 'Monitor your health statistics over time.',
                    position: 'right'
                },
                {
                    element: '.dashboard-card:nth-child(5)',
                    intro: 'Access your recent prescriptions and medications.',
                    position: 'left'
                },
                {
                    element: '.dashboard-card:nth-child(6)',
                    intro: 'Stay updated with your recent medical activities and updates.',
                    position: 'left'
                },
                {
                    element: '#notificationDropdown',
                    intro: 'Check your notifications for important updates.',
                    position: 'bottom'
                },
                {
                    element: '#messageDropdown',
                    intro: 'Access your messages and communicate with medical staff.',
                    position: 'bottom'
                },
                {
                    element: '#userDropdown',
                    intro: 'Manage your profile and account settings here.',
                    position: 'left'
                },
                {
                    title: 'Student Dashboard',
                    intro: 'Welcome to your Student Health Portal! Let\'s explore your health management features.',
                    position: 'center'
                },
                {
                    element: '.quick-actions-grid',
                    intro: 'Book appointments, check records, and access AI consultations instantly.',
                    position: 'bottom'
                },
                {
                    element: '.stats-grid',
                    intro: 'View your vital health information and statistics.',
                    position: 'bottom',
                    tooltipPosition: 'bottom'
                },
                {
                    element: '.list-group',
                    intro: 'Track your upcoming appointments and medical schedules.',
                    position: 'left'
                },
                {
                    element: '.timeline',
                    intro: 'See your recent medical activities and updates.',
                    position: 'left'
                }
            ],
            showProgress: true,
            showBullets: true,
            exitOnOverlayClick: false,
            exitOnEsc: false,
            doneLabel: 'Finish Tour',
            tooltipClass: 'customTooltip introjs-custom-theme',
            highlightClass: 'introjs-custom-highlight',
            overlayOpacity: 1, // Lighter overlay
            scrollToElement: true,
            scrollPadding: 100,
            disableInteraction: true,
            showStepNumbers: true
        }).onbeforechange(function(targetElement) {
            // Remove bootstrap dropdowns that might interfere
            $('.dropdown-menu').removeClass('show');
        }).onafterchange(function(targetElement) {
            // Ensure buttons are clickable
            const buttons = document.querySelectorAll('.introjs-button');
            buttons.forEach(button => {
                button.style.pointerEvents = 'auto';
                button.style.cursor = 'pointer';
            });
        }).oncomplete(function() {
            sessionStorage.removeItem('demo_active');
            setTimeout(() => {
                window.location.reload();
            }, 100);
        }).onexit(function() {
            sessionStorage.removeItem('demo_active');
            setTimeout(() => {
                window.location.reload();
            }, 100);
        }).start();
    }
    </script>
    <style>
    .introjs-custom-theme {
        background: var(--bg-primary);
        color: var(--text-primary);
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        max-width: 1800px;
    }

    .introjs-custom-theme .introjs-tooltip-title {
        font-size: 1.1em;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .introjs-custom-theme .introjs-button {
        background: var(--accent-color);
        border: none;
        color: white;
        padding: 8px 16px;
        margin: 4px;
        border-radius: 4px;
        font-size: 0.9em;
    }

    .introjs-custom-theme .introjs-button:hover {
        background: var(--bg-primary);
    }

    .introjs-custom-theme .introjs-skipbutton {
        color: var(--text-color);
        opacity: 0.8;
    }

    .introjs-helperLayer {
        background-color: rgba(255, 255, 255, 0.8) !important;
        border: 2px solid var(--accent-color);
        box-shadow: 0 0 15px rgba(0,0,0,0.5) !important;
    }

    .introjs-custom-highlight {
        background-color: transparent !important; 
    }

    .introjs-overlay {
        background-color: transparent !important; 
    }

    .introjs-tooltip.customTooltip {
        max-width: 300px;
        margin-top: 15px; 
    }
    </style>
</body>
</html>
