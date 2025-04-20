<?php
// Start session before any output
session_start();

// Include configuration and function files
require_once '../../../../config/config.php';

// Define necessary functions directly in this file since functions.php is not available
function get_dashboard_stats() {
    global $conn;
    
    $stats = [
        'doctors' => 0,
        'nurses' => 0,
        'students' => 0,
        'teachers' => 0,
        'appointments' => 0,
        'inventory' => [
            'total_items' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0
        ]
    ];
    
    // Get counts with error handling to prevent query failures
    // Try to get medical staff counts - adjust query to match actual table structure
    try {
        // Check if medical_staff table exists and has the proper structure
        $sql = "SHOW TABLES LIKE 'medical_staff'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            // Check columns
            $sql = "SHOW COLUMNS FROM medical_staff";
            $columns = mysqli_query($conn, $sql);
            $columnNames = [];
            
            if ($columns) {
                while ($col = mysqli_fetch_assoc($columns)) {
                    $columnNames[] = $col['Field'];
                }
            }
            
            // If staff_type column exists
            if (in_array('staff_type', $columnNames)) {
                $sql = "SELECT COUNT(*) as count FROM medical_staff ms 
                        JOIN users u ON ms.user_id = u.user_id 
                        WHERE ms.staff_type = 'doctor'";
                $result = mysqli_query($conn, $sql);
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $stats['doctors'] = $row['count'];
                }
                
                $sql = "SELECT COUNT(*) as count FROM medical_staff ms 
                        JOIN users u ON ms.user_id = u.user_id 
                        WHERE ms.staff_type = 'nurse'";
                $result = mysqli_query($conn, $sql);
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $stats['nurses'] = $row['count'];
                }
            } 
            // Try with role column if staff_type doesn't exist
            elseif (in_array('role', $columnNames)) {
                $sql = "SELECT COUNT(*) as count FROM medical_staff ms 
                        JOIN users u ON ms.user_id = u.user_id 
                        WHERE ms.role = 'doctor'";
                $result = mysqli_query($conn, $sql);
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $stats['doctors'] = $row['count'];
                }
                
                $sql = "SELECT COUNT(*) as count FROM medical_staff ms 
                        JOIN users u ON ms.user_id = u.user_id 
                        WHERE ms.role = 'nurse'";
                $result = mysqli_query($conn, $sql);
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $stats['nurses'] = $row['count'];
                }
            }
            // Fallback to direct users table if no specific column found
            else {
                $sql = "SELECT COUNT(*) as count FROM users u 
                        JOIN roles r ON u.role_id = r.role_id 
                        WHERE r.role_name = 'doctor'";
                $result = mysqli_query($conn, $sql);
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $stats['doctors'] = $row['count'];
                }
                
                $sql = "SELECT COUNT(*) as count FROM users u 
                        JOIN roles r ON u.role_id = r.role_id 
                        WHERE r.role_name = 'nurse'";
                $result = mysqli_query($conn, $sql);
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $stats['nurses'] = $row['count'];
                }
            }
        }
        else {
            // Fallback to users table directly
            $sql = "SELECT COUNT(*) as count FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE r.role_name = 'doctor'";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['doctors'] = $row['count'];
            }
            
            $sql = "SELECT COUNT(*) as count FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE r.role_name = 'nurse'";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['nurses'] = $row['count'];
            }
        }
    } catch (Exception $e) {
        // Silently catch errors and continue
    }
    
    // Get student count - check if table exists first
    try {
        $sql = "SHOW TABLES LIKE 'students'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $sql = "SELECT COUNT(*) as count FROM students";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['students'] = $row['count'];
            }
        } else {
            // Fallback to users table with role
            $sql = "SELECT COUNT(*) as count FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE r.role_name = 'student'";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['students'] = $row['count'];
            }
        }
    } catch (Exception $e) {
        // Silently catch errors and continue
    }
    
    // Get teacher count - check if table exists first
    try {
        $sql = "SHOW TABLES LIKE 'teachers'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $sql = "SELECT COUNT(*) as count FROM teachers";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['teachers'] = $row['count'];
            }
        } else {
            // Fallback to users table with role
            $sql = "SELECT COUNT(*) as count FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE r.role_name = 'teacher'";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['teachers'] = $row['count'];
            }
        }
    } catch (Exception $e) {
        // Silently catch errors and continue
    }
    
    // Get appointment count - check if table exists first
    try {
        $sql = "SHOW TABLES LIKE 'appointments'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $sql = "SELECT COUNT(*) as count FROM appointments";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['appointments'] = $row['count'];
            }
        }
    } catch (Exception $e) {
        // Silently catch errors and continue
    }
    
    // Get inventory stats - check if table exists first
    try {
        $sql = "SHOW TABLES LIKE 'medical_supplies'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $sql = "SELECT 
                        COUNT(*) as total_items,
                        SUM(CASE WHEN quantity <= min_quantity AND quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                        SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock
                    FROM medical_supplies";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['inventory']['total_items'] = $row['total_items'];
                $stats['inventory']['low_stock'] = $row['low_stock'];
                $stats['inventory']['out_of_stock'] = $row['out_of_stock'];
            }
        }
    } catch (Exception $e) {
        // Silently catch errors and continue
    }
    
    return $stats;
}

