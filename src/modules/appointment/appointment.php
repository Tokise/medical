<?php
session_start();
require_once '../../config/db.php';
require_once '../../auth/auth_check.php';

// Get user role and permissions
$stmt = $conn->prepare("
    SELECT r.name as role, p.name as permission, u.first_name, u.last_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    JOIN role_permissions rp ON r.id = rp.role_id 
    JOIN permissions p ON rp.permission_id = p.id 
    WHERE u.email = ?
");
$stmt->bind_param("s", $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
$permissions = [];
$role = '';
$fullname = '';

while ($row = $result->fetch_assoc()) {
    $role = $row['role'];
    $permissions[] = $row['permission'];
    $fullname = $row['first_name'] . ' ' . $row['last_name'];
}

// Add after getting user permissions
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_tutorials 
    FROM tutorials t 
    LEFT JOIN user_tutorials ut ON t.id = ut.tutorial_id AND ut.user_id = ?
    WHERE (t.user_role = ? OR t.user_role = 'all')
    AND (ut.completed IS NULL OR ut.completed = 0)
");
$stmt->bind_param("is", $_SESSION['user_id'], $role);
$stmt->execute();
$pending_tutorials = $stmt->get_result()->fetch_assoc()['pending_tutorials'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Appointment - Medical Management</title>
    <link rel="stylesheet" href="../../../styles/global.css">
    <link rel="stylesheet" href="../appointment/styles/appointment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include_once('../includes/header.php'); ?>
    <?php include_once('../includes/sidebar.php'); ?>

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
                        <span class="update">Last updated: 4 March 2025 10:45 AM</span>
                    </div>
                    <div class="tables">
                        <div class="header-tabs">
                            <div class="head-tab activeTab">
                                <p>Student Appointment Request</p>
                                <div class="pending-patient">5</div>
                            </div>
                            <div class="head-tab">
                                <p>Tasks</p>
                                <div class="pending-task">4</div>
                            </div>
                        </div>
                        <div class="tab-content">
                            <div class="table-container">
                                <table>
                                    <thead> <!--Sample Data-->
                                       
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="patient-formdata">
                    <div class="patient-header">
                        <p>Alex Cruz</p>
                        <label class="inProgress">In Progress</label>
                    </div>
                    <div class="patient-info">
                        <div class="patient-data">
                            <label>Date of Birth</label>
                            <p>March 15, 2002</p>
                        </div>
                        <div class="patient-data">
                            <label>Gender</label>
                            <p>Male</p>
                        </div>
                        <div class="patient-data">
                            <label>ID No.</label>
                            <p>s230111174</p>
                        </div>
                        <div class="patient-data">
                            <label>Indication</label>
                            <p>Cough and fever</p>
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
        let studentAppointmentData = [
            {status: "Completed", name: "Alex Cruz", date:"March 1, 2025", reason:"Cough Fever", priority:"Medium"},
            {status: "Completed", name: "Mia Santos", date:"	March 4, 2025", reason:"Cough Fever", priority:"Low"},
            {status: "In Progress", name: "Sam Reyes", date:"March 12, 2025", reason:"Cough Fever", priority:"High"},
            {status: "In Progress", name: "Liam Reyes", date:"March 3, 2025", reason:"Cough Fever", priority:"Medium"}
        ];

        const tHead = document.querySelector('thead');
        tHead.innerHTML = `
            <tr>
                <th>Status</th>
                <th>Student Name</th>
                <th>Appointment Date</th>
                <th>Reason</th>
                <th>Priority</th>
                <th>Action</th>
            </tr>
        `;

        const tBody = document.querySelector('tbody');
        tBody.innerHTML = ''; //clear table body
        studentAppointmentData.forEach(student => {
            const className = student.status == 'Completed' ? 'status-completed': 'inProgress';
            const tr = `
                <tr>
                    <td>
                        <label class=${className}>${student.status}</label>
                    </td>
                    <td>${student.name}</td>
                    <td>${student.date}</td>
                    <td>${student.reason}</td>
                    <td>${student.priority}</td>
                    <td>
                        <button class="btnAction">Action</button>
                    </td>
                </tr>
            `;
            tBody.innerHTML += tr;
            
        });
    }

    function tasksTab(){
        let tasks = [
            {status: "In Progress", taskName: "Check Temperature", patientName: "Alex Cruz", assignedStaff:"March 1, 2025", lastUpdate:"Medium"},
            {status: "Completed", taskName: "Give Medication", patientName: "Sam Reyes", assignedStaff:"	March 4, 2025", lastUpdate:"Low"},
            {status: "In Progress", taskName: "Monitor for Nausea", patientName: "Mia Santos", assignedStaff:"March 12, 2025", lastUpdate:"High"},
            {status: "Completed", taskName: "Record Visit", patientName: "Liam Reyes", assignedStaff:"March 3, 2025", lastUpdate:"Medium"}
        ];

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
        tBody.innerHTML = ''; //clear table body
        tasks.forEach(student => {
            const className = student.status == 'Completed' ? 'status-completed': 'inProgress';
            const tr = `
                <tr>
                     <td>
                        <label class=${className}>${student.status}</label>
                    </td>
                    <td>${student.taskName}</td>
                    <td>${student.patientName}</td>
                    <td>${student.assignedStaff}</td>
                    <td>${student.lastUpdate}</td>
                </tr>
            `;
            tBody.innerHTML += tr;
        });
    }
    
</script>

</html>