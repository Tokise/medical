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

// Get available doctors
$doctorsQuery = "SELECT u.user_id, u.first_name, u.last_name, u.profile_image, d.specialization 
                FROM users u
                JOIN doctors d ON u.user_id = d.user_id
                JOIN roles r ON u.role_id = r.role_id
                WHERE r.role_name = 'Doctor'
                ORDER BY u.last_name ASC";
$doctors = $conn->query($doctorsQuery)->fetch_all(MYSQLI_ASSOC);

// Get available time slots (example data)
$timeSlots = [
    '09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', 
    '11:00 AM', '11:30 AM', '12:00 PM', '01:30 PM',
    '02:00 PM', '02:30 PM', '03:00 PM', '03:30 PM',
    '04:00 PM', '04:30 PM'
];

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_SANITIZE_NUMBER_INT);
    $appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointment_time = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);
    $chief_complaint = filter_input(INPUT_POST, 'chief_complaint', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    
    // Combine date and time
    $consultation_date = date('Y-m-d H:i:s', strtotime("$appointment_date $appointment_time"));
    
    // Check if the selected time slot is available
    $checkAvailabilityQuery = "SELECT COUNT(*) as count FROM consultations 
                              WHERE doctor_id = ? AND consultation_date = ? AND status != 'Cancelled'";
    $checkStmt = $conn->prepare($checkAvailabilityQuery);
    $checkStmt->bind_param("is", $doctor_id, $consultation_date);
    $checkStmt->execute();
    $availabilityResult = $checkStmt->get_result()->fetch_assoc();
    
    if ($availabilityResult['count'] > 0) {
        $errorMessage = "This time slot is already booked. Please select a different time.";
    } else {
        // Insert the appointment into the database
        $insertQuery = "INSERT INTO consultations (patient_id, doctor_id, consultation_date, chief_complaint, 
                        notes, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'Scheduled', NOW())";
        $insertStmt = $conn->prepare($insertQuery);
        $status = "Scheduled";
        $insertStmt->bind_param("iisss", $user_id, $doctor_id, $consultation_date, $chief_complaint, $notes);
        
        if ($insertStmt->execute()) {
            $appointment_id = $insertStmt->insert_id;
            $successMessage = "Appointment successfully scheduled!";
            
            // Send notification to doctor (example)
            $notificationQuery = "INSERT INTO notifications (user_id, title, message, link, created_at) 
                                VALUES (?, 'New Appointment', ?, ?, NOW())";
            $notificationStmt = $conn->prepare($notificationQuery);
            $notificationTitle = "New staff appointment scheduled";
            $notificationMessage = "A new appointment has been scheduled with you by " . $user['first_name'] . " " . $user['last_name'] . " on " . date('M j, Y', strtotime($appointment_date)) . " at " . $appointment_time;
            $notificationLink = "/medical/src/modules/appointment/view.php?id=" . $appointment_id;
            $notificationStmt->bind_param("iss", $doctor_id, $notificationMessage, $notificationLink);
            $notificationStmt->execute();
        } else {
            $errorMessage = "Error scheduling appointment. Please try again.";
        }
    }
}

// Get user's recent health history
$healthHistoryQuery = "SELECT * FROM medical_history WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1";
$healthHistoryStmt = $conn->prepare($healthHistoryQuery);
$healthHistoryStmt->bind_param("i", $user_id);
$healthHistoryStmt->execute();
$healthHistory = $healthHistoryStmt->get_result()->fetch_assoc();

// Get user's recent appointments
$recentAppointmentsQuery = "SELECT c.*, u.first_name, u.last_name, u.profile_image 
                          FROM consultations c
                          JOIN users u ON c.doctor_id = u.user_id
                          WHERE c.patient_id = ?
                          ORDER BY c.consultation_date DESC
                          LIMIT 3";