function get_recent_appointments($limit = 5) {
    global $conn;
    
    $appointments = [];
    
    try {
        // Check if appointments table exists
        $sql = "SHOW TABLES LIKE 'appointments'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            // Check if medical_staff table exists
            $sql = "SHOW TABLES LIKE 'medical_staff'";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $sql = "SELECT 
                           a.appointment_id,
                           a.appointment_date,
                           a.status,
                           CONCAT(u_patient.first_name, ' ', u_patient.last_name) as patient_name,
                           CONCAT(u_doctor.first_name, ' ', u_doctor.last_name) as doctor_name
                       FROM 
                           appointments a
                       JOIN 
                           users u_patient ON a.patient_id = u_patient.user_id
                       JOIN 
                           medical_staff ms ON a.staff_id = ms.staff_id
                       JOIN 
                           users u_doctor ON ms.user_id = u_doctor.user_id
                       ORDER BY 
                           a.appointment_date DESC
                       LIMIT $limit";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $appointments[] = $row;
                    }
                }
            } else {
                // Simplified query if medical_staff table doesn't exist
                $sql = "SELECT 
                           a.appointment_id,
                           a.appointment_date,
                           a.status,
                           CONCAT(u.first_name, ' ', u.last_name) as patient_name,
                           'Doctor' as doctor_name
                       FROM 
                           appointments a
                       JOIN 
                           users u ON a.patient_id = u.user_id
                       ORDER BY 
                           a.appointment_date DESC
                       LIMIT $limit";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $appointments[] = $row;
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Continue with empty appointments array
    }
    
    return $appointments;
}

function get_low_stock_items($limit = 5) {
    global $conn;
    
    $items = [];
    
    try {
        // Check if medical_supplies table exists
        $sql = "SHOW TABLES LIKE 'medical_supplies'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            // Check column names to ensure we use the right ones
            $sql = "SHOW COLUMNS FROM medical_supplies";
            $columns_result = mysqli_query($conn, $sql);
            $columns = [];
            
            if ($columns_result) {
                while ($col = mysqli_fetch_assoc($columns_result)) {
                    $columns[] = $col['Field'];
                }
            }
            
            // Based on schema.sql, the correct column names are:
            // item_id, item_name, current_quantity, reorder_level
            $sql = "SELECT 
                        item_id,
                        item_name as name,
                        description as category,
                        current_quantity as quantity,
                        reorder_level as min_quantity
                    FROM 
                        medical_supplies
                    WHERE 
                        current_quantity <= reorder_level
                    ORDER BY 
                        current_quantity ASC
                    LIMIT $limit";
            
            $result = mysqli_query($conn, $sql);
            
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $items[] = $row;
                }
            }
        }
    } catch (Exception $e) {
        // Continue with empty items array
    }
    
    return $items;
}

function format_date($date, $format = 'M d, Y h:i A') {
    $datetime = new DateTime($date);
    return $datetime->format($format);
}

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("Location: ../../../auth/login.php");
    exit;
}

