<?php
session_start();
require_once '../../../../config/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor', 'nurse'])) {
    header('Location: /medical/login.php');
    exit();
}

$user_id = $_SESSION['id'];
$fullname = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

// Handle add/edit/delete prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_prescription':
                $patient = mysqli_real_escape_string($conn, $_POST['patient']);
                $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
                $issue_date = mysqli_real_escape_string($conn, $_POST['issue_date']);
                $status = mysqli_real_escape_string($conn, $_POST['status']);
                $medication_id = intval($_POST['medication_id'] ?? 0);
                $dosage = mysqli_real_escape_string($conn, $_POST['dosage'] ?? '');
                $duration = mysqli_real_escape_string($conn, $_POST['duration'] ?? '');
                $sql = "INSERT INTO prescriptions (user_id, doctor_id, diagnosis, issue_date, status) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iisss", $patient, $user_id, $diagnosis, $issue_date, $status);
                if (mysqli_stmt_execute($stmt)) {
                    $prescription_id = $conn->insert_id;
                    // Save single medication
                    if ($medication_id > 0) {
                        $sqlMed = "INSERT INTO prescription_medications (prescription_id, medication_id, dosage, duration) VALUES (?, ?, ?, ?)";
                        $stmtMed = mysqli_prepare($conn, $sqlMed);
                        mysqli_stmt_bind_param($stmtMed, "iiss", $prescription_id, $medication_id, $dosage, $duration);
                        mysqli_stmt_execute($stmtMed);
                    }
                    header("Location: prescription.php?success=1"); exit();
                } else {
                    header("Location: prescription.php?error=1"); exit();
                }
            case 'update_prescription':
                $prescription_id = (int)$_POST['prescription_id'];
                $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
                $instructions = mysqli_real_escape_string($conn, $_POST['instructions']);
                $status = mysqli_real_escape_string($conn, $_POST['status']);
                $sql = "UPDATE prescriptions SET diagnosis=?, instructions=?, status=? WHERE prescription_id=? AND doctor_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssii", $diagnosis, $instructions, $status, $prescription_id, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: prescription.php?success=1"); exit();
                } else {
                    header("Location: prescription.php?error=1"); exit();
                }
            case 'delete_prescription':
                $prescription_id = (int)$_POST['prescription_id'];
                $sql = "DELETE FROM prescriptions WHERE prescription_id=? AND doctor_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $prescription_id, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: prescription.php?success=1"); exit();
                } else {
                    header("Location: prescription.php?error=1"); exit();
                }
        }
    }
}

// Handle add new medication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medication'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $dosage_instructions = mysqli_real_escape_string($conn, $_POST['dosage_instructions'] ?? '');
    $side_effects = mysqli_real_escape_string($conn, $_POST['side_effects'] ?? '');
    if ($name) {
        $sql = "INSERT INTO medications (name, description, dosage_instructions, side_effects) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $name, $description, $dosage_instructions, $side_effects);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: prescription.php?med_success=1"); exit();
        } else {
            header("Location: prescription.php?med_error=1"); exit();
        }
    } else {
        header("Location: prescription.php?med_error=1"); exit();
    }
}

// Fetch prescriptions for this doctor
$prescriptions = [];
$sql = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as patient_name, 
       m.name as medication_name, pm.dosage, pm.duration, pm.notes as intake_instructions
       FROM prescriptions p 
       JOIN users u ON p.user_id = u.user_id 
       JOIN prescription_medications pm ON p.prescription_id = pm.prescription_id
       JOIN medications m ON pm.medication_id = m.medication_id
       WHERE p.doctor_id = ? 
       ORDER BY p.issue_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $prescriptions[] = $row;
}

// Fetch all patients for dropdown
$patients = [];
$sql = "SELECT user_id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role_id IN (SELECT role_id FROM roles WHERE role_name IN ('student','teacher','staff')) ORDER BY name";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $patients[] = $row;
}

// Fetch all medications for dropdown
$medications = [];
$sql = "SELECT medication_id, name FROM medications ORDER BY name";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $medications[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Prescriptions - Medical Management</title>
    <link rel="stylesheet" href="../../../../src/styles/global.css">
    <link rel="stylesheet" href="../../../../src/styles/components.css">
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
            width: 100vw;
            height: 100vh;
            overflow: auto;
            background: rgba(0, 0, 0, 0.25);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--bg-white);
            margin: auto;
            padding: 2.5rem 2rem 2rem 2rem;
            border-radius: 18px;
            width: 100%;
            max-width: 420px;
            position: relative;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
            display: flex;
            flex-direction: column;
            align-items: stretch;
            animation: fadeInModal 0.3s cubic-bezier(.4, 2, .6, 1);
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
        <h1 class="page-title">Prescriptions</h1>
        <div class="table-container">
            <table class="data-table" id="prescriptionsTable">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Medication</th>
                        <th>Dosage</th>
                        <th>Duration</th>
                        <th>Intake Instructions</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prescriptions as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['patient_name']) ?></td>
                        <td><?= htmlspecialchars($p['medication_name']) ?></td>
                        <td><?= htmlspecialchars($p['dosage']) ?></td>
                        <td><?= htmlspecialchars($p['duration']) ?></td>
                        <td><?= htmlspecialchars($p['intake_instructions']) ?></td>
                        <td><?= htmlspecialchars($p['issue_date']) ?></td>
                        <td><?= htmlspecialchars($p['status']) ?></td>
                        <td>
                            <button type="button" class="btn btn-primary view-prescription-btn" data-id="<?= $p['prescription_id'] ?>">View</button>
                            <button type="button" class="btn btn-primary edit-prescription-btn" data-id="<?= $p['prescription_id'] ?>">Edit</button>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="action" value="delete_prescription">
                                <input type="hidden" name="prescription_id" value="<?= $p['prescription_id'] ?>">
                                <button type="submit" class="btn btn-danger delete-prescription-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- View/Edit Prescription Modal -->
