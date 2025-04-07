<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get user data and role
$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, r.role_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.role_id 
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user's role
$role = $user['role_name'];
$fullname = $user['first_name'] . ' ' . $user['last_name'];

// Get pending tutorials if any
$pending_tutorials = 0; // This would be implemented if tutorials are added to the system

// Get appointments based on user role
$appointments = [];
if ($role == 'Doctor' || $role == 'Nurse') {
    // For medical staff, get appointments they are assigned to
    $appointmentsQuery = "SELECT c.*, u.first_name, u.last_name, u.school_id, u.profile_image 
                         FROM consultations c
                         JOIN users u ON c.patient_id = u.user_id
                         WHERE c.staff_id = ? AND DATE(c.consultation_date) = CURDATE()
                         ORDER BY c.consultation_date ASC";
    $appointmentsStmt = $conn->prepare($appointmentsQuery);
    $appointmentsStmt->bind_param("i", $user_id);
    $appointmentsStmt->execute();
    $appointments = $appointmentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // For students/teachers, get their own appointments
    $appointmentsQuery = "SELECT c.*, u.first_name, u.last_name, ms.specialization 
                         FROM consultations c
                         JOIN users u ON c.staff_id = u.user_id
                         JOIN medical_staff ms ON u.user_id = ms.user_id
                         WHERE c.patient_id = ? AND DATE(c.consultation_date) = CURDATE()
                         ORDER BY c.consultation_date ASC";
    $appointmentsStmt = $conn->prepare($appointmentsQuery);
    $appointmentsStmt->bind_param("i", $user_id);
    $appointmentsStmt->execute();
    $appointments = $appointmentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Pass the role to be used in the sidebar
$role = $user['role_name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Appointment - Medical Management</title>
    <link rel="stylesheet" href="../../../styles/global.css">
    <link rel="stylesheet" href="styles/appointment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include_once('../../includes/header.php'); ?>
    <?php include_once('../../includes/sidebar.php'); ?>

    <section>
        <div class="content">
            <div class="main-container">
                <div class="list-patient">
                    <div class="header-actions">
                        <div class="filter">
                            <div class="patient-search">
                                <input type="text" placeholder="Search"/>
                                <i class="fas fa-search"></i>
                            </div>
                            <span>Filter</span>
                            <span class="selected-filter">
                                Status: Actionable
                                <i class="fas fa-times"></i>
                            </span>
                        </div>
                        <span class="update">Last updated: <?= date('F d, Y h:i A') ?></span>
                    </div>
                    <div class="tables">
                        <div class="header-tabs">
                            <div class="head-tab activeTab">
                                <p>Appointment Requests</p>
                                <div class="pending-patient"><?= count($appointments) ?></div>
                            </div>
                            <div class="head-tab">
                                <p>Tasks</p>
                                <div class="pending-task">0</div>
                            </div>
                        </div>
                        <div class="tab-content">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Name</th>
                                            <th>Appointment Date</th>
                                            <th>Reason</th>
                                            <th>Priority</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($appointments)): ?>
                                            <?php foreach ($appointments as $appointment): ?>
                                                <tr>
                                                    <td>
                                                        <label class="<?= strtolower($appointment['status']) ?>"><?= $appointment['status'] ?></label>
                                                    </td>
                                                    <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></td>
                                                    <td><?= date('F d, Y h:i A', strtotime($appointment['consultation_date'])) ?></td>
                                                    <td><?= htmlspecialchars($appointment['symptoms'] ?? 'Not specified') ?></td>
                                                    <td>
                                                        <?php 
                                                        // Determine priority based on symptoms or other factors
                                                        $priority = 'Medium';
                                                        if (strpos(strtolower($appointment['symptoms'] ?? ''), 'emergency') !== false || 
                                                            strpos(strtolower($appointment['symptoms'] ?? ''), 'severe') !== false) {
                                                            $priority = 'High';
                                                        } elseif (strpos(strtolower($appointment['symptoms'] ?? ''), 'mild') !== false) {
                                                            $priority = 'Low';
                                                        }
                                                        echo $priority;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <button class="btnAction">Action</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="no-data">No appointments scheduled for today</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="patient-formdata">
                    <?php if (!empty($appointments)): ?>
                        <?php $selectedAppointment = $appointments[0]; ?>
                        <div class="patient-header">
                            <p><?= htmlspecialchars($selectedAppointment['first_name'] . ' ' . $selectedAppointment['last_name']) ?></p>
                            <label class="<?= strtolower($selectedAppointment['status']) ?>"><?= $selectedAppointment['status'] ?></label>
                        </div>
                        <div class="patient-info">
                            <div class="patient-data">
                                <label>ID No.</label>
                                <p><?= htmlspecialchars($selectedAppointment['school_id'] ?? 'Not specified') ?></p>
                            </div>
                            <div class="patient-data">
                                <label>Indication</label>
                                <p><?= htmlspecialchars($selectedAppointment['symptoms'] ?? 'Not specified') ?></p>
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
                                        <p><?= htmlspecialchars($selectedAppointment['symptoms'] ?? 'Not specified') ?></p>
                                    </div>
                                    <div class="patient-data">
                                        <label>Chemicals</label>
                                        <p>NO</p>
                                    </div>
                                    <div class="patient-data">
                                        <label>Site:</label>
                                        <p>Not specified</p>
                                    </div>
                                    <div class="patient-data">
                                        <label>Severity</label>
                                        <p><label>6</label>/10</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="patient-header">
                            <p>No appointments</p>
                            <label>No data</label>
                        </div>
                        <div class="patient-info">
                            <div class="patient-data">
                                <label>No appointments scheduled for today</label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</body>

<script>
    document.addEventListener("DOMContentLoaded", (event) => {
        studentRquestTab();
        document.querySelector('.page-title').innerText = "Appointment";
    });

    const tabs = document.querySelectorAll('.head-tab');
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', (e) => {
            tabs.forEach(activeTab => {
                if(activeTab.classList.contains('activeTab')){
                    activeTab.classList.remove('activeTab');
                }
            }); 
            tab.classList.add('activeTab');

            switch(index){
                case 0: studentRquestTab()
                    break;
                case 1: tasksTab();
                    break;
            }
        })
    });

    function studentRquestTab(){
        // This function is now handled by PHP
    }

    function tasksTab(){
        // This function would be implemented if tasks are added to the system
        const tHead = document.querySelector('thead');
        tHead.innerHTML = `
            <tr>
                <th>Task Status</th>
                <th>Task Name</th>
                <th>Name</th>
                <th>Assigned Staff</th>
                <th>Last Updated</th>
            </tr>
        `;

        const tBody = document.querySelector('tbody');
        tBody.innerHTML = '<tr><td colspan="5" class="no-data">No tasks available</td></tr>';
    }
</script>

</html>