// Check if user is admin
if(!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin"){
    header("Location: ../index.php");
    exit;
}

// Get user info - using username instead of user_id if it's not available
$username = $_SESSION['username'] ?? 'Admin';

// Get dashboard statistics
$stats = get_dashboard_stats();
$recent_appointments = get_recent_appointments(5);
$low_stock_items = get_low_stock_items(5);

// Page title
$page_title = "Admin Dashboard";
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
    <link rel="stylesheet" href="styles/admin.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <?php include_once "../../../../includes/header.php"; ?>
    
    <div class="admin-dashboard">
        <h1 class="page-title">Admin Dashboard</h1>
        
        <div class="d-flex justify-content-end mb-4">
            <div class="date-picker">
                <input type="text" id="date-range" class="form-control" placeholder="Filter by date range">
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">Doctors</h4>
                    <div class="stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['doctors']; ?></h2>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Active medical providers</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">Nurses</h4>
                    <div class="stat-icon">
                        <i class="fas fa-user-nurse"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['nurses']; ?></h2>
                <div class="stat-change">
                    <i class="fas fa-minus"></i>
                    <span>Supporting medical staff</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">Students</h4>
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['students']; ?></h2>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Registered students</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">Appointments</h4>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['appointments']; ?></h2>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Total appointments</span>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Appointments -->
            <div class="col-lg-8">
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Recent Appointments</h3>
                        <a href="../appointment/list.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($recent_appointments)): ?>
                                <?php foreach($recent_appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo format_date($appointment['appointment_date'], 'M d, Y'); ?></td>
                                    <td><?php echo $appointment['patient_name']; ?></td>
                                    <td><?php echo $appointment['doctor_name']; ?></td>
                                    <td>
                                        <span class="badge-status badge-<?php echo $appointment['status'] == 'Completed' ? 'success' : 
                                            ($appointment['status'] == 'Scheduled' ? 'primary' : 
                                            ($appointment['status'] == 'Cancelled' ? 'danger' : 'warning')); ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No recent appointments</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Inventory Status -->
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Inventory Status</h3>
                        <a href="../inventory/list.php" class="btn btn-primary btn-sm">Manage Inventory</a>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="stat-card" style="margin-bottom:0">
                                <p class="stat-title">Total Items</p>
                                <h3 class="stat-value"><?php echo $stats['inventory']['total_items']; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card" style="margin-bottom:0">
                                <p class="stat-title">Low Stock</p>
                                <h3 class="stat-value"><?php echo $stats['inventory']['low_stock']; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card" style="margin-bottom:0">
                                <p class="stat-title">Out of Stock</p>
                                <h3 class="stat-value"><?php echo $stats['inventory']['out_of_stock']; ?></h3>
                            </div>
                        </div>
                    </div>
                        
                    <h6 class="mb-3">Low Stock Items</h6>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Min Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($low_stock_items)): ?>
                                <?php foreach($low_stock_items as $item): ?>
                                <tr>
                                    <td><?php echo $item['name']; ?></td>
                                    <td><?php echo $item['category']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo $item['min_quantity']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No low stock items</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Medical Staff Activity -->
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Medical Staff Activity</h3>
                    </div>
                    <div class="chart-container" style="position: relative; height:250px;">
                        <canvas id="activity-chart"></canvas>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Quick Actions</h3>
                    </div>
                    <div class="quick-actions">
                        <a href="../user/add.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h4 class="action-title">Add User</h4>
                            <p class="action-description">Create a new user account</p>
                        </a>
                   
                        <a href="../inventory/add.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-pills"></i>
                            </div>
                            <h4 class="action-title">Add Inventory</h4>
                            <p class="action-description">Add new supplies</p>
                        </a>
                        <a href="../reports/index.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h4 class="action-title">Generate Reports</h4>
                            <p class="action-description">Create system reports</p>
                        </a>
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
                            data: [12, 19, 15, 8, 22, 14, 6],
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.4
                        },
                        {
                            label: 'Consultations',
                            data: [10, 15, 12, 5, 18, 10, 4],
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