<div class="modal" id="prescriptionModal">
    <div class="modal-content" id="prescriptionModalContent">
        <button class="close-modal" id="closePrescriptionModal" aria-label="Close">&times;</button>
        <h2 id="prescriptionModalTitle">Prescription Details</h2>
        <hr class="modal-divider" />
        <form method="POST" action="" id="prescriptionForm">
            <input type="hidden" name="action" id="prescription_action" value="update_prescription">
            <input type="hidden" name="prescription_id" id="prescription_id">
            
            <div class="form-group">
                <label>Patient</label>
                <input type="text" id="patient_name" readonly>
            </div>
            
            <div class="form-group">
                <label>Medication</label>
                <input type="text" id="medication_name" readonly>
            </div>
            
            <div class="form-group">
                <label for="dosage">Dosage</label>
                <input type="text" id="dosage" name="dosage" required>
            </div>
            
            <div class="form-group">
                <label for="duration">Duration</label>
                <input type="text" id="duration" name="duration" required>
            </div>
            
            <div class="form-group">
                <label for="intake">Intake Instructions</label>
                <textarea id="intake" name="intake" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Active">Active</option>
                    <option value="Completed">Completed</option>
                    <option value="Expired">Expired</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" id="prescriptionSubmitBtn">Update</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Updated modal logic for view/edit
function openPrescriptionModal(type, data = {}) {
    document.getElementById('prescriptionModalTitle').textContent = type === 'view' ? 'Prescription Details' : 'Edit Prescription';
    document.getElementById('prescription_action').value = type === 'edit' ? 'update_prescription' : 'view_prescription';
    document.getElementById('prescription_id').value = data.id || '';
    document.getElementById('patient_name').value = data.patient || '';
    document.getElementById('medication_name').value = data.medication || '';
    document.getElementById('dosage').value = data.dosage || '';
    document.getElementById('duration').value = data.duration || '';
    document.getElementById('intake').value = data.intake || '';
    document.getElementById('status').value = data.status || 'Active';
    
    // Disable fields in view mode
    const isView = type === 'view';
    document.getElementById('dosage').readOnly = isView;
    document.getElementById('duration').readOnly = isView;
    document.getElementById('intake').readOnly = isView;
    document.getElementById('status').disabled = isView;
    document.getElementById('prescriptionSubmitBtn').style.display = isView ? 'none' : 'block';
    
    prescriptionModal.style.display = 'flex';
}

document.querySelectorAll('.view-prescription-btn').forEach(btn => {
    btn.onclick = function() {
        const row = btn.closest('tr').children;
        openPrescriptionModal('view', {
            id: btn.dataset.id,
            patient: row[0].textContent,
            medication: row[1].textContent,
            dosage: row[2].textContent,
            duration: row[3].textContent,
            intake: row[4].textContent,
            status: row[6].textContent
        });
    };
});

document.querySelectorAll('.edit-prescription-btn').forEach(btn => {
    btn.onclick = function() {
        const row = btn.closest('tr').children;
        openPrescriptionModal('edit', {
            id: btn.dataset.id,
            patient: row[0].textContent,
            medication: row[1].textContent,
            dosage: row[2].textContent,
            duration: row[3].textContent,
            intake: row[4].textContent,
            status: row[6].textContent
        });
    };
});

// SweetAlert2 for add/update/delete
<?php if (isset($_GET['success'])): ?>
    Swal.fire({icon:'success',title:'Success',text:'Prescription action completed successfully!'});
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    Swal.fire({icon:'error',title:'Error',text:'There was an error processing your request.'});
<?php endif; ?>
document.querySelectorAll('.delete-prescription-btn').forEach(btn => {
    btn.onclick = function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will delete the prescription.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.closest('form').submit();
            }
        });
        return false;
    };
});
// SweetAlert2 for add/update/delete and medication add
<?php if (isset($_GET['med_success'])): ?>
    Swal.fire({icon:'success',title:'Success',text:'Medication added successfully!'});
<?php endif; ?>
<?php if (isset($_GET['med_error'])): ?>
    Swal.fire({icon:'error',title:'Error',text:'There was an error adding the medication.'});
<?php endif; ?>
</script>
</body>
</html> 