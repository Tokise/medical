<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor', 'nurse'])) {
    header('Location: /medical/login.php');
    exit();
}

// Include database connection
require_once "../../../../config/config.php";

// Functions to get dashboard data
function get_today_appointments($user_id, $role) {
    global $conn;
    $appointments = [];
    if ($role === 'doctor') {
        $sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, CONCAT(u.first_name, ' ', u.last_name) as patient_name
                FROM appointments a 
                JOIN users u ON a.patient_id = u.user_id 
                WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
                ORDER BY a.appointment_time ASC";
    } else if ($role === 'nurse') {
        // If nurse_id column exists, use it. If not, fallback to doctor_id.
        $sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, CONCAT(u.first_name, ' ', u.last_name) as patient_name
                FROM appointments a 
                JOIN users u ON a.patient_id = u.user_id 
                WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
                ORDER BY a.appointment_time ASC";
    }
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $appointments[] = [
                'id' => $row['appointment_id'],
                'date' => $row['appointment_date'],
                'time' => $row['appointment_time'],
                'patient_name' => $row['patient_name'],
                'status' => $row['status']
            ];
        }
        mysqli_stmt_close($stmt);
    }
    return $appointments;
}

function get_pending_consultations($doctor_id) {
    global $conn;
    $count = 0;
    
    $sql = "SELECT COUNT(*) as total FROM consultations WHERE doctor_id = ? AND status = 'Scheduled'";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $count = $row["total"];
        }
        mysqli_stmt_close($stmt);
    }
    
    return $count;
}

function get_pending_prescriptions($doctor_id) {
    global $conn;
    $count = 0;
    
    $sql = "SELECT COUNT(*) as total FROM prescriptions WHERE doctor_id = ? AND status = 'Pending'";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $count = $row["total"];
        }
        mysqli_stmt_close($stmt);
    }
    
    return $count;
}

function get_total_patients($doctor_id) {
    global $conn;
    $count = 0;
    
    $sql = "SELECT COUNT(DISTINCT patient_id) as total FROM medical_records WHERE doctor_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $count = $row["total"];
        }
        mysqli_stmt_close($stmt);
    }
    
    return $count;
}

function get_recent_appointments($doctor_id, $limit = 5) {
    global $conn;
    $appointments = [];
    
    $sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, 
                   CONCAT(u.first_name, ' ', u.last_name) as patient_name
            FROM appointments a 
            JOIN users u ON a.patient_id = u.user_id 
            WHERE a.doctor_id = ? 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC 
            LIMIT ?";
            
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $appointments[] = [
                'id' => $row['appointment_id'],
                'date' => $row['appointment_date'],
                'time' => $row['appointment_time'],
                'patient_name' => $row['patient_name'],
                'status' => $row['status']
            ];
        }
        mysqli_stmt_close($stmt);
    }
    
    return $appointments;
}

function get_recent_records($doctor_id, $limit = 5) {
    global $conn;
    $records = [];
    
    $sql = "SELECT mr.id, mr.record_date, CONCAT(u.first_name, ' ', u.last_name) as patient_name, mr.diagnosis 
            FROM medical_records mr 
            JOIN users u ON mr.patient_id = u.user_id 
            WHERE mr.doctor_id = ? 
            ORDER BY mr.record_date DESC 
            LIMIT ?";
            
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $records[] = [
                'id' => $row['id'],
                'date' => $row['record_date'],
                'patient_name' => $row['patient_name'],
                'diagnosis' => $row['diagnosis']
            ];
        }
        mysqli_stmt_close($stmt);
    }
    
    return $records;
}

