<?php
session_start();
require_once '../../../../../config/config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'teacher', 'staff'])) {
    header("Location: /medical/src/auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];

// Fetch all doctors & nurses
$providers = [];
$sql = "
    SELECT u.user_id, u.first_name, u.last_name, r.role_name,
           CASE 
               WHEN d.specialization IS NOT NULL THEN d.specialization
               WHEN n.specialization IS NOT NULL THEN n.specialization
               ELSE NULL 
           END as specialization
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    LEFT JOIN doctors d ON u.user_id = d.user_id
    LEFT JOIN nurses n ON u.user_id = n.user_id
    WHERE r.role_name IN ('doctor', 'nurse')
    ORDER BY r.role_name, u.last_name, u.first_name
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
    $stmt->close();
}

// Fetch consultation types
$consultationTypes = [];
$sql = "SELECT * FROM consultation_types ORDER BY name";
if ($stmt = $conn->prepare($sql)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $consultationTypes[] = $row;
    }
    $stmt->close();
}

// Fetch patient's consultations
$consultations = [];
$sql = "SELECT c.*, 
               u1.first_name as provider_first_name, u1.last_name as provider_last_name,
               u2.first_name as confirmer_first_name, u2.last_name as confirmer_last_name,
               ct.name as consultation_type_name
        FROM consultations c
        JOIN users u1 ON c.doctor_id = u1.user_id
        LEFT JOIN users u2 ON c.confirmed_by = u2.user_id
        LEFT JOIN consultation_types ct ON c.consultation_type_id = ct.consultation_type_id
        WHERE c.patient_id = ? 
        ORDER BY c.consultation_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $consultations[] = $row;
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider_id = intval($_POST['provider_id'] ?? 0);
    $consultation_date = trim($_POST['consultation_date'] ?? '');
    $consultation_time = trim($_POST['consultation_time'] ?? '');
    $consultation_type_id = intval($_POST['consultation_type_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $symptoms = trim($_POST['symptoms'] ?? '');

    if ($provider_id <= 0) {
        $errors[] = "Please select a doctor or nurse.";
    }
    if (empty($consultation_date)) {
        $errors[] = "Please select a date.";
    }
    if (empty($consultation_time)) {
        $errors[] = "Please select a time.";
    }
    if ($consultation_type_id <= 0) {
        $errors[] = "Please select a consultation type.";
    }
    if (empty($reason)) {
        $errors[] = "Please provide a reason for the consultation.";
    }

    if (empty($errors)) {
        $datetime = $consultation_date . ' ' . $consultation_time;
        $sql = "INSERT INTO consultations 
                (patient_id, doctor_id, consultation_date, consultation_type_id, reason, symptoms, status, confirmation_status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Scheduled', 'pending')";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("iisiss", $user_id, $provider_id, $datetime, $consultation_type_id, $reason, $symptoms);
            if ($stmt->execute()) {
                header("Location: consultation.php?success=added");
                exit();
            } else {
                $errors[] = "Database error: Could not schedule consultation.";
            }
            $stmt->close();
        }
    }
}

// Handle consultation cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_consultation') {
    $consultation_id = (int)$_POST['consultation_id'];
    
    $sql = "UPDATE consultations SET status = 'Cancelled', confirmation_status = 'rejected' WHERE consultation_id = ? AND patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $consultation_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: consultation.php?success=1");
    } else {
        header("Location: consultation.php?error=1");
    }
    exit();
}

