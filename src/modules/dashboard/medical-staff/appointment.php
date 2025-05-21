<?php
session_start();
require_once '../../../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../../src/auth/login.php");
    exit;
}

// Get user info from session
$user_id = $_SESSION['user_id'];
$role = strtolower($_SESSION['role']); // role should be available in session from login
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$fullname = $first_name . ' ' . $last_name;

// Debug: Output session array as HTML comment
echo '<!-- SESSION: '; print_r($_SESSION); echo ' -->';

// Get today's appointments for display
$today_appointments = [];
$sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.reason, 
               CONCAT(u.first_name, ' ', u.last_name) as staff_name,
               u.user_id as staff_id,
               CASE 
                  WHEN a.status = 'Scheduled' THEN 'High'
                  WHEN a.status = 'Completed' THEN 'Low'
                  ELSE 'Medium'
               END as priority
        FROM staff_appointments a 
        JOIN users u ON a.staff_id = u.user_id 
        WHERE a.appointment_date = CURDATE()";

// Append role-specific conditions
if ($role == 'doctor' || $role == 'nurse') {
    $sql .= " AND a.requested_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else { // Admin can see all
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $today_appointments[] = [
        'id' => $row['appointment_id'],
        'status' => $row['status'],
        'name' => $row['staff_name'],
        'staff_id' => $row['staff_id'],
        'date' => date('F j, Y', strtotime($row['appointment_date'])),
        'time' => date('h:i A', strtotime($row['appointment_time'])),
        'reason' => $row['reason'],
        'priority' => $row['priority']
    ];
}

// Get all appointment requests
$appointment_requests = [];
$sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.reason, 
               CONCAT(u.first_name, ' ', u.last_name) as staff_name,
               u.user_id as staff_id,
               CASE 
                  WHEN a.status = 'Scheduled' THEN 'High'
                  WHEN a.status = 'Completed' THEN 'Low'
                  ELSE 'Medium'
               END as priority
        FROM staff_appointments a 
        JOIN users u ON a.staff_id = u.user_id";

// Append role-specific conditions
if ($role == 'doctor' || $role == 'nurse') {
    $sql .= " WHERE a.requested_by = ? ORDER BY a.appointment_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else { // Admin can see all
    $sql .= " ORDER BY a.appointment_date DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $appointment_requests[] = [
        'id' => $row['appointment_id'],
        'status' => $row['status'],
        'name' => $row['staff_name'],
        'staff_id' => $row['staff_id'],
        'date' => date('F j, Y', strtotime($row['appointment_date'])),
        'time' => date('h:i A', strtotime($row['appointment_time'])),
        'reason' => $row['reason'],
        'priority' => $row['priority']
    ];
}

