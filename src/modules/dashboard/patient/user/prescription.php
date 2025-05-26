<?php
session_start();
require_once '../../../../../config/config.php';

// Check if user is logged in and has a valid patient role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'teacher', 'staff'])) {
    header("Location: /medical/src/auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$prescriptions = [];
$prescriptionMedications = [];

// 2) Fetch recent prescriptions
try {
    $prescriptionsQuery = "SELECT p.*, u.first_name, u.last_name, d.specialization, pm.patient_status, pm.patient_updated_at
                          FROM prescriptions p
                          JOIN users u ON p.doctor_id = u.user_id
                          LEFT JOIN doctors d ON u.user_id = d.user_id
                          JOIN prescription_medications pm ON p.prescription_id = pm.prescription_id
                          WHERE p.user_id = ? 
                          ORDER BY p.issue_date DESC";
    $prescriptionsStmt = $conn->prepare($prescriptionsQuery);
    $prescriptionsStmt->bind_param("i", $user_id);
    $prescriptionsStmt->execute();
    $prescriptions = $prescriptionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch all medications for these prescriptions
    $ids = array_column($prescriptions, 'prescription_id');
    $prescriptionMedications = [];
    if (!empty($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $sql = "SELECT prescription_id, medication_name, dosage, duration FROM prescription_medications WHERE prescription_id IN ($in)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $prescriptionMedications[$row['prescription_id']] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching prescriptions: " . $e->getMessage());
}

// Include header
include_once '../../../../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions - Medical Management System</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/user/styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="prescription-page">
    <div class="section-header">
        <h2 class="section-title">Prescriptions</h2>
       
    </div>
    <div class="centered-table-container">
        <?php if (!empty($prescriptions)): ?>
            <table class="prescription-table centered-table">
                <thead>
                    <tr>
                        <th>Date Issued</th>
                        <th>Prescription Details</th>
                        <th>Doctor</th>
                        <th>Adherence Status</th>
                        <th>Last Patient Update</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prescriptions as $prescription): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($prescription['issue_date'])) ?></td>
                            <td>
                                <div class="prescription-details">
                                    <?php $med = $prescriptionMedications[$prescription['prescription_id']] ?? null; ?>
                                    <div class="drug-name">
                                        <?= htmlspecialchars($med['medication_name'] ?? 'N/A') ?>
                                    </div>
                                    <div class="dosage">
                                        <?= htmlspecialchars($med['dosage'] ?? 'N/A') ?>
                                    </div>
                                    <div class="duration">
                                        <?= htmlspecialchars($med['duration'] ?? 'N/A') ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="doctor-info">
                                    <div class="doctor-name">Dr. <?= htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']) ?></div>
                                    <div class="specialization"><?= htmlspecialchars($prescription['specialization'] ?? 'General Practitioner') ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-indicator <?= strtolower($prescription['patient_status'] ?? '') ?>"><?= htmlspecialchars($prescription['patient_status'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <?= htmlspecialchars($prescription['patient_updated_at'] ?? 'N/A') ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-primary view-prescription-btn" data-id="<?= $prescription['prescription_id'] ?>"><i class="fas fa-eye"></i></button>
                                    <?php if (!in_array($prescription['patient_status'], ['Completed', 'Stopped Early'])): ?>
                                        <button class="btn btn-primary edit-prescription-btn" data-id="<?= $prescription['prescription_id'] ?>"><i class="fas fa-edit"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-prescription-bottle"></i>
                <div class="empty-message">
                    <div class="empty-title">No prescriptions</div>
                    <div class="empty-description">Your prescriptions will appear here</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Prescription Modal -->
<div class="modal" id="viewPrescriptionModal">
  <div class="modal-content">
    <button class="close-modal" id="closeViewPrescriptionModal">&times;</button>
    <h2>Prescription Details</h2>
    <div id="viewPrescriptionDetails"></div>
  </div>
