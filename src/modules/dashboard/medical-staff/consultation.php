<?php
session_start();
require_once '../../../../config/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor', 'nurse'])) {
    header('Location: /medical/login.php');
    exit();
}

$user_id = $_SESSION['id'];
$fullname = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

// Fetch consultations for this doctor
$consultations = [];
$sql = "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as patient_name, 
        c.status as consultation_status 
        FROM consultations c 
        JOIN users u ON c.patient_id = u.user_id 
        WHERE c.doctor_id = ? 
        ORDER BY c.consultation_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $consultations[] = $row;
}

// Fetch all patients for dropdown
$patients = [];
$sql = "SELECT user_id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role_id IN (SELECT role_id FROM roles WHERE role_name IN ('student','teacher','staff')) ORDER BY name";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $patients[] = $row;
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $consultation_id = (int)$_POST['consultation_id'];
        $new_status = $_POST['status'];
        
        // Update both status and confirmation_status
        $sql = "UPDATE consultations SET status = ?, confirmation_status = ? WHERE consultation_id = ? AND doctor_id = ?";
        $stmt = $conn->prepare($sql);
        $confirmation_status = ($new_status === 'Completed') ? 'confirmed' : (($new_status === 'Confirmed') ? 'confirmed' : 'pending');
        $stmt->bind_param("ssii", $new_status, $confirmation_status, $consultation_id, $user_id);
        
        if ($stmt->execute()) {
            header("Location: consultation.php?success=1");
        } else {
            header("Location: consultation.php?error=1");
        }
        exit();
    }
    
    // Handle consultation deletion
    if ($_POST['action'] === 'delete_consultation') {
        $consultation_id = (int)$_POST['consultation_id'];
        
        $sql = "DELETE FROM consultations WHERE consultation_id = ? AND doctor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $consultation_id, $user_id);
        
        if ($stmt->execute()) {
            header("Location: consultation.php?success=1");
        } else {
            header("Location: consultation.php?error=1");
        }
        exit();
    }
}

