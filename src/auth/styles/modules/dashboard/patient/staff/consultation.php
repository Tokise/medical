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

// Get available doctors for consultations
$doctorsQuery = "SELECT u.user_id, u.first_name, u.last_name, u.profile_image, d.specialization 
                FROM users u
                JOIN doctors d ON u.user_id = d.user_id
                JOIN roles r ON u.role_id = r.role_id
                WHERE r.role_name = 'Doctor' AND d.availability_status = 'Available'
                ORDER BY u.last_name ASC";
$doctors = $conn->query($doctorsQuery)->fetch_all(MYSQLI_ASSOC);

// Get consultation types
$consultationTypesQuery = "SELECT * FROM consultation_types ORDER BY name ASC";
$consultationTypes = $conn->query($consultationTypesQuery)->fetch_all(MYSQLI_ASSOC);

// Get user's previous consultations
$previousConsultationsQuery = "SELECT c.*, u.first_name, u.last_name, u.profile_image 
                             FROM consultations c
                             JOIN users u ON c.doctor_id = u.user_id
                             WHERE c.patient_id = ?
                             ORDER BY c.consultation_date DESC
                             LIMIT 5";
$previousConsultationsStmt = $conn->prepare($previousConsultationsQuery);
$previousConsultationsStmt->bind_param("i", $user_id);
$previousConsultationsStmt->execute();
$previousConsultations = $previousConsultationsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Process consultation request submission
$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_SANITIZE_NUMBER_INT);
    $consultation_type = filter_input(INPUT_POST, 'consultation_type', FILTER_SANITIZE_NUMBER_INT);
    $consultation_date = filter_input(INPUT_POST, 'consultation_date', FILTER_SANITIZE_STRING);
    $chief_complaint = filter_input(INPUT_POST, 'chief_complaint', FILTER_SANITIZE_STRING);
    $symptoms = filter_input(INPUT_POST, 'symptoms', FILTER_SANITIZE_STRING);
    $additional_notes = filter_input(INPUT_POST, 'additional_notes', FILTER_SANITIZE_STRING);
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    
    // Validate required fields
    if (empty($doctor_id) || empty($consultation_type) || empty($consultation_date) || empty($chief_complaint)) {
        $errorMessage = "Please fill in all required fields";
    } else {
        // Insert consultation request
        $insertQuery = "INSERT INTO consultations (patient_id, doctor_id, consultation_type_id, consultation_date, 
                        chief_complaint, symptoms, additional_notes, is_urgent, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Requested', NOW())";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iiissssi", $user_id, $doctor_id, $consultation_type, $consultation_date, 
                               $chief_complaint, $symptoms, $additional_notes, $is_urgent);
        
        if ($insertStmt->execute()) {
            $successMessage = "Your consultation request has been submitted successfully!";
            
            // If urgent, send notification
            if ($is_urgent) {
                // Insert notification for the doctor
                $notificationQuery = "INSERT INTO notifications (user_id, title, content, is_read, created_at)
                                     VALUES (?, 'Urgent Consultation Request', ?, 0, NOW())";
                $notificationStmt = $conn->prepare($notificationQuery);
                $notificationContent = "Staff member " . $user['first_name'] . " " . $user['last_name'] . " has requested an urgent consultation.";
                $notificationStmt->bind_param("is", $doctor_id, $notificationContent);
                $notificationStmt->execute();
            }
        } else {
            $errorMessage = "Error submitting your request. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Consultation - Staff Health Portal</title>
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
    
    <div class="staff-dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-stethoscope"></i> Request Consultation</h1>
            <p>Request a medical consultation with one of our healthcare professionals</p>
        </div>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success animate-in">
                <i class="fas fa-check-circle"></i>
                <?= $successMessage ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger animate-in">
                <i class="fas fa-exclamation-circle"></i>
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="dashboard-column">
                <!-- Consultation Request Form -->
                <div class="form-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-clipboard-list"></i>
                            Consultation Request Form
                        </h3>
                    </div>
                    
                    <div class="form-container">
                        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" class="styled-form">
                            <!-- Doctor Selection -->
                            <div class="form-group">
                                <label for="doctor_id">Select Doctor <span class="required">*</span></label>
                                <select id="doctor_id" name="doctor_id" class="form-control" required>
                                    <option value="">-- Select a Doctor --</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?= $doctor['user_id'] ?>">
                                            Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> 
                                            (<?= htmlspecialchars($doctor['specialization']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Consultation Type -->
                            <div class="form-group">
                                <label for="consultation_type">Consultation Type <span class="required">*</span></label>
                                <select id="consultation_type" name="consultation_type" class="form-control" required>
                                    <option value="">-- Select Type --</option>
                                    <?php foreach ($consultationTypes as $type): ?>
                                        <option value="<?= $type['consultation_type_id'] ?>">
                                            <?= htmlspecialchars($type['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Preferred Date and Time -->
                            <div class="form-group">
                                <label for="consultation_date">Preferred Date & Time <span class="required">*</span></label>
                                <input type="text" id="consultation_date" name="consultation_date" class="form-control date-time-picker" placeholder="Select date and time" required>
                            </div>
                            
                            <!-- Chief Complaint -->
                            <div class="form-group">
                                <label for="chief_complaint">Chief Complaint <span class="required">*</span></label>
                                <input type="text" id="chief_complaint" name="chief_complaint" class="form-control" placeholder="e.g. Headache, Fever, Regular Checkup" required>
                            </div>
                            
                            <!-- Symptoms -->
                            <div class="form-group">
                                <label for="symptoms">Symptoms</label>
                                <textarea id="symptoms" name="symptoms" class="form-control" rows="3" placeholder="Describe your symptoms in detail"></textarea>
                            </div>
                            
                            <!-- Additional Notes -->
                            <div class="form-group">
                                <label for="additional_notes">Additional Notes</label>
                                <textarea id="additional_notes" name="additional_notes" class="form-control" rows="3" placeholder="Any additional information the doctor should know"></textarea>
                            </div>
                            
                            <!-- Is Urgent -->
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is_urgent" name="is_urgent" class="styled-checkbox">
                                <label for="is_urgent">This is an urgent request</label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Request
                                </button>
                                <a href="javascript:history.back()" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Go Back
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Consultation Process Guide -->
                <div class="info-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Consultation Process Guide
                        </h3>
                    </div>
                    
                    <div class="process-steps">
                        <div class="process-step animate-in" style="animation-delay: 0.1s;">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Submit Request</h4>
                                <p>Fill out the form with your consultation details and submit your request</p>
                            </div>
                        </div>
                        
                        <div class="process-step animate-in" style="animation-delay: 0.2s;">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Request Review</h4>
                                <p>Your request will be reviewed by the selected healthcare professional</p>
                            </div>
                        </div>
                        
                        <div class="process-step animate-in" style="animation-delay: 0.3s;">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Confirmation</h4>
                                <p>You'll receive a confirmation notification with final appointment details</p>
                            </div>
                        </div>
                        
                        <div class="process-step animate-in" style="animation-delay: 0.4s;">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h4>Consultation</h4>
                                <p>Attend your appointment at the scheduled time</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="dashboard-column">
                <!-- Doctor Availability -->
                <div class="doctors-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-user-md"></i>
                            Available Healthcare Professionals
                        </h3>
                    </div>
                    
                    <div class="doctors-grid">
                        <?php if (!empty($doctors)): ?>
                            <?php foreach ($doctors as $index => $doctor): ?>
                                <div class="doctor-card animate-in" style="animation-delay: <?= 0.1 + ($index * 0.1) ?>s;">
                                    <div class="doctor-avatar">
                                        <img src="<?= !empty($doctor['profile_image']) ? $doctor['profile_image'] : '/medical/assets/img/default-avatar.png' ?>" 
                                             alt="Dr. <?= htmlspecialchars($doctor['last_name']) ?>">
                                    </div>
                                    <h4 class="doctor-name">Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?></h4>
                                    <p class="doctor-specialty"><?= htmlspecialchars($doctor['specialization']) ?></p>
                                    <button class="btn btn-sm btn-primary select-doctor" data-doctor-id="<?= $doctor['user_id'] ?>">
                                        Select
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-md"></i>
                                <h4 class="empty-title">No Doctors Available</h4>
                                <p class="empty-description">There are no doctors available for consultations at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Previous Consultations -->
                <div class="previous-consultations-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-history"></i>
                            Your Previous Consultations
                        </h3>
                        <a href="/medical/src/modules/consultation/history.php" class="btn btn-primary">View All</a>
                    </div>
                    
                    <div class="consultations-list">
                        <?php if (!empty($previousConsultations)): ?>
                            <?php foreach ($previousConsultations as $index => $consultation): ?>
                                <div class="consultation-item animate-in" style="animation-delay: <?= 0.1 + ($index * 0.1) ?>s;">
                                    <div class="consultation-date">
                                        <span class="date-day"><?= date('d', strtotime($consultation['consultation_date'])) ?></span>
                                        <span class="date-month"><?= date('M', strtotime($consultation['consultation_date'])) ?></span>
                                        <span class="date-year"><?= date('Y', strtotime($consultation['consultation_date'])) ?></span>
                                    </div>
                                    <div class="consultation-content">
                                        <h4 class="consultation-title"><?= htmlspecialchars($consultation['chief_complaint']) ?></h4>
                                        <p class="consultation-doctor">With Dr. <?= htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']) ?></p>
                                        <span class="consultation-status <?= strtolower($consultation['status']) ?>">
                                            <?= htmlspecialchars($consultation['status']) ?>
                                        </span>
                                    </div>
                                    <a href="/medical/src/modules/consultation/view.php?id=<?= $consultation['consultation_id'] ?>" class="btn btn-sm btn-primary">View</a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <h4 class="empty-title">No Previous Consultations</h4>
                                <p class="empty-description">You haven't had any consultations yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Urgent Care Info -->
                <div class="urgent-care-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-ambulance"></i>
                            Urgent Care Information
                        </h3>
                    </div>
                    
                    <div class="urgent-care-info">
                        <div class="info-block animate-in" style="animation-delay: 0.1s;">
                            <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                            <div class="info-content">
                                <h4>Emergency Contact</h4>
                                <p>School Clinic Hotline: <strong>(555) 123-4567</strong></p>
                            </div>
                        </div>
                        
                        <div class="info-block animate-in" style="animation-delay: 0.2s;">
                            <div class="info-icon"><i class="fas fa-clock"></i></div>
                            <div class="info-content">
                                <h4>Urgent Care Hours</h4>
                                <p>Monday - Friday: <strong>8:00 AM - 6:00 PM</strong></p>
                            </div>
                        </div>
                        
                        <div class="info-block animate-in" style="animation-delay: 0.3s;">
                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="info-content">
                                <h4>Clinic Location</h4>
                                <p>Main Building, 1st Floor, Room 105</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-info animate-in" style="animation-delay: 0.4s;">
                            <i class="fas fa-info-circle"></i>
                            <p>For life-threatening emergencies, please call <strong>911</strong> immediately.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flatpickr JS for date picking -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date picker
            flatpickr(".date-time-picker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                time_24hr: false,
                minTime: "08:00",
                maxTime: "17:00",
                disable: [
                    function(date) {
                        // Disable weekends
                        return (date.getDay() === 0 || date.getDay() === 6);
                    }
                ]
            });
            
            // Add animation classes
            const animateItems = document.querySelectorAll('.animate-in');
            animateItems.forEach(item => {
                item.classList.add('show');
            });
            
            // Handle doctor selection
            const selectDoctorBtns = document.querySelectorAll('.select-doctor');
            selectDoctorBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const doctorId = this.getAttribute('data-doctor-id');
                    document.getElementById('doctor_id').value = doctorId;
                    
                    // Scroll to the form
                    document.querySelector('.form-section').scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>