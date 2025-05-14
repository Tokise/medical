<?php
session_start();
require_once '../../../../../config/config.php';

// 1) Only students allowed
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: /medical/src/auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$errors = [];

// 2) Fetch all doctors & nurses
$providers = [];
$sql = "
  SELECT u.user_id, u.first_name, u.last_name, 'doctor' AS role
    FROM users u
    JOIN doctors d ON u.user_id = d.user_id

  UNION

  SELECT u.user_id, u.first_name, u.last_name, 'nurse' AS role
    FROM users u
    JOIN nurses n ON u.user_id = n.user_id

  ORDER BY role, last_name, first_name
";
if ($stmt = $conn->prepare($sql)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
    $stmt->close();
}

// 3) Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider_id   = intval($_POST['provider_id'] ?? 0);
    $datetime_raw  = trim($_POST['consultation_datetime'] ?? '');
    $reason        = trim($_POST['reason'] ?? '');

    if ($provider_id <= 0) {
        $errors[] = "Please select a doctor or nurse.";
    }
    if (empty($datetime_raw)) {
        $errors[] = "Please choose a date & time.";
    } else {
        // convert to MySQL DATETIME
        $dt = date_create($datetime_raw);
        if (!$dt) {
            $errors[] = "Invalid date/time format.";
        } else {
            $consultation_date = $dt->format('Y-m-d H:i:s');
        }
    }
    if (empty($reason)) {
        $errors[] = "Please tell us briefly why you need this appointment.";
    }

    // Insert if no errors
    if (empty($errors)) {
        $ins = "INSERT INTO consultations
                  (patient_id, doctor_id, consultation_date, reason, status)
                VALUES
                  (?, ?, ?, ?, 'Scheduled')";
        if ($insertStmt = $conn->prepare($ins)) {
            $insertStmt->bind_param(
                "iiss",
                $user_id,
                $provider_id,
                $consultation_date,
                $reason
            );
            if ($insertStmt->execute()) {
                // success → back to list
                header("Location: /medical/src/modules/dashboard/patient/appointments/index.php?msg=scheduled");
                exit;
            } else {
                $errors[] = "Database error: could not schedule. Please try again.";
            }
            $insertStmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointment - Medical Management System</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/student/styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php include_once '../../../../../includes/header.php'; ?>

<div class="student-dashboard">
    <div class="dashboard-grid">
        <div class="dashboard-column">
            <div class="section-header">
                <h2 class="section-title">Schedule New Appointment</h2>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="form-grid">
                <div class="form-group">
                    <label for="provider_id">Doctor / Nurse</label>
                    <select name="provider_id" id="provider_id" class="form-control">
                        <option value="">— Select —</option>
                        <?php foreach($providers as $p): ?>
                            <option value="<?= $p['user_id'] ?>"
                                <?= (isset($provider_id) && $provider_id == $p['user_id']) ? 'selected' : '' ?>>
                                <?= ucfirst($p['role']) ?>  
                                Dr. <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="consultation_datetime">Date &amp; Time</label>
                    <input
                        type="datetime-local"
                        id="consultation_datetime"
                        name="consultation_datetime"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['consultation_datetime'] ?? '') ?>"
                    />
                </div>

                <div class="form-group-full">
                    <label for="reason">Reason for Appointment</label>
                    <textarea
                        id="reason"
                        name="reason"
                        class="form-control"
                        rows="4"
                        placeholder="Briefly describe your concern…"
                    ><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                </div>

                <div class="form-footer">
                    <a href="/medical/src/modules/dashboard/patient/appointments/index.php" class="btn btn-secondary">
                        ← Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Schedule Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add animation classes after DOM is loaded
    setTimeout(() => {
        document.querySelectorAll('.form-group').forEach((group, index) => {
            setTimeout(() => {
                group.classList.add('animate-in');
            }, index * 100);
        });
    }, 300);
});
</script>

</body>
</html>