// Handle add prescription from modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_prescription') {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = $_SESSION['id'];
    $diagnosis = $_POST['diagnosis'] ?? '';
    $issue_date = date('Y-m-d');
    $status = 'Active';
    $medication_name = $_POST['medication_name'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $intake = $_POST['intake'] ?? '';

    $sql = "INSERT INTO prescriptions (user_id, doctor_id, diagnosis, issue_date, status, prescribed_by) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssi", $patient_id, $doctor_id, $diagnosis, $issue_date, $status, $doctor_id);
    if ($stmt->execute()) {
        $prescription_id = $conn->insert_id;
        // Insert medication details (no medication_id, use medication_name)
        $sqlMed = "INSERT INTO prescription_medications (prescription_id, medication_name, dosage, duration, notes) VALUES (?, ?, ?, ?, ?)";
        $stmtMed = $conn->prepare($sqlMed);
        $stmtMed->bind_param("issss", $prescription_id, $medication_name, $dosage, $duration, $intake);
        $stmtMed->execute();
        header("Location: consultation.php?success=prescribed");
        exit();
    } else {
        header("Location: consultation.php?error=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Consultations - Medical Management</title>
    <link rel="stylesheet" href="../../../../src/styles/global.css">
    <link rel="stylesheet" href="../../../../src/styles/components.css">
    <link rel="stylesheet" href="./styles/appointment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .main-content {
            padding: 2rem;
            margin-top: 60px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .table-container {
            background: var(--bg-white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-top: 1.5rem;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table th,
        .data-table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }

        .data-table th {
            font-weight: 600;
            color: var(--text-primary);
            background: var(--bg-light);
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tr:hover {
            background-color: var(--bg-light);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            margin: 0 0.25rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-color-dark);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: var(--danger-color-dark);
            transform: translateY(-2px);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--bg-white);
            margin: auto;
            padding: 2.5rem 2rem 2rem 2rem;
            border-radius: 18px;
            width: 100%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
        }

        .close-modal {
            position: absolute;
            top: 1.2rem;
            right: 1.2rem;
            width: 36px;
            height: 36px;
            background: var(--bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--text-muted);
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            background: var(--border-light);
            color: var(--text-primary);
        }

        .modal-divider {
            border: none;
            border-top: 2px solid var(--border-light);
            margin: 1.5rem 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        @keyframes fadeInModal {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .data-table {
                display: block;
                overflow-x: auto;
            }

            .btn {
                width: 100%;
            }

            .modal-content {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
<?php include_once('../../../../includes/header.php'); ?>
<section class="main-content">
    <div class="container">
        <h1 class="page-title">Consultations</h1>
        <div class="table-container">
            <table class="data-table" id="consultationsTable">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultations as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['patient_name']) ?></td>
                        <td><?= htmlspecialchars($c['consultation_date']) ?></td>
                        <td><?= htmlspecialchars($c['notes']) ?></td>
                        <td><?= htmlspecialchars($c['consultation_status']) ?></td>
                        <td>
                            <button type="button" class="btn btn-primary view-consultation-btn" data-id="<?= $c['consultation_id'] ?>">View</button>
                            <?php if ($c['consultation_status'] === 'Scheduled'): ?>
                                <button type="button" class="btn btn-primary confirm-consultation-btn" data-id="<?= $c['consultation_id'] ?>">Confirm</button>
                            <?php elseif ($c['consultation_status'] === 'Confirmed'): ?>
                                <button type="button" class="btn btn-primary complete-consultation-btn" data-id="<?= $c['consultation_id'] ?>">Done</button>
                            <?php endif; ?>
                            <?php if ($c['consultation_status'] !== 'Completed'): ?>
                                <button type="button" class="btn btn-primary prescribe-btn" data-id="<?= $c['consultation_id'] ?>" data-patient="<?= htmlspecialchars($c['patient_name']) ?>" data-patientid="<?= $c['patient_id'] ?>">Prescribe</button>
                            <?php endif; ?>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="action" value="delete_consultation">
                                <input type="hidden" name="consultation_id" value="<?= $c['consultation_id'] ?>">
                                <button type="submit" class="btn btn-danger delete-consultation-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<!-- Add/Edit/View Consultation Modal -->
<div class="modal" id="consultationModal">
    <div class="modal-content" id="consultationModalContent">
        <button class="close-modal" id="closeConsultationModal" aria-label="Close">&times;</button>
        <h2 id="consultationModalTitle">Consultation</h2>
        <hr class="modal-divider" />
        <form method="POST" action="" id="consultationForm">
            <input type="hidden" name="action" id="consultation_action" value="add_consultation">
            <input type="hidden" name="consultation_id" id="consultation_id">
            <div class="form-group" id="consultationPatientGroup">
                <label for="consultation_patient">Patient</label>
                <select id="consultation_patient" name="patient" required>
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['user_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="consultation_date">Date</label>
                <input type="date" id="consultation_date" name="date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="consultation_notes">Notes</label>
                <textarea id="consultation_notes" name="notes" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary" id="consultationSubmitBtn">Save</button>
        </form>
    </div>
</div>
<!-- Add Prescription Modal -->
<div class="modal" id="prescriptionModal">
    <div class="modal-content" id="prescriptionModalContent">
        <button class="close-modal" id="closePrescriptionModal" aria-label="Close">&times;</button>
        <h2 id="prescriptionModalTitle">Add Prescription</h2>
        <hr class="modal-divider" />
        <form method="POST" action="" id="prescriptionForm">
            <input type="hidden" name="action" value="add_prescription">
            <input type="hidden" name="consultation_id" id="consultation_id">
            <input type="hidden" name="patient_id" id="patient_id">
            
            <div class="form-group">
                <label>Patient</label>
                <input type="text" id="patient_name" readonly>
            </div>
            
            <div class="form-group">
                <label for="medication_name">Medication</label>
                <input type="text" id="medication_name" name="medication_name" placeholder="Enter medication name" required />
            </div>
            
            <div class="form-group">
                <label for="dosage">Dosage</label>
                <input type="text" id="dosage" name="dosage" placeholder="e.g. 500mg" required />
            </div>
            
            <div class="form-group">
                <label for="duration">Duration</label>
                <input type="text" id="duration" name="duration" placeholder="e.g. 5 days" required />
            </div>
            
            <div class="form-group">
                <label for="intake">Intake Instructions</label>
                <textarea id="intake" name="intake" placeholder="e.g. Take after meals, twice daily" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Prescription</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const consultationModal = document.getElementById('consultationModal');
    const prescriptionModal = document.getElementById('prescriptionModal');
    const closeConsultationModal = document.getElementById('closeConsultationModal');
    const closePrescriptionModal = document.getElementById('closePrescriptionModal');

    // View consultation functionality
    document.querySelectorAll('.view-consultation-btn').forEach(btn => {
        btn.onclick = function() {
            const consultationId = btn.dataset.id;
            fetch(`get_consultation.php?id=${consultationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error
                        });
                        return;
                    }
                    openConsultationModal('view', {
                        id: data.consultation_id,
                        patient: data.patient_name,
                        date: data.consultation_date,
                        notes: data.notes,
                        status: data.status
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load consultation details'
                    });
                });
        };
    });

    // Confirm consultation functionality
    document.querySelectorAll('.confirm-consultation-btn').forEach(btn => {
        btn.onclick = function() {
            const consultationId = btn.dataset.id;
            Swal.fire({
                title: 'Confirm Consultation',
                text: 'Are you sure you want to confirm this consultation?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, confirm it',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="consultation_id" value="${consultationId}">
                        <input type="hidden" name="status" value="Confirmed">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        };
    });

    // Complete consultation functionality
    document.querySelectorAll('.complete-consultation-btn').forEach(btn => {
        btn.onclick = function() {
            const consultationId = btn.dataset.id;
            Swal.fire({
                title: 'Complete Consultation',
                text: 'Are you sure you want to mark this consultation as completed?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, complete it',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="consultation_id" value="${consultationId}">
                        <input type="hidden" name="status" value="Completed">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        };
    });

    // Prescribe button functionality
    document.querySelectorAll('.prescribe-btn').forEach(btn => {
        btn.onclick = function() {
            const consultationId = btn.dataset.id;
            const patientName = btn.dataset.patient;
            const patientId = btn.dataset.patientid;
            document.getElementById('consultation_id').value = consultationId;
            document.getElementById('patient_name').value = patientName;
            document.getElementById('patient_id').value = patientId;
            prescriptionModal.style.display = 'flex';
        };
    });

    // Close modal functionality
    closeConsultationModal.onclick = function() {
        consultationModal.style.display = 'none';
    };

    closePrescriptionModal.onclick = function() {
        prescriptionModal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target === consultationModal) {
            consultationModal.style.display = 'none';
        }
        if (event.target === prescriptionModal) {
            prescriptionModal.style.display = 'none';
        }
    };

    // Delete consultation functionality
    document.querySelectorAll('.delete-consultation-btn').forEach(btn => {
        btn.onclick = function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Delete Consultation',
                text: 'Are you sure you want to delete this consultation?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_consultation">
                        <input type="hidden" name="consultation_id" value="${btn.dataset.id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        };
    });

    // Success/Error messages
    <?php if (isset($_GET['success']) && $_GET['success'] === '1'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: 'Operation completed successfully!',
            allowOutsideClick: true,
            allowEscapeKey: true
        }).then(() => {
            if (window.location.search.includes('success=1')) {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        });
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'prescribed'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Prescription Added!',
            text: 'The prescription has been saved successfully.',
            allowOutsideClick: true,
            allowEscapeKey: true
        }).then(() => {
            if (window.location.search.includes('success=prescribed')) {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        });
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'There was an error processing your request.'
        });
    <?php endif; ?>
});
</script>
</body>
</html> 