$recentAppointmentsStmt = $conn->prepare($recentAppointmentsQuery);
$recentAppointmentsStmt->bind_param("i", $user_id);
$recentAppointmentsStmt->execute();
$recentAppointments = $recentAppointmentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - MedMS</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr for date selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css"> 
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="../staff/styles/staff.css">
    <style>
        /* Main Container Styles */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }
        
        .page-header h1 {
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .breadcrumb {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: '/';
            margin: 0 0.5rem;
            color: var(--text-muted);
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--text-muted);
        }
        
        /* Form Section */
        .appointment-form-section {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: box-shadow 0.3s ease;
        }
        
        .appointment-form-section:hover {
            box-shadow: var(--card-shadow-hover);
        }
        
        .section-title {
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-description {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        
        /* Form Layout */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: var(--text-color);
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(var(--primary-color-rgb), 0.25);
        }
        
        /* Doctor Selection */
        .doctor-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .doctor-card {
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #fff;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .doctor-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-color);
            box-shadow: var(--card-shadow-hover);
        }
        
        .doctor-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(var(--primary-color-rgb), 0.1);
        }
        
        .doctor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 0.75rem;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        .doctor-card.selected .doctor-avatar {
            border-color: var(--primary-color);
        }
        
        .doctor-card h4 {
            font-size: 1rem;
            margin: 0 0 0.25rem;
            color: var(--text-color);
        }
        
        .doctor-specialty {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin: 0;
        }
        
        /* Time Slots */
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .time-slot {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            padding: 0.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            background-color: #fff;
        }
        
        .time-slot:hover:not(.unavailable) {
            background-color: rgba(var(--primary-color-rgb), 0.1);
            border-color: var(--primary-color);
        }
        
        .time-slot.selected {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            font-weight: 500;
        }
        
        .time-slot.unavailable {
            background-color: #f1f1f1;
            color: #999;
            cursor: not-allowed;
            opacity: 0.7;
            text-decoration: line-through;
        }
        
        /* Health Summary Card */
        .health-summary-card {
            background-color: rgba(var(--primary-color-rgb), 0.05);
            border: 1px solid rgba(var(--primary-color-rgb), 0.2);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .health-summary-card h4 {
            color: var(--primary-color);
            margin-top: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .health-info-summary {
            margin: 1rem 0;
        }
        
        .health-info-item {
            margin-bottom: 0.5rem;
            padding-left: 1rem;
            border-left: 2px solid rgba(var(--primary-color-rgb), 0.3);
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }
        
        .btn-lg {
            padding: 0.875rem 1.75rem;
            font-size: 1.1rem;
        }
        
        .btn-primary {
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color-dark);
            border-color: var(--primary-color-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            background-color: transparent;
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            color: #fff;
            background-color: var(--primary-color);
        }
        
        .btn-outline-secondary {
            color: var(--text-color);
            background-color: transparent;
            border-color: var(--border-color);
        }
        
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
        }
        
        .btn-danger {
            color: #fff;
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: var(--danger-color-dark);
            border-color: var(--danger-color-dark);
        }
        
        /* Appointment History */
        .appointment-history {
            margin-top: 2.5rem;
        }
        
        .appointment-timeline {
            position: relative;
            padding-left: 2rem;
            margin-top: 1.5rem;
        }
        
        .appointment-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: var(--border-color);
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.75rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2.05rem;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        
        .timeline-item.completed::before {
            background-color: var(--success-color);
            box-shadow: 0 0 0 2px var(--success-color);
        }
        
        .timeline-item.cancelled::before {
            background-color: var(--danger-color);
            box-shadow: 0 0 0 2px var(--danger-color);
        }
        
        .timeline-content {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1rem;
            border-left: 3px solid var(--primary-color);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .timeline-content:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .timeline-item.completed .timeline-content {
            border-left-color: var(--success-color);
        }
        
        .timeline-item.cancelled .timeline-content {
            border-left-color: var(--danger-color);
        }
        
        .timeline-content h4 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        .timeline-content p {
            margin: 0.25rem 0;
            color: var(--text-color);
        }
        
        .appointment-notes {
            background-color: rgba(0, 0, 0, 0.03);
            border-radius: var(--border-radius-sm);
            padding: 0.75rem;
            margin: 0.75rem 0;
            border-left: 2px solid var(--border-color);
        }
        
        .timeline-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            border-radius: 0.25rem;
        }
        
        .badge-scheduled {
            background-color: rgba(var(--primary-color-rgb), 0.1);
            color: var(--primary-color);
        }
        
        .badge-completed {
            background-color: rgba(var(--success-color-rgb), 0.1);
            color: var(--success-color);
        }
        
        .badge-cancelled {
            background-color: rgba(var(--danger-color-rgb), 0.1);
            color: var(--danger-color);
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background-color: rgba(var(--success-color-rgb), 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color-dark);
        }
        
        .alert-danger {
            background-color: rgba(var(--danger-color-rgb), 0.1);
            border: 1px solid var(--danger-color);
            color: var(--danger-color-dark);
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 2rem;
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: var(--border-radius);
            margin: 1rem 0;
        }
        
        .empty-state i {
            font-size: 2rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        .empty-title {
            margin: 0.5rem 0;
            font-size: 1.1rem;
            color: var(--text-color);
        }
        
        .empty-description {
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        /* Animations */
        .animate-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .animate-in.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Helper Classes */
        .d-flex {
            display: flex;
        }
        
        .d-none {
            display: none;
        }
        
        .justify-content-between {
            justify-content: space-between;
        }
        
        .align-items-start {
            align-items: flex-start;
        }
        
        .text-muted {
            color: var(--text-muted);
        }
        
        .my-4 {
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    
    <div class="container my-4">
        <div class="page-header">
            <h1><i class="fas fa-calendar-plus"></i> Book an Appointment</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/medical/staff/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Book Appointment</li>
                </ol>
            </nav>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $successMessage ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $errorMessage ?>
            </div>
        <?php endif; ?>
        
        <div class="appointment-form-section animate-in">
            <form method="POST" action="" id="appointmentForm">
                <h3 class="section-title">Schedule New Appointment</h3>
                <p class="section-description">Select a doctor, date, and time for your appointment</p>
                
                <!-- Doctor Selection -->
                <div class="form-group">
                    <label for="doctor_id"><i class="fas fa-user-md"></i> Select Doctor</label>
                    <div class="doctor-cards">
                        <?php foreach ($doctors as $doctor): ?>
                            <div class="doctor-card" data-doctor-id="<?= $doctor['user_id'] ?>">
                                <img src="<?= $doctor['profile_image'] ?? '/medical/assets/img/default-avatar.png' ?>" 
                                     alt="Dr. <?= htmlspecialchars($doctor['last_name']) ?>" 
                                     class="doctor-avatar"
                                     onerror="this.src='/medical/assets/img/default-avatar.png'">
                                <h4>Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?></h4>
                                <p class="doctor-specialty"><?= htmlspecialchars($doctor['specialization']) ?></p>
                                <input type="radio" name="doctor_id" value="<?= $doctor['user_id'] ?>" class="d-none" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-grid">
                    <!-- Date Selection -->
                    <div class="form-group">
                        <label for="appointment_date"><i class="fas fa-calendar"></i> Select Date</label>
                        <input type="text" class="form-control" id="appointment_date" name="appointment_date" placeholder="Select a date" required>
                    </div>
                    
                    <!-- Time Selection -->
                    <div class="form-group">
                        <label for="appointment_time"><i class="fas fa-clock"></i> Select Time</label>
                        <div class="time-slots" id="timeSlots">
                            <?php foreach ($timeSlots as $timeSlot): ?>
                                <div class="time-slot" data-time="<?= $timeSlot ?>">
                                    <?= $timeSlot ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="appointment_time" id="appointment_time" required>
                    </div>
                </div>
                
                <!-- Reason for Visit -->
                <div class="form-group">
                    <label for="chief_complaint"><i class="fas fa-clipboard"></i> Reason for Visit</label>
                    <select class="form-control" id="chief_complaint" name="chief_complaint" required>
                        <option value="">-- Select Reason --</option>
                        <option value="General Checkup">General Checkup</option>
                        <option value="Feeling Unwell">Feeling Unwell</option>
                        <option value="Follow-up">Follow-up Appointment</option>
                        <option value="Vaccination">Vaccination</option>
                        <option value="Blood Work">Blood Work</option>
                        <option value="Injury Treatment">Injury Treatment</option>
                        <option value="Prescription Renewal">Prescription Renewal</option>
                        <option value="Mental Health">Mental Health Consultation</option>
                        <option value="Other">Other (Specify in Notes)</option>
                    </select>
                </div>
                
                <!-- Additional Notes -->
                <div class="form-group">
                    <label for="notes"><i class="fas fa-notes-medical"></i> Additional Notes (Optional)</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Provide any additional information about your visit"></textarea>
                </div>
                
                <!-- Health History Summary -->
                <?php if ($healthHistory): ?>
                <div class="form-group">
                    <div class="health-summary-card">
                        <h4><i class="fas fa-file-medical"></i> Your Health Summary</h4>
                        <p class="text-muted">The following information will be shared with your doctor</p>
                        <div class="health-info-summary">
                            <?php if (!empty($healthHistory['condition_name'])): ?>
                                <div class="health-info-item">
                                    <strong>Medical Conditions:</strong> <?= htmlspecialchars($healthHistory['condition_name']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($healthHistory['medication'])): ?>
                                <div class="health-info-item">
                                    <strong>Current Medications:</strong> <?= htmlspecialchars($healthHistory['medication']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="/medical/src/modules/health-records/update.php" class="btn btn-sm btn-outline-primary">Update Health Information</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-calendar-check"></i> Schedule Appointment
                    </button>
                    <a href="/medical/staff/dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Recent Appointments -->
        <div class="appointment-history animate-in" style="animation-delay: 0.2s;">
            <h3 class="section-title"><i class="fas fa-history"></i> Recent Appointment History</h3>
            
            <?php if (!empty($recentAppointments)): ?>
                <div class="appointment-timeline">
                    <?php foreach ($recentAppointments as $appointment): ?>
                        <div class="timeline-item <?= strtolower($appointment['status']) ?>">
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h4><?= htmlspecialchars($appointment['chief_complaint'] ?? 'Appointment') ?></h4>
                                        <p>
                                            <i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($appointment['consultation_date'])) ?> at 
                                            <?= date('h:i A', strtotime($appointment['consultation_date'])) ?>
                                        </p>
                                        <p>
                                            <i class="fas fa-user-md"></i> Dr. <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?>
                                        </p>
                                    </div>
                                    <span class="badge badge-<?= strtolower($appointment['status']) ?>">
                                        <?= htmlspecialchars($appointment['status']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($appointment['notes'])): ?>
                                    <div class="appointment-notes">
                                        <p><i class="fas fa-sticky-note"></i> <?= htmlspecialchars($appointment['notes']) ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="timeline-actions">
                                    <a href="/medical/src/modules/appointment/view.php?id=<?= $appointment['consultation_id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <?php if ($appointment['status'] == 'Scheduled'): ?>
                                        <a href="/medical/src/modules/appointment/cancel.php?id=<?= $appointment['consultation_id'] ?>" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4 class="empty-title">No Recent Appointments</h4>
                    <p class="empty-description">You don't have any recent appointment history.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Flatpickr JS for date picking -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize animations
            const animateItems = document.querySelectorAll('.animate-in');
            animateItems.forEach(item => {
                item.classList.add('show');
            });
            
            // Initialize date picker
            flatpickr("#appointment_date", {
                minDate: "today",
                maxDate: new Date().fp_incr(30), // Allow booking 30 days in advance
                disable: [
                    function(date) {
                        // Disable weekends
                        return (date.getDay() === 0 || date.getDay() === 6);
                    }
                ],
                locale: {
                    firstDayOfWeek: 1 // Start with Monday
                }
            });
            
            // Doctor selection
            const doctorCards = document.querySelectorAll('.doctor-card');
            doctorCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    doctorCards.forEach(c => c.classList.remove('selected'));
                    // Add selected class to clicked card
                    this.classList.add('selected');
                    // Check the radio button
                    const radioBtn = this.querySelector('input[type="radio"]');
                    radioBtn.checked = true;
                    
                    // Simulate checking availability for the selected doctor
                    checkAvailability();
                });
            });
            
            // Time slot selection
            const timeSlots = document.querySelectorAll('.time-slot');
            timeSlots.forEach(slot => {
                slot.addEventListener('click', function() {
                    if (!this.classList.contains('unavailable')) {
                        // Remove selected class from all slots
                        timeSlots.forEach(s => s.classList.remove('selected'));
                        // Add selected class to clicked slot
                        this.classList.add('selected');
                        // Set the hidden input value
                        document.getElementById('appointment_time').value = this.getAttribute('data-time');
                    }
                });
            });
            
            // Check availability when date changes
            document.getElementById('appointment_date').addEventListener('change', function() {
                checkAvailability();
            });
            
            // Function to simulate checking availability
            function checkAvailability() {
                const selectedDate = document.getElementById('appointment_date').value;
                const selectedDoctorCard = document.querySelector('.doctor-card.selected');
                
                if (selectedDate && selectedDoctorCard) {
                    // Reset all slots
                    timeSlots.forEach(slot => {
                        slot.classList.remove('unavailable');
                        slot.classList.remove('selected');
                    });
                    
                    // Simulate some unavailable slots (in a real app, this would be an AJAX call to check actual availability)
                    const unavailableSlots = ['09:00 AM', '11:30 AM', '02:00 PM']; // Example
                    
                    unavailableSlots.forEach(unavailableTime => {
                        const slot = document.querySelector(`.time-slot[data-time="${unavailableTime}"]`);
                        if (slot) {
                            slot.classList.add('unavailable');
                        }
                    });
                    
                    // Clear selected time
                    document.getElementById('appointment_time').value = '';
                }
            }
            
            // Form validation
            document.getElementById('appointmentForm').addEventListener('submit', function(e) {
                const doctorSelected = document.querySelector('input[name="doctor_id"]:checked');
                const dateSelected = document.getElementById('appointment_date').value;
                const timeSelected = document.getElementById('appointment_time').value;
                
                if (!doctorSelected || !dateSelected || !timeSelected) {
                    e.preventDefault();
                    alert('Please select a doctor, date, and time slot for your appointment.');
                }
            });
        });
    </script>
</body>
</html>