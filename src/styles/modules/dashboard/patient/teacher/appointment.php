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

// Handle appointment booking
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $consultation_date = $_POST['consultation_date'] ?? '';
    $consultation_type = $_POST['consultation_type'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validate inputs
    if (empty($doctor_id) || empty($consultation_date) || empty($consultation_type)) {
        $error = 'Please fill all required fields';
    } else {
        // Check doctor availability
        $conflictCheck = "SELECT consultation_id FROM consultations 
                         WHERE doctor_id = ? AND consultation_date = ? 
                         AND status != 'Cancelled'";
        $stmt = $conn->prepare($conflictCheck);
        $stmt->bind_param("is", $doctor_id, $consultation_date);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Selected time is not available. Please choose another time.';
        } else {
            // Insert new appointment
            $insertQuery = "INSERT INTO consultations 
                           (patient_id, doctor_id, consultation_date, consultation_type, notes, status) 
                           VALUES (?, ?, ?, ?, ?, 'Scheduled')";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("iisss", $user_id, $doctor_id, $consultation_date, $consultation_type, $notes);
            
            if ($stmt->execute()) {
                $success = 'Appointment booked successfully!';
            } else {
                $error = 'Error booking appointment: ' . $conn->error;
            }
        }
    }
}

// Get teacher's appointments
$appointmentsQuery = "SELECT c.*, u.first_name, u.last_name, u.profile_image 
                     FROM consultations c
                     JOIN users u ON c.doctor_id = u.user_id
                     WHERE c.patient_id = ?
                     ORDER BY c.consultation_date DESC";
$appointmentsStmt = $conn->prepare($appointmentsQuery);
$appointmentsStmt->bind_param("i", $user_id);
$appointmentsStmt->execute();
$appointments = $appointmentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get available doctors
$doctorsQuery = "SELECT u.user_id, u.first_name, u.last_name, d.specialization 
                FROM users u
                JOIN doctors d ON u.user_id = d.user_id
                WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'Doctor')
                ORDER BY u.last_name ASC";
$doctors = $conn->query($doctorsQuery)->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - MedMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="styles/teacher.css">
</head>
<body>
    <?php include_once '../../../../../includes/header.php'; ?>
    
    <div class="teacher-dashboard">
        <!-- Appointment Management Header -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>Manage Appointments</h1>
                <p>Schedule new consultations or manage existing appointments with school medical staff.</p>
            </div>
            <div class="welcome-image">
                <img src="/medical/assets/img/appointments.svg" alt="Appointments" onerror="this.src='/medical/assets/img/default-banner.png'">
            </div>
        </div>

        <!-- Booking Section -->
        <div class="dashboard-grid">
            <div class="dashboard-column">
                <div class="booking-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-plus"></i>
                            Book New Appointment
                        </h3>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="POST" class="appointment-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="doctor_id">Select Doctor</label>
                                <select name="doctor_id" id="doctor_id" class="form-control" required>
                                    <option value="">Choose a doctor</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?= $doctor['user_id'] ?>">
                                            Dr. <?= htmlspecialchars($doctor['first_name']) ?> <?= htmlspecialchars($doctor['last_name']) ?> 
                                            (<?= htmlspecialchars($doctor['specialization']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="consultation_date">Date & Time</label>
                                <input type="datetime-local" name="consultation_date" id="consultation_date" 
                                       class="form-control date-picker" required>
                            </div>

                            <div class="form-group">
                                <label for="consultation_type">Consultation Type</label>
                                <select name="consultation_type" id="consultation_type" class="form-control" required>
                                    <option value="General Checkup">General Checkup</option>
                                    <option value="Follow-up">Follow-up</option>
                                    <option value="Vaccination">Vaccination</option>
                                    <option value="Mental Health">Mental Health</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="notes">Additional Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i>
                            Book Appointment
                        </button>
                    </form>
                </div>
            </div>

            <!-- Appointments List -->
            <div class="dashboard-column">
                <div class="appointments-list-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-list-alt"></i>
                            Your Appointments
                        </h3>
                    </div>

                    <div class="appointments-container">
                        <?php if (count($appointments) > 0): ?>
                            <?php foreach ($appointments as $appointment): ?>
                                <?php 
                                $appointmentDate = new DateTime($appointment['consultation_date']);
                                $status = $appointment['status'];
                                ?>
                                <div class="appointment-item animate-in">
                                    <div class="appointment-time">
                                        <div class="time-month"><?= $appointmentDate->format('M') ?></div>
                                        <div class="time-day"><?= $appointmentDate->format('d') ?></div>
                                        <div class="time-hour"><?= $appointmentDate->format('h:i A') ?></div>
                                    </div>
                                    <div class="appointment-content">
                                        <h4 class="appointment-title">
                                            Dr. <?= htmlspecialchars($appointment['last_name']) ?>
                                            <span class="consultation-type">(<?= htmlspecialchars($appointment['consultation_type']) ?>)</span>
                                        </h4>
                                        <?php if (!empty($appointment['notes'])): ?>
                                            <p class="appointment-notes"><?= htmlspecialchars($appointment['notes']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appointment-actions">
                                        <div class="appointment-status <?= strtolower($status) ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </div>
                                        <?php if ($status === 'Scheduled' && $appointmentDate > new DateTime()): ?>
                                            <form method="POST" action="cancel.php" class="cancel-form">
                                                <input type="hidden" name="appointment_id" value="<?= $appointment['consultation_id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i>
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <h4 class="empty-title">No Appointments Found</h4>
                                <p class="empty-description">You haven't booked any appointments yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date/time picker
        flatpickr("#consultation_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: false,
            minuteIncrement: 15,
            disableMobile: true
        });

        // Animate appointment items
        document.querySelectorAll('.appointment-item').forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
        });
    });
    </script>
</body>
</html>