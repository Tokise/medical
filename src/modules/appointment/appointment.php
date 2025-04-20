<?php
session_start();
require_once '../../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../../src/auth/login.php");
    exit;
}

// Get user info from session
$user_id = $_SESSION['id'];
$role = strtolower($_SESSION['role']); // role should be available in session from login
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$fullname = $first_name . ' ' . $last_name;

// Get today's appointments for display
$today_appointments = [];
$sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.reason, 
               CONCAT(u.first_name, ' ', u.last_name) as patient_name,
               CASE 
                  WHEN a.status = 'Scheduled' THEN 'High'
                  WHEN a.status = 'Completed' THEN 'Low'
                  ELSE 'Medium'
               END as priority
        FROM appointments a 
        JOIN users u ON a.patient_id = u.user_id 
        WHERE a.appointment_date = CURDATE()";

// Append role-specific conditions
if ($role == 'doctor' || $role == 'nurse') {
    $sql .= " AND a.doctor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else if ($role == 'student' || $role == 'teacher' || $role == 'staff') {
    $sql .= " AND a.patient_id = ?";
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
        'name' => $row['patient_name'],
        'date' => date('F j, Y', strtotime($row['appointment_date'])),
        'time' => date('h:i A', strtotime($row['appointment_time'])),
        'reason' => $row['reason'],
        'priority' => $row['priority']
    ];
}

// Get all appointment requests
$appointment_requests = [];
$sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.reason, 
               CONCAT(u.first_name, ' ', u.last_name) as patient_name,
               u.user_id as patient_id,
               CASE 
                  WHEN a.status = 'Scheduled' THEN 'High'
                  WHEN a.status = 'Completed' THEN 'Low'
                  ELSE 'Medium'
               END as priority
        FROM appointments a 
        JOIN users u ON a.patient_id = u.user_id";

// Append role-specific conditions
if ($role == 'doctor' || $role == 'nurse') {
    $sql .= " WHERE a.doctor_id = ? ORDER BY a.appointment_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else if ($role == 'student' || $role == 'teacher' || $role == 'staff') {
    $sql .= " WHERE a.patient_id = ? ORDER BY a.appointment_date DESC";
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
        'name' => $row['patient_name'],
        'patient_id' => $row['patient_id'],
        'date' => date('F j, Y', strtotime($row['appointment_date'])),
        'time' => date('h:i A', strtotime($row['appointment_time'])),
        'reason' => $row['reason'],
        'priority' => $row['priority']
    ];
}

// Get patient data if we have appointments
$default_patient = null;
if (!empty($appointment_requests)) {
    $default_patient_id = $appointment_requests[0]['patient_id'];
    
    // Get patient details
    $stmt = $conn->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.school_id,
               COALESCE(s.date_of_birth, t.date_of_birth, st.date_of_birth) as date_of_birth,
               COALESCE(s.gender, t.gender, st.gender) as gender
        FROM users u
        LEFT JOIN students s ON u.user_id = s.user_id
        LEFT JOIN teachers t ON u.user_id = t.user_id
        LEFT JOIN staff st ON u.user_id = st.user_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $default_patient_id);
    $stmt->execute();
    $patient_result = $stmt->get_result();
    
    if ($patient_result->num_rows > 0) {
        $default_patient = $patient_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Appointment - Medical Management</title>
    <link rel="stylesheet" href="../../../src/styles/global.css">
    <link rel="stylesheet" href="../../../src/styles/components.css">
    <link rel="stylesheet" href="styles/appointment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include_once('../../../includes/header.php'); ?>

    <section class="main-content">
        <div class="container">
            <h1 class="page-title">Appointments</h1>
            
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
                                            <th>Patient Name</th>
                                            <th>Appointment Date</th>
                                            <th>Reason</th>
                                            <th>Priority</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointment_requests as $appointment): ?>
                                        <tr data-patient-id="<?php echo $appointment['patient_id']; ?>">
                                            <td>
                                                <span class="badge-status badge-<?php echo strtolower($appointment['status']) == 'completed' ? 'completed' : 'inProgress'; ?>">
                                                    <?php echo $appointment['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $appointment['name']; ?></td>
                                            <td><?php echo $appointment['date']; ?></td>
                                            <td><?php echo $appointment['reason']; ?></td>
                                            <td><?php echo $appointment['priority']; ?></td>
                                            <td>
                                                <button class="btnAction" data-id="<?php echo $appointment['id']; ?>">View</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="patient-formdata" id="patientDetails">
                    <?php if ($default_patient): ?>
                    <div class="patient-header">
                        <p><?php echo $default_patient['first_name'] . ' ' . $default_patient['last_name']; ?></p>
                        <label class="inProgress">In Progress</label>
                    </div>
                    <div class="patient-info">
                        <div class="patient-data">
                            <label>Date of Birth</label>
                            <p><?php echo $default_patient['date_of_birth'] ? date('F j, Y', strtotime($default_patient['date_of_birth'])) : 'Not available'; ?></p>
                        </div>
                        <div class="patient-data">
                            <label>Gender</label>
                            <p><?php echo $default_patient['gender'] ?? 'Not available'; ?></p>
                        </div>
                        <div class="patient-data">
                            <label>ID No.</label>
                            <p><?php echo $default_patient['school_id']; ?></p>
                        </div>
                        <div class="patient-data">
                            <label>Indication</label>
                            <p><?php echo $appointment_requests[0]['reason'] ?? 'None'; ?></p>
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
                        <h3>No Patient Selected</h3>
                        <p>Select a patient from the appointment list to view details</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

<script>
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
                
                // Highlight first row after switching tabs
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
                
                // Highlight clicked row
                document.querySelectorAll('#appointmentTable tbody tr').forEach(r => {
                    r.classList.remove('selected-row');
                });
                row.classList.add('selected-row');
                
                // Load patient details (in a real app, this would fetch from server)
                loadPatientDetails(patientId);
            }
        });
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
            tr.dataset.patientId = appointment.patient_id;
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
</script>

</body>
</html>
</body>
</html>

</html>