// Get staff data if we have appointments
$default_staff = null;
if (!empty($appointment_requests)) {
    $default_staff_id = $appointment_requests[0]['staff_id'];
    
    // Get staff details
    $stmt = $conn->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email,
               COALESCE(d.specialization, n.specialization) as specialization,
               COALESCE(d.license_number, n.license_number) as license_number
        FROM users u
        LEFT JOIN doctors d ON u.user_id = d.user_id
        LEFT JOIN nurses n ON u.user_id = n.user_id
        WHERE u.user_id = ? AND u.role IN ('doctor', 'nurse')
    ");
    $stmt->bind_param("i", $default_staff_id);
    $stmt->execute();
    $staff_result = $stmt->get_result();
    
    if ($staff_result->num_rows > 0) {
        $default_staff = $staff_result->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_appointment') {
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id'] ?? '');
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date'] ?? '');
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time'] ?? '');
    $duration = mysqli_real_escape_string($conn, $_POST['duration'] ?? 30);
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Scheduled');
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $requested_by = $user_id;
    
    if ($staff_id && $appointment_date && $appointment_time) {
        $sql = "INSERT INTO staff_appointments (staff_id, requested_by, appointment_date, appointment_time, duration, status, reason, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iississs", $staff_id, $requested_by, $appointment_date, $appointment_time, $duration, $status, $reason, $notes);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: appointment.php?success=1"); exit();
        } else {
            header("Location: appointment.php?error=1"); exit();
        }
    } else {
        header("Location: appointment.php?error=1"); exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Appointment - Medical Management</title>
    <link rel="stylesheet" href="../../../../src/styles/global.css">
    <link rel="stylesheet" href="../../../../src/styles/components.css">
    <link rel="stylesheet" href="styles/doctor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>
    <?php include_once('../../../../includes/header.php'); ?>

    <section class="main-content">
        <div class="container">
            <div class="page-header-flex" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <h1 class="page-title" style="margin: 0;">Appointments</h1>
                <button class="btn btn-primary" id="openAddAppointment"><i class="fas fa-plus"></i> Add Appointment</button>
            </div>
            
            <div class="main-container">
                <div class="list-patient">
                    <div class="header-actions">
                        <div class="filter">
                            <div class="patient-search">
                                <input type="text" id="searchInput" placeholder="Search"/>
                                <i class="fas fa-search"></i>
                            </div>
               
                            <select id="statusFilter" class="form-control">
                                <option value="all">All Status</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="No-show">No-show</option>
                            </select>
                        </div>
                        <span class="update">Last updated: <?php echo date('j F Y h:i A'); ?></span>
                    </div>
                    <div class="tables">
                        <div class="header-tabs">
                            <div class="head-tab activeTab" data-tab="appointments">
                                <p>Appointment Requests</p>
                                <div class="pending-patient"><?php echo count($appointment_requests); ?></div>
                            </div>
                            <div class="head-tab" data-tab="today">
                                <p>Today's Schedule</p>
                                <div class="pending-task"><?php echo count($today_appointments); ?></div>
                            </div>
                        </div>
                        <div class="tab-content">
                            <div class="table-container">
                                <table id="appointmentTable">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Staff Name</th>
                                            <th>Appointment Date</th>
                                            <th>Reason</th>
                                            <th>Priority</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Table rows will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="patient-formdata" id="patientDetails">
                    <?php if ($default_staff): ?>
                    <div class="patient-header">
                        <p><?php echo $default_staff['first_name'] . ' ' . $default_staff['last_name']; ?></p>
                        <label class="inProgress">In Progress</label>
                    </div>
                    <div class="patient-info">
                        <div class="patient-data">
                            <label>Email</label>
                            <p><?php echo $default_staff['email']; ?></p>
                        </div>
                        <div class="patient-data">
                            <label>Role</label>
                            <p><?php echo ucfirst($default_staff['role']); ?></p>
                        </div>
                        <div class="patient-data">
                            <label>License No.</label>
                            <p><?php echo $default_staff['license_number']; ?></p>
                        </div>
                        <div class="patient-data">
                            <label>Specialization</label>
                            <p><?php echo $default_staff['specialization'] ?? 'Not specified'; ?></p>
                        </div>
                    </div>
                    <div class="patient-health">
                        <div class="risk">
                            <div class="patient-data">
                                <label>Risk</label>
                                <p>Moderate</p>
                            </div>
                            <div class="patient-data">
                                <label>N&V</label>
                                <p>NO</p>
                            </div>
                            <div class="patient-data">
                                <label>Injury:</label>
                                <p>No</p>
                            </div>
                            <div class="patient-data">
                                <label>Progress</label>
                                <p>Worsening</p>
                            </div>
                        </div>
                        <div class="symptoms">
                            <div class="risk">
                                <div class="patient-data">
                                    <label>Symptoms</label>
                                    <p>Persistent Cough</p>
                                </div>
                                <div class="patient-data">
                                    <label>Chemicals</label>
                                    <p>NO</p>
                                </div>
                                <div class="patient-data">
                                    <label>Site:</label>
                                    <p>Throat and Chest</p>
                                </div>
                                <div class="patient-data">
                                    <label>Severity</label>
                                    <p><label>6</label>/10</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn-primary"><i class="fas fa-stethoscope"></i> Start Consultation</button>
                        <button class="btn-outline"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                    <?php else: ?>
                    <div class="no-patient-selected">
                        <i class="fas fa-user-md fa-3x"></i>
                        <h3>No Staff Selected</h3>
                        <p>Select a staff from the appointment list to view details</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Appointment Modal Redesign -->
    <div class="modal" id="appointmentModal" style="display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.25);">
        <div class="modal-content" id="appointmentModalContent" style="max-width: 420px; padding: 2.5rem 2rem 2rem 2rem; border-radius: 18px; box-shadow: 0 8px 32px rgba(0,0,0,0.18); border: 1px solid #e0e0e0; background: #fff;">
            <button class="close-modal" id="closeAppointmentModal" aria-label="Close">&times;</button>
            <h2 id="appointmentModalTitle" style="margin-bottom: 1rem; text-align: center; font-size: 1.5rem; font-weight: 600;">Add Appointment</h2>
            <hr class="modal-divider" />
            <form method="POST" action="" id="appointmentForm">
                <input type="hidden" name="action" id="appointment_action" value="add_appointment">
                <input type="hidden" name="appointment_id" id="appointment_id">
                <div class="form-group">
                    <label for="staff">Staff Member</label>
                    <select id="staff" name="staff_id" required>
                        <option value="">Select Staff Member</option>
                        <?php
                        $sql = "SELECT u.user_id, u.first_name, u.last_name, u.role 
                                FROM users u 
                                WHERE u.role IN ('doctor', 'nurse') 
                                AND u.user_id != ? 
                                ORDER BY u.role, u.first_name";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['user_id'] . "'>" . 
                                 ucfirst($row['role']) . " - " . 
                                 $row['first_name'] . " " . $row['last_name'] . 
                                 "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="modal_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="time">Time</label>
                    <input type="time" id="modal_time" name="appointment_time" required>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reason">Reason</label>
                    <input type="text" id="modal_reason" name="reason" maxlength="255">
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="modal_notes" name="notes" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" id="appointmentSubmitBtn" style="width: 100%; margin-top: 1rem;">Save Appointment</button>
            </form>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Modal logic for add/edit/view
    const appointmentModal = document.getElementById('appointmentModal');
    const closeAppointmentModal = document.getElementById('closeAppointmentModal');
    const openAddAppointment = document.getElementById('openAddAppointment');

    function openAppointmentModal(type, data = {}) {
        document.getElementById('appointmentModalTitle').textContent = type === 'view' ? 'Appointment Details' : (type === 'edit' ? 'Edit Appointment' : 'Add Appointment');
        document.getElementById('appointment_action').value = type === 'edit' ? 'update_appointment' : 'add_appointment';
        document.getElementById('appointment_id').value = data.id || '';
        document.getElementById('staff').value = data.staff_id || '';
        document.getElementById('modal_date').value = data.date || '';
        document.getElementById('modal_time').value = data.time || '';
        document.getElementById('status').value = data.status || 'Scheduled';
        document.getElementById('staff').disabled = type === 'view';
        document.getElementById('modal_date').readOnly = type === 'view';
        document.getElementById('modal_time').readOnly = type === 'view';
        document.getElementById('status').disabled = type === 'view';
        document.getElementById('appointmentSubmitBtn').style.display = type === 'view' ? 'none' : 'block';
        appointmentModal.style.display = 'flex';
    }

    document.addEventListener("DOMContentLoaded", (event) => {
        showAppointmentRequests();
        highlightFirstRow();
        // Tab switching
        const tabs = document.querySelectorAll('.head-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('activeTab'));
                tab.classList.add('activeTab');
                if (tab.dataset.tab === 'appointments') {
                    showAppointmentRequests();
                } else if (tab.dataset.tab === 'today') {
                    showTodaySchedule();
                }
                setTimeout(highlightFirstRow, 100);
            });
        });
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', filterTable);
        // View button click
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('btnAction')) {
                const row = e.target.closest('tr');
                const patientId = row.dataset.patientId;
                document.querySelectorAll('#appointmentTable tbody tr').forEach(r => {
                    r.classList.remove('selected-row');
                });
                row.classList.add('selected-row');
                loadPatientDetails(patientId);
            }
        });
        if (closeAppointmentModal) {
            closeAppointmentModal.onclick = function() { appointmentModal.style.display = 'none'; };
        }
        window.onclick = function(event) {
            if (event.target === appointmentModal) appointmentModal.style.display = 'none';
        };
        if (openAddAppointment) {
            openAddAppointment.onclick = function() {
                openAppointmentModal('add');
                appointmentModal.style.display = 'flex';
            };
        }
    });

    function highlightFirstRow() {
        const firstRow = document.querySelector('#appointmentTable tbody tr');
        if (firstRow) {
            firstRow.classList.add('selected-row');
        }
    }
    
    function loadPatientDetails(patientId) {
        // In a real implementation, this would make an AJAX call to get patient details
        // For now, we'll just update what we have with some visual feedback
        const patientDetails = document.getElementById('patientDetails');
        patientDetails.classList.add('loading');
        
        setTimeout(() => {
            patientDetails.classList.remove('loading');
        }, 500);
    }

    function filterTable() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('#appointmentTable tbody tr');
        
        rows.forEach(row => {
            const patientName = row.cells[1].textContent.toLowerCase();
            const status = row.cells[0].textContent.trim();
            
            const matchesSearch = patientName.includes(searchInput);
            const matchesStatus = statusFilter === 'all' || status === statusFilter;
            
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    function showAppointmentRequests() {
        const appointmentData = <?php echo json_encode($appointment_requests); ?>;
        populateTable(appointmentData);
    }
    
    function showTodaySchedule() {
        const todayData = <?php echo json_encode($today_appointments); ?>;
        populateTable(todayData);
    }
    
    function populateTable(data) {
        const tBody = document.querySelector('#appointmentTable tbody');
        tBody.innerHTML = '';
        
        if (data.length === 0) {
            tBody.innerHTML = '<tr><td colspan="6" class="text-center">No appointments found</td></tr>';
            return;
        }
        
        data.forEach(appointment => {
            const statusClass = appointment.status.toLowerCase() === 'completed' ? 'completed' : 'inProgress';
            
            const tr = document.createElement('tr');
            tr.dataset.patientId = appointment.staff_id;
            tr.innerHTML = `
                <td>
                    <span class="badge-status badge-${statusClass}">
                        ${appointment.status}
                    </span>
                </td>
                <td>${appointment.name}</td>
                <td>${appointment.date}</td>
                <td>${appointment.reason}</td>
                <td>${appointment.priority}</td>
                <td>
                    <button class="btnAction" data-id="${appointment.id}">View</button>
                </td>
            `;
            tBody.appendChild(tr);
        });
    }

    // SweetAlert2 for add/update/delete
    <?php if (isset($_GET['success'])): ?>
        Swal.fire({icon:'success',title:'Success',text:'Appointment added successfully!'});
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        Swal.fire({icon:'error',title:'Error',text:'There was an error adding the appointment.'});
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'pastdate'): ?>
        Swal.fire({icon:'error',title:'Invalid Date',text:'You can only schedule for today or future dates.'});
    <?php endif; ?>
    document.querySelectorAll('.delete-appointment-btn').forEach(btn => {
        btn.onclick = function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will delete the appointment.',
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
</script>


</body>
</html>