// Handle consultation deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_consultation') {
    $consultation_id = (int)$_POST['consultation_id'];
    $sql = "DELETE FROM consultations WHERE consultation_id = ? AND patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $consultation_id, $user_id);
    if ($stmt->execute()) {
        header("Location: consultation.php?success=1");
    } else {
        header("Location: consultation.php?error=1");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations - Medical Management System</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/user/styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php include_once '../../../../../includes/header.php'; ?>

<div class="consultation-page">
    <div class="section-header">
        <h2 class="section-title">Medical Consultations</h2>
        <button class="btn btn-primary" id="open-consultation-modal">Request Consultation</button>
    </div>

    <div class="centered-table-container">
        <table class="centered-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Provider</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($consultations)): ?>
                    <?php foreach ($consultations as $consult): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($consult['consultation_date'])) ?></td>
                            <td><?= date('h:i A', strtotime($consult['consultation_date'])) ?></td>
                            <td>
                                <?= ucfirst($consult['provider_first_name'] . ' ' . $consult['provider_last_name']) ?>
                                <?php if (isset($consult['specialization']) && $consult['specialization']): ?>
                                    <span class="text-muted">(<?= htmlspecialchars($consult['specialization']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($consult['consultation_type_name']) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($consult['status']) ?>">
                                    <?= ucfirst($consult['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-primary view-consult-btn" data-consult='<?= json_encode($consult) ?>'><i class="fas fa-eye"></i></button>
                                    <?php if ($consult['status'] === 'Scheduled'): ?>
                                        <button class="btn btn-danger cancel-consult-btn" data-id="<?= $consult['consultation_id'] ?>"><i class="fas fa-times-circle"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No consultations found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Request Consultation Modal -->
<div class="modal" id="consultationModal">
    <div class="modal-content">
        <button class="close-modal" id="closeConsultationModal">&times;</button>
        <h2>Request Medical Consultation</h2>
        <form method="post" class="form-grid">
            <div class="form-group">
                <label for="provider_id">Doctor / Nurse</label>
                <select name="provider_id" id="provider_id" class="form-control" required>
                    <option value="">Select provider</option>
                    <?php foreach ($providers as $p): ?>
                        <option value="<?= $p['user_id'] ?>">
                            <?= ucfirst($p['role_name']) ?> <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                            <?php if ($p['specialization']): ?>
                                (<?= htmlspecialchars($p['specialization']) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="consultation_type_id">Consultation Type</label>
                <select name="consultation_type_id" id="consultation_type_id" class="form-control" required>
                    <option value="">Select type</option>
                    <?php foreach ($consultationTypes as $type): ?>
                        <option value="<?= $type['consultation_type_id'] ?>">
                            <?= htmlspecialchars($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="consultation_date">Date</label>
                <input type="date" name="consultation_date" id="consultation_date" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="consultation_time">Time</label>
                <input type="time" name="consultation_time" id="consultation_time" class="form-control" required>
            </div>
            <div class="form-group-full">
                <label for="reason">Reason for Consultation</label>
                <textarea name="reason" id="reason" class="form-control" rows="3" required></textarea>
            </div>
            <div class="form-group-full">
                <label for="symptoms">Symptoms (if any)</label>
                <textarea name="symptoms" id="symptoms" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-footer">
                <button type="button" class="btn btn-outline" id="cancelConsultation">Cancel</button>
                <button type="submit" class="btn btn-primary">Request Consultation</button>
            </div>
        </form>
    </div>
</div>

<!-- View Consultation Modal -->
<div class="modal" id="viewConsultationModal">
    <div class="modal-content">
        <button class="close-modal" id="closeViewModal">&times;</button>
        <h2>Consultation Details</h2>
        <div id="consultationDetails"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Modal handling
const consultationModal = document.getElementById('consultationModal');
const viewConsultationModal = document.getElementById('viewConsultationModal');
const openConsultationBtn = document.getElementById('open-consultation-modal');
const closeConsultationBtn = document.getElementById('closeConsultationModal');
const cancelConsultationBtn = document.getElementById('cancelConsultation');
const closeViewBtn = document.getElementById('closeViewModal');

openConsultationBtn.onclick = () => consultationModal.style.display = 'flex';
closeConsultationBtn.onclick = () => consultationModal.style.display = 'none';
cancelConsultationBtn.onclick = () => consultationModal.style.display = 'none';
closeViewBtn.onclick = () => viewConsultationModal.style.display = 'none';

// Close modals when clicking outside
window.onclick = (event) => {
    if (event.target === consultationModal) consultationModal.style.display = 'none';
    if (event.target === viewConsultationModal) viewConsultationModal.style.display = 'none';
};

// View consultation details
document.querySelectorAll('.view-consult-btn').forEach(btn => {
    btn.onclick = function() {
        const consult = JSON.parse(this.dataset.consult);
        document.getElementById('consultationDetails').innerHTML = `
            <div class="consultation-details">
                <p><strong>Date:</strong> ${new Date(consult.consultation_date).toLocaleDateString()}</p>
                <p><strong>Time:</strong> ${new Date(consult.consultation_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                <p><strong>Provider:</strong> ${consult.provider_first_name} ${consult.provider_last_name}</p>
                <p><strong>Type:</strong> ${consult.consultation_type_name}</p>
                <p><strong>Reason:</strong> ${consult.reason}</p>
                <p><strong>Status:</strong> <span class="status-badge ${consult.confirmation_status}">${consult.confirmation_status}</span></p>
                ${consult.symptoms ? `<p><strong>Symptoms:</strong> ${consult.symptoms}</p>` : ''}
                ${consult.confirmation_notes ? `<p><strong>Provider Notes:</strong> ${consult.confirmation_notes}</p>` : ''}
            </div>
        `;
        viewConsultationModal.style.display = 'flex';
    };
});

// Cancel consultation
document.querySelectorAll('.cancel-consult-btn').forEach(btn => {
    btn.onclick = function() {
        const consultationId = this.dataset.id;
        
        Swal.fire({
            title: 'Cancel Consultation',
            text: 'Are you sure you want to cancel this consultation?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel it',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel_consultation">
                    <input type="hidden" name="consultation_id" value="${consultationId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    };
});

// Delete consultation confirmation
document.querySelectorAll('.delete-consult-btn').forEach(btn => {
    btn.onclick = function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Delete Consultation',
            text: 'Are you sure you want to delete this consultation?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.closest('form').submit();
            }
        });
    };
});

// Success/Error messages
<?php if (isset($_GET['success'])): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'Operation successfully!',
        allowOutsideClick: true,
        allowEscapeKey: true
    }).then(() => {
        if (window.location.search.includes('success=1')) {
            const url = new URL(window.location.href);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    });
    </script>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'There was an error processing your request.',
        allowOutsideClick: true,
        allowEscapeKey: true
    });
    </script>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Consultation Requested!',
    text: 'Your consultation has been successfully scheduled.',
    allowOutsideClick: true,
    allowEscapeKey: true
}).then(() => {
    if (window.location.search.includes('success=added')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
});
</script>
<?php endif; ?>

// Set minimum date to today for consultation date
document.getElementById('consultation_date').min = new Date().toISOString().split('T')[0];
</script>
</body>
</html>