function get_recent_prescriptions($doctor_id, $limit = 5) {
    global $conn;
    $prescriptions = [];
    
    $sql = "SELECT p.prescription_id, p.issue_date as prescription_date, p.status,
                   CONCAT(u.first_name, ' ', u.last_name) as patient_name,
                   GROUP_CONCAT(m.name) as medications
            FROM prescriptions p 
            JOIN users u ON p.user_id = u.user_id 
            LEFT JOIN prescription_medications pm ON p.prescription_id = pm.prescription_id
            LEFT JOIN medications m ON pm.medication_id = m.medication_id
            WHERE p.doctor_id = ? 
            GROUP BY p.prescription_id
            ORDER BY p.issue_date DESC 
            LIMIT ?";
            
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $prescriptions[] = [
                'id' => $row['prescription_id'],
                'date' => $row['prescription_date'],
                'patient_name' => $row['patient_name'],
                'medications' => $row['medications'],
                'status' => $row['status']
            ];
        }
        mysqli_stmt_close($stmt);
    }
    
    return $prescriptions;
}

function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function get_today_staff_schedule($user_id) {
    global $conn;
    $schedules = [];
    $sql = "SELECT start_time, end_time, type FROM staff_schedules WHERE staff_id = ? AND date = CURDATE() ORDER BY start_time ASC";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $schedules[] = [
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'type' => $row['type']
            ];
        }
        mysqli_stmt_close($stmt);
    }
    return $schedules;
}

function get_upcoming_staff_schedule($user_id, $days = 7) {
    global $conn;
    $schedules = [];
    $sql = "SELECT date, start_time, end_time, type FROM staff_schedules WHERE staff_id = ? AND date >= CURDATE() ORDER BY date ASC, start_time ASC LIMIT ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $days);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $schedules[] = [
                'date' => $row['date'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'type' => $row['type']
            ];
        }
        mysqli_stmt_close($stmt);
    }
    return $schedules;
}

// Get doctor's data
$doctor_id = $_SESSION["user_id"];
$today_appointments = get_today_appointments($doctor_id, $_SESSION['role']);
$pending_consultations = get_pending_consultations($doctor_id);
$pending_prescriptions = get_pending_prescriptions($doctor_id);
$total_patients = get_total_patients($doctor_id);
$recent_appointments = get_recent_appointments($doctor_id);
$recent_records = get_recent_records($doctor_id);
$recent_prescriptions = get_recent_prescriptions($doctor_id);
$today_staff_schedule = get_today_staff_schedule($_SESSION['user_id']);
$upcoming_staff_schedule = get_upcoming_staff_schedule($_SESSION['user_id'], 7);

