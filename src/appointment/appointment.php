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
    <link rel="stylesheet" href="../../src/styles/global.css">
    <link rel="stylesheet" href="../../src/styles/appointment.css">
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
                                        <tr>
                                            <th>Status</th>
                                            <th>Student Name</th>
                                            <th>Appointment Date</th>
                                            <th>Reason</th>
                                            <th>Priority</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Completed</td>
                                            <td>Alex Cruz</td>
                                            <td>March 1, 2025</td>
                                            <td>Cough Fever</td>
                                            <td>Medium</td>
                                            <td>
                                                <button>Action</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Completed</td>
                                            <td>Mia Santos</td>
                                            <td>March 4, 2025</td>
                                            <td>Cough Fever</td>
                                            <td>Low</td>
                                            <td>
                                                <button>Action</button>
                                            </td>
                                        </tr>
                                        <tr>
                                        <td>In Progress</td>
                                            <td>Sam Reyes</td>
                                            <td>March 12, 2025</td>
                                            <td>Cough Fever</td>
                                            <td>High</td>
                                            <td>
                                                <button>Action</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>In Progress</td>
                                            <td>Liam Reyes</td>
                                            <td>March 3, 2025</td>
                                            <td>Cough Fever</td>
                                            <td>Medium</td>
                                            <td>
                                                <button>Action</button>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>In Progress</td>
                                            <td>Liam Reyes</td>
                                            <td>March 3, 2025</td>
                                            <td>Cough Fever</td>
                                            <td>Medium</td>
                                            <td>
                                                <button>Action</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>In Progress</td>
                                            <td>Liam Reyes</td>
                                            <td>March 3, 2025</td>
                                            <td>Cough Fever</td>
                                            <td>Medium</td>
                                            <td>
                                                <button>Action</button>
                                            </td>
                                        </tr>
                                       
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="patient-formdata">
                    <div class="patient-name">
                        <p>Alex Cruz</p>
                        <p>In Progress</p>
                    </div>
                    <div class="patient-info">

                    </div>
                    <div class="patient-health">
                        <div>

                        </div>
                        <div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

<script>
    const tabs = document.querySelectorAll('.head-tab');

    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            tabs.forEach(activeTab => {
                if(activeTab.classList.contains('activeTab')){
                activeTab.classList.remove('activeTab');
                }
            }); 
            tab.classList.add('activeTab');
        })
    });
</script>

</html>