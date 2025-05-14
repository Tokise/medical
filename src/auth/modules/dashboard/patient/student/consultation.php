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

// Get student data
try {
    $studentQuery = "SELECT * FROM students WHERE user_id = ?";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->bind_param("i", $user_id);
    $studentStmt->execute();
    $student = $studentStmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    error_log("Error fetching student data: " . $e->getMessage());
    $student = null;
}

// Get consultation details if editing
$consultation_id = isset($_GET['id']) ? $_GET['id'] : null;
if ($consultation_id) {
    try {
        $consultationQuery = "SELECT * FROM consultations WHERE consultation_id = ?";
        $consultationStmt = $conn->prepare($consultationQuery);
        $consultationStmt->bind_param("i", $consultation_id);
        $consultationStmt->execute();
        $consultation = $consultationStmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error fetching consultation: " . $e->getMessage());
        $consultation = null;
    }
} else {
    $consultation = null;
}

// Get doctor list for dropdown
try {
    $doctorQuery = "SELECT u.user_id, u.first_name, u.last_name, d.specialization 
                   FROM users u 
                   LEFT JOIN doctors d ON u.user_id = d.user_id 
                   WHERE u.role = 'doctor'";
    $doctorStmt = $conn->prepare($doctorQuery);
    $doctorStmt->execute();
    $doctors = $doctorStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching doctors: " . $e->getMessage());
    $doctors = [];
}

// Get consultation types
$consultationTypes = [
    'General Check-up' => 'General health check-up',
    'Follow-up' => 'Follow-up consultation',
    'Specialist Consultation' => 'Specialist consultation',
    'Urgent Care' => 'Urgent care consultation'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $consultation_date = $_POST['consultation_date'];
        $doctor_id = $_POST['doctor_id'];
        $type = $_POST['type'];
        $reason = $_POST['reason'];
        $symptoms = $_POST['symptoms'];

        if (!$consultation_id) {
            // Insert new consultation
            $insertQuery = "INSERT INTO consultations 
                           (patient_id, doctor_id, consultation_date, type, reason, symptoms, status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iissss", 
                                  $user_id, 
                                  $doctor_id, 
                                  $consultation_date, 
                                  $type, 
                                  $reason, 
                                  $symptoms);
            $insertStmt->execute();
            header("Location: /medical/src/modules/dashboard/patient/appointments/index.php?success=consultation_added");
            exit;
        } else {
            // Update existing consultation
            $updateQuery = "UPDATE consultations 
                           SET doctor_id = ?, consultation_date = ?, type = ?, reason = ?, symptoms = ?
                           WHERE consultation_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("issssi", 
                                  $doctor_id, 
                                  $consultation_date, 
                                  $type, 
                                  $reason, 
                                  $symptoms, 
                                  $consultation_id);
            $updateStmt->execute();
            header("Location: /medical/src/modules/dashboard/patient/appointments/index.php?success=consultation_updated");
            exit;
        }
    } catch (Exception $e) {
        error_log("Error processing consultation: " . $e->getMessage());
        header("Location: /medical/src/modules/dashboard/patient/appointments/index.php?error=consultation_error");
        exit;
    }
}

// Pass the role to be used in the sidebar
$role = 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Form - Medical Management System</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/student/styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .consultation-form {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-header {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-primary);
            font-weight: bold;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }

        .form-textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            resize: vertical;
        }

        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: var(--secondary-hover);
        }
    </style>
</head>
<body>
<?php include_once '../../../../../includes/header.php'; ?>

<div class="student-dashboard">
    <form class="consultation-form" action="/medical/src/modules/dashboard/patient/appointments/consultation.php" method="POST">
        <div class="form-section">
            <div class="form-section-header">
                <h2 class="section-title">Consultation Details</h2>
            </div>
            <div class="form-group">
                <label class="form-label" for="doctor_id">Doctor</label>
                <select class="form-select" id="doctor_id" name="doctor_id" required>
                    <option value="">Select a doctor</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= $doctor['user_id'] ?>" 
                            <?= isset($consultation) && $consultation['doctor_id'] == $doctor['user_id'] ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                            <?= isset($doctor['specialization']) ? ' (' . htmlspecialchars($doctor['specialization']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="consultation_date">Date</label>
                <input type="date" class="form-input" id="consultation_date" name="consultation_date" 
                       value="<?= isset($consultation) ? $consultation['consultation_date'] : '' ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="type">Consultation Type</label>
                <select class="form-select" id="type" name="type" required>
                    <option value="">Select type</option>
                    <?php foreach ($consultationTypes as $typeKey => $typeValue): ?>
                        <option value="<?= $typeKey ?>" 
                            <?= isset($consultation) && $consultation['type'] == $typeKey ? 'selected' : '' ?>>
                            <?= $typeValue ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-header">
                <h2 class="section-title">Reason for Consultation</h2>
            </div>
            <div class="form-group">
                <label class="form-label" for="reason">Main Reason</label>
                <input type="text" class="form-input" id="reason" name="reason" 
                       value="<?= isset($consultation) ? $consultation['reason'] : '' ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="symptoms">Symptoms (if any)</label>
                <textarea class="form-textarea" id="symptoms" name="symptoms" 
                          placeholder="Describe your symptoms (e.g., fever, headache, etc.)">
                    <?= isset($consultation) ? $consultation['symptoms'] : '' ?>
                </textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="/medical/src/modules/dashboard/patient/appointments/index.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Consultation</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date picker
    const dateInput = document.getElementById('consultation_date');
    if (dateInput) {
        dateInput.min = new Date().toISOString().split('T')[0];
    }
});
</script>

</body>
</html>