// Page title
$page_title = "Doctor Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | MedMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../../../src/styles/variables.css">
    <link rel="stylesheet" href="../../../../src/styles/global.css">
    <link rel="stylesheet" href="../../../../src/styles/components.css">
    <link rel="stylesheet" href="styles/doctor.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <?php include_once "../../../../includes/header.php"; ?>
    
    <div class="doctor-dashboard">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>Welcome, Dr. <?= htmlspecialchars($_SESSION['last_name'] ?? ($_SESSION['full_name'] ?? $_SESSION['username'])); ?>!</h1>
                <p>Manage your patient appointments, consultations, and medical records all in one place.</p>
            </div>
            <div class="welcome-image">
                <img src="/medical/assets/img/doctor-dashboard.svg" alt="Doctor Dashboard" onerror="this.src='/medical/assets/img/default-banner.png'">
            </div>
        </div>
        
 
        
        <div class="d-flex justify-content-end mb-4">
            <div class="date-picker">
                <input type="text" id="date-range" class="form-control" placeholder="Filter by date range">
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">Today's Appointments</h4>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo count($today_appointments); ?></h2>
                <div class="stat-change">
                    <i class="fas fa-calendar"></i>
                    <span>Scheduled for today</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">Pending Consultations</h4>
                    <div class="stat-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $pending_consultations; ?></h2>
                <div class="stat-change">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Waiting for review</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">Pending Prescriptions</h4>
                    <div class="stat-icon">
                        <i class="fas fa-prescription"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $pending_prescriptions; ?></h2>
                <div class="stat-change">
                    <i class="fas fa-pills"></i>
                    <span>To be filled</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">Total Patients</h4>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $total_patients; ?></h2>
                <div class="stat-change positive">
                    <i class="fas fa-user-injured"></i>
                    <span>Under your care</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">Working Hours</h4>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <h2 class="stat-value">8:00 - 17:00</h2>
                <div class="stat-change">
                    <i class="fas fa-calendar-week"></i>
                    <span>Monday - Friday</span>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Appointments -->
            <div class="col-lg-8">
                
                
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Today's Staff Schedule</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($today_staff_schedule)): ?>
                                <?php foreach($today_staff_schedule as $sched): ?>
                                <tr>
                                    <td><?php echo date('h:i A', strtotime($sched['start_time'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($sched['end_time'])); ?></td>
                                    <td><?php echo ucfirst($sched['type']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No staff schedule found for today</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Recent Medical Records</h3>
                        <a href="../../records/doctor-records.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Diagnosis</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($recent_records)): ?>
                                <?php foreach($recent_records as $record): ?>
                                <tr>
                                    <td><?php echo format_date($record['date']); ?></td>
                                    <td><?php echo $record['patient_name']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($record['diagnosis'], 0, 50)) . "..."; ?></td>
                                    <td>
                                        <a href="../../records/view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
                                        <a href="../../records/edit.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No medical records found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Prescriptions Section -->
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Recent Prescriptions</h3>
                        <a href="../../prescription/list.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Medications</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($recent_prescriptions)): ?>
                                <?php foreach($recent_prescriptions as $prescription): ?>
                                <tr>
                                    <td><?php echo format_date($prescription['date']); ?></td>
                                    <td><?php echo $prescription['patient_name']; ?></td>
                                    <td>
                                        <?php
                                        // Fetch medication details for this prescription
                                        $meds = [];
                                        $sql = "SELECT m.name, pm.dosage, pm.frequency, pm.duration FROM prescription_medications pm JOIN medications m ON pm.medication_id = m.medication_id WHERE pm.prescription_id = ?";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("i", $prescription['id']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()) {
                                            $meds[] = $row['name'] .
                                                (empty($row['dosage']) ? '' : ' (' . $row['dosage'] . ')') .
                                                (empty($row['frequency']) ? '' : ', ' . $row['frequency']) .
                                                (empty($row['duration']) ? '' : ', ' . $row['duration']);
                                        }
                                        echo htmlspecialchars(implode('; ', $meds));
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-<?php echo $prescription['status'] == 'Completed' ? 'success' : 
                                            ($prescription['status'] == 'Pending' ? 'warning' : 
                                            ($prescription['status'] == 'Cancelled' ? 'danger' : 'primary')); ?>">
                                            <?php echo ucfirst($prescription['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../../prescription/view.php?id=<?php echo $prescription['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
                                        <a href="../../prescription/edit.php?id=<?php echo $prescription['id']; ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                        <a href="../../prescription/print.php?id=<?php echo $prescription['id']; ?>" class="btn btn-sm btn-outline" target="_blank"><i class="fas fa-print"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No recent prescriptions found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Patient Activity -->
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Patient Activity</h3>
                    </div>
                    <div class="chart-container" style="position: relative; height:250px;">
                        <canvas id="activity-chart"></canvas>
                    </div>
                </div>

                <!-- Add Medications Section -->
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Medications Inventory</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $medications = [];
                                $sql = "SELECT name, description FROM medications ORDER BY name";
                                $result = mysqli_query($conn, $sql);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['description']) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Upcoming Staff Schedules -->
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Upcoming Staff Schedules</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($upcoming_staff_schedule)): ?>
                                <?php foreach($upcoming_staff_schedule as $sched): ?>
                                <tr>
                                    <td><?php echo format_date($sched['date']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($sched['start_time'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($sched['end_time'])); ?></td>
                                    <td><?php echo ucfirst($sched['type']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No upcoming staff schedules found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize date picker
        flatpickr("#date-range", {
            mode: "range",
            dateFormat: "Y-m-d",
            maxDate: "today"
        });

        // Activity chart
        const activityChart = new Chart(
            document.getElementById('activity-chart'),
            {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'Appointments',
                            data: [7, 11, 9, 8, 12, 5, 2],
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.4
                        },
                        {
                            label: 'Consultations',
                            data: [5, 8, 7, 4, 9, 3, 1],
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Weekly Activity'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );
    </script>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
