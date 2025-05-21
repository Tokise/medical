<?php
session_start();
require_once '../../../../config/config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /medical/src/auth/login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_schedule':
                $staffId = (int)$_POST['staff_id'];
                $date = mysqli_real_escape_string($conn, $_POST['date']);
                $startTime = mysqli_real_escape_string($conn, $_POST['start_time']);
                $endTime = mysqli_real_escape_string($conn, $_POST['end_time']);
                $type = mysqli_real_escape_string($conn, $_POST['type']); // 'shift' or 'break'
                if (strtotime($date) < strtotime(date('Y-m-d'))) {
                    header("Location: schedule_management.php?error=pastdate");
                    exit();
                }
                
                $sql = "INSERT INTO staff_schedules (staff_id, date, start_time, end_time, type) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "issss", $staffId, $date, $startTime, $endTime, $type);
                
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: schedule_management.php?success=1");
                    exit();
                } else {
                    header("Location: schedule_management.php?error=1");
                    exit();
                }
                break;
                
            case 'delete_schedule':
                $scheduleId = (int)$_POST['schedule_id'];
                
                $sql = "DELETE FROM staff_schedules WHERE schedule_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $scheduleId);
                
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: schedule_management.php?success=1");
                    exit();
                } else {
                    header("Location: schedule_management.php?error=1");
                    exit();
                }
                break;

            case 'update_schedule':
                $scheduleId = (int)$_POST['schedule_id'];
                $date = mysqli_real_escape_string($conn, $_POST['date']);
                $startTime = mysqli_real_escape_string($conn, $_POST['start_time']);
                $endTime = mysqli_real_escape_string($conn, $_POST['end_time']);
                $type = mysqli_real_escape_string($conn, $_POST['type']);
                if (strtotime($date) < strtotime(date('Y-m-d'))) {
                    header("Location: schedule_management.php?error=pastdate");
                    exit();
                }
                $sql = "UPDATE staff_schedules SET date=?, start_time=?, end_time=?, type=? WHERE schedule_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $date, $startTime, $endTime, $type, $scheduleId);
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: schedule_management.php?success=1");
                    exit();
                } else {
                    header("Location: schedule_management.php?error=1");
                    exit();
                }
                break;
        }
    }
}

// Get all staff members
$staff = [];
$sql = "SELECT u.*, r.role_name, 
        CASE 
            WHEN d.doctor_id IS NOT NULL THEN d.specialization 
            ELSE NULL 
        END as specialization
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN doctors d ON u.user_id = d.user_id 
        LEFT JOIN nurses n ON u.user_id = n.user_id 
        WHERE r.role_name IN ('doctor', 'nurse')
        ORDER BY u.first_name, u.last_name";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $staff[] = $row;
    }
}