</div>
<!-- Edit Prescription Modal -->
<div class="modal" id="editPrescriptionModal">
  <div class="modal-content">
    <button class="close-modal" id="closeEditPrescriptionModal">&times;</button>
    <h2>Edit Prescription</h2>
    <form id="editPrescriptionForm">
      <input type="hidden" name="prescription_id" id="edit_prescription_id">
      <hr class="modal-divider" />
      <h3>Your Adherence</h3>
      <input type="hidden" name="prescription_medication_id" id="edit_prescription_medication_id">
      <div class="form-group">
        <label for="edit_patient_status">Status</label>
        <select name="patient_status" id="edit_patient_status" required>
          <option value="Pending">Pending</option>
          <option value="Completed">Completed</option>
          <option value="Stopped Early">Stopped Early</option>
        </select>
      </div>
      <div class="form-group">
        <label for="edit_patient_notes">Notes (Optional)</label>
        <textarea name="patient_notes" id="edit_patient_notes"></textarea>
      </div>
      <!-- End Adherence Form -->

      <button type="submit" class="btn btn-primary">Update</button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add animation classes after DOM is loaded
    setTimeout(() => {
        document.querySelectorAll('table')[0].classList.add('animate-in');
    }, 300);

    // Fix delete button to submit via JS
    document.querySelectorAll('.delete-prescription-btn').forEach(btn => {
        btn.onclick = function() {
            const prescriptionId = btn.dataset.id;
            Swal.fire({
                title: 'Delete Prescription',
                text: 'Are you sure you want to delete this prescription?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_prescription">
                        <input type="hidden" name="prescription_id" value="${prescriptionId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        };
    });

    // View Prescription
    const viewModal = document.getElementById('viewPrescriptionModal');
    const closeViewModal = document.getElementById('closeViewPrescriptionModal');
    document.querySelectorAll('.view-prescription-btn').forEach(btn => {
        btn.onclick = function() {
            fetch('actions/get_prescription.php?id=' + btn.dataset.id)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }
                    // Display prescription details in view modal
                    let html = '<strong>Date Issued:</strong> ' + data.issue_date + '<br>';
                    html += '<strong>Doctor:</strong> Dr. ' + data.first_name + ' ' + data.last_name + ' (' + (data.specialization || 'General') + ')<br>';
                    html += '<strong>Status:</strong> ' + data.status + '<br>';
                    
                    if (data.medication) {
                        html += '<br><strong>Medication:</strong> ' + data.medication.medication_name + '<br>';
                        html += '<strong>Dosage:</strong> ' + data.medication.dosage + '<br>';
                        html += '<strong>Duration:</strong> ' + data.medication.duration + '<br>';
                        html += '<strong>Intake:</strong> ' + (data.medication.notes || 'None') + '<br>';
                        html += '<br><strong>Patient Adherence Status:</strong> ' + (data.medication.patient_status || 'Pending') + '<br>';
                        html += '<strong>Patient Notes:</strong> ' + (data.medication.patient_notes || 'None') + '<br>';
                        html += '<strong>Last Patient Update:</strong> ' + (data.medication.patient_updated_at || 'N/A') + '<br>';

                    }

                    document.getElementById('viewPrescriptionDetails').innerHTML = html;
                    viewModal.style.display = 'flex';
                });
        };
    });
    closeViewModal.onclick = function() { viewModal.style.display = 'none'; };
    window.onclick = function(e) {
        if (e.target === viewModal) viewModal.style.display = 'none';
        if (e.target === editModal) editModal.style.display = 'none';
    };
    
    // Edit Prescription
    const editModal = document.getElementById('editPrescriptionModal');
    const closeEditModal = document.getElementById('closeEditPrescriptionModal');
    document.querySelectorAll('.edit-prescription-btn').forEach(btn => {
        btn.onclick = function() {
            fetch('actions/get_prescription.php?id=' + btn.dataset.id)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }
                    // Populate the edit form with prescription details
                    document.getElementById('edit_prescription_id').value = data.prescription_id;
                    // Populate adherence form fields
                     if (data.medication) {
                        document.getElementById('edit_prescription_medication_id').value = data.medication.id; // This should be the ID from prescription_medications
                        document.getElementById('edit_patient_status').value = data.medication.patient_status || 'Pending';
                        document.getElementById('edit_patient_notes').value = data.medication.patient_notes || '';
                     } else {
                         // Hide adherence form if no medication data
                         document.getElementById('updateAdherenceForm').style.display = 'none';
                     }
                    
                    editModal.style.display = 'flex';
                });
        };
    });
    closeEditModal.onclick = function() { editModal.style.display = 'none'; };

    // Handle Edit Form Submit (This form now handles both doctor status and patient adherence)
    document.getElementById('editPrescriptionForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        // Determine which action to perform based on who is submitting (though in this file, it's always the patient)
        // We'll assume this form is primarily for patient adherence updates now.
        
        fetch('actions/update_patient_medication_status.php', { // Pointing to the correct script
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Updated', data.message || 'Status updated!', 'success').then(() => {
                    location.reload(); // Reload the page on success
                });
            } else {
                Swal.fire('Error', data.error || 'Failed to update status.', 'error');
            }
        })
        .catch(error => {
            console.error('Error submitting update:', error);
            Swal.fire('Error', 'An error occurred while submitting your update.', 'error');
        });
    };

});
</script>

</body>
</html>