// Get schedules for the current month
$currentMonth = date('Y-m');
$schedules = [];
$sql = "SELECT s.*, u.first_name, u.last_name, r.role_name 
        FROM staff_schedules s 
        JOIN users u ON s.staff_id = u.user_id 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE DATE_FORMAT(s.date, '%Y-%m') = ? 
        ORDER BY s.date, s.start_time";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $currentMonth);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $schedules[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management - Medical Management System</title>
    <link rel="stylesheet" href="../../../../src/styles/variables.css">
    <link rel="stylesheet" href="../../../../src/styles/global.css">
    <link rel="stylesheet" href="../../../../src/styles/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .schedule-management {
            padding: 2rem;
            margin-top: 5rem;
        }
        
        .schedule-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .schedule-sidebar {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .schedule-form {
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .calendar-container {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .fc-event {
            cursor: pointer;
        }
        
        .fc-event.doctor {
            background-color: #007bff;
            border-color: #0056b3;
        }
        
        .fc-event.nurse {
            background-color: #28a745;
            border-color: #1e7e34;
        }
        
        .fc-event.break {
            background-color: #ffc107;
            border-color: #d39e00;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .add-schedule-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .schedule-table-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        table.schedule-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.schedule-table th, table.schedule-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        table.schedule-table th {
            background: #f8f9fa;
            color: #333;
        }
        .action-btns button {
            margin-right: 0.5rem;
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            overflow: auto;
            background: rgba(0,0,0,0.4);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #fff;
            margin: auto;
            padding: 2rem;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            color: #888;
            cursor: pointer;
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(40px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
    <?php include '../../../../includes/header.php'; ?>
    
    <div class="schedule-management">
        <h1>Schedule Management</h1>
        <button class="add-schedule-btn" id="openAddModal" title="Add Schedule"><i class="fas fa-plus"></i></button>
        <div class="calendar-container">
            <div id="calendar"></div>
        </div>
        <div class="schedule-table-container">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Staff Name</th>
                        <th>Role</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($schedule['role_name'])); ?></td>
                            <td><?php echo htmlspecialchars($schedule['date']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['start_time']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['end_time']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($schedule['type'])); ?></td>
                            <td class="action-btns">
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this schedule?')"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Modal for Add Schedule -->
        <div class="modal" id="addScheduleModal">
            <div class="modal-content">
                <span class="close-modal" id="closeAddModal">&times;</span>
                <h2>Add Schedule</h2>
                <form method="POST" action="" id="addScheduleForm">
                    <input type="hidden" name="action" value="add_schedule">
                    <div class="form-group">
                        <label for="staff_id">Staff Member</label>
                        <select id="staff_id" name="staff_id" required>
                            <option value="">Select Staff Member</option>
                            <?php foreach ($staff as $member): ?>
                                <option value="<?php echo $member['user_id']; ?>">
                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name'] . ' (' . ucfirst($member['role_name']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="shift">Shift</option>
                            <option value="break">Break</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:1rem;">Add Schedule</button>
                </form>
            </div>
        </div>
        <!-- Modal for Viewing/Updating Schedule Details -->
        <div class="modal" id="viewScheduleModal">
            <div class="modal-content" id="viewScheduleContent" style="animation: fadeInModal 0.3s cubic-bezier(.4,2,.6,1);">
                <button class="close-modal" id="closeViewModal" aria-label="Close">&times;</button>
                <h2>Schedule Details</h2>
                <hr class="modal-divider" />
                <form id="updateScheduleForm" method="POST" action="">
                    <input type="hidden" name="action" value="update_schedule">
                    <input type="hidden" name="schedule_id" id="update_schedule_id">
                    <div class="form-group">
                        <label for="update_staff">Staff</label>
                        <input type="text" id="update_staff" name="update_staff" readonly>
                    </div>
                    <div class="form-group">
                        <label for="update_date">Date</label>
                        <input type="date" id="update_date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="update_start_time">Start Time</label>
                        <input type="time" id="update_start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="update_end_time">End Time</label>
                        <input type="time" id="update_end_time" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label for="update_type">Type</label>
                        <select id="update_type" name="type" required>
                            <option value="shift">Shift</option>
                            <option value="break">Break</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Modal logic
        const addModal = document.getElementById('addScheduleModal');
        const openAddModal = document.getElementById('openAddModal');
        const closeAddModal = document.getElementById('closeAddModal');
        openAddModal.onclick = () => addModal.style.display = 'flex';
        closeAddModal.onclick = () => addModal.style.display = 'none';
        window.onclick = function(event) {
            if (event.target === addModal) addModal.style.display = 'none';
        }
        // SweetAlert2 notifications from PHP
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({icon:'success',title:'Success',text:'Schedule action completed successfully!'});
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            Swal.fire({icon:'error',title:'Error',text:'There was an error processing your request.'});
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'pastdate'): ?>
            Swal.fire({icon:'error',title:'Invalid Date',text:'You can only schedule for today or future dates.'});
        <?php endif; ?>
        // Calendar JS remains unchanged
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    <?php foreach ($schedules as $schedule): ?>
                    {
                        id: '<?php echo $schedule['schedule_id']; ?>',
                        title: '<?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?>',
                        start: '<?php echo $schedule['date'] . 'T' . $schedule['start_time']; ?>',
                        end: '<?php echo $schedule['date'] . 'T' . $schedule['end_time']; ?>',
                        className: '<?php echo $schedule['type'] === 'break' ? 'break' : strtolower($schedule['role_name']); ?>',
                        extendedProps: {
                            type: '<?php echo $schedule['type']; ?>',
                            role: '<?php echo $schedule['role_name']; ?>'
                        }
                    },
                    <?php endforeach; ?>
                ],
                eventClick: function(info) {
                    // Fetch event details
                    const event = info.event;
                    document.getElementById('update_schedule_id').value = event.id;
                    document.getElementById('update_staff').value = event.title;
                    document.getElementById('update_date').value = event.startStr.split('T')[0];
                    document.getElementById('update_start_time').value = event.startStr.split('T')[1].slice(0,5);
                    document.getElementById('update_end_time').value = event.endStr ? event.endStr.split('T')[1].slice(0,5) : '';
                    document.getElementById('update_type').value = event.extendedProps.type;
                    document.getElementById('viewScheduleModal').style.display = 'flex';
                }
            });
            calendar.render();
        });
        document.getElementById('closeViewModal').onclick = function() {
            document.getElementById('viewScheduleModal').style.display = 'none';
        };
        window.onclick = function(event) {
            if (event.target === document.getElementById('viewScheduleModal')) document.getElementById('viewScheduleModal').style.display = 'none';
        };
    </script>
</body>
</html> 