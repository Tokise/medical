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
    // Try to get doctors and nurses counts from the new tables
    try {
        // Check if doctors table exists
        $sql = "SHOW TABLES LIKE 'doctors'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $sql = "SELECT COUNT(*) as count FROM doctors d 
                    JOIN users u ON d.user_id = u.user_id";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['doctors'] = $row['count'];
            }
        } else {
            // Fallback to users table with role
            $sql = "SELECT COUNT(*) as count FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE r.role_name = 'doctor'";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['doctors'] = $row['count'];
            }
        }
        
        // Check if nurses table exists
        $sql = "SHOW TABLES LIKE 'nurses'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $sql = "SELECT COUNT(*) as count FROM nurses n 
                    JOIN users u ON n.user_id = u.user_id";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $stats['nurses'] = $row['count'];
            }
        } else {
            // Fallback to users table with role
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
            // First check for doctors table (new schema)
            $sql = "SHOW TABLES LIKE 'doctors'";
            $doctor_table_exists = mysqli_query($conn, $sql) && mysqli_num_rows(mysqli_query($conn, $sql)) > 0;
            
            // Also check for nurses table (new schema)
            $sql = "SHOW TABLES LIKE 'nurses'";
            $nurse_table_exists = mysqli_query($conn, $sql) && mysqli_num_rows(mysqli_query($conn, $sql)) > 0;
            
            if ($doctor_table_exists || $nurse_table_exists) {
                $sql = "SELECT COUNT(*) as count FROM appointments";
                $result = mysqli_query($conn, $sql);
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $stats['appointments'] = $row['count'];
                }
            } else {
                $sql = "SELECT COUNT(*) as count FROM appointments";
                $result = mysqli_query($conn, $sql);
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $stats['appointments'] = $row['count'];
                }
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

function get_recent_appointments($conn, $limit = 5) {
    $appointments = [];
    
    // First check for doctors table (new schema)
    $sql = "SHOW TABLES LIKE 'doctors'";
    $doctor_table_exists = mysqli_query($conn, $sql) && mysqli_num_rows(mysqli_query($conn, $sql)) > 0;
    
    // Also check for nurses table (new schema)
    $sql = "SHOW TABLES LIKE 'nurses'";
    $nurse_table_exists = mysqli_query($conn, $sql) && mysqli_num_rows(mysqli_query($conn, $sql)) > 0;
    
    if ($doctor_table_exists || $nurse_table_exists) {
        // Using the new schema with separate tables for doctors and nurses
        $sql = "SELECT 
                   a.appointment_id,
                   a.appointment_date,
                   a.status,
                   CONCAT(u_patient.first_name, ' ', u_patient.last_name) as patient_name,
                   CONCAT(u_staff.first_name, ' ', u_staff.last_name) as staff_name,
                   CASE 
                       WHEN d.doctor_id IS NOT NULL THEN 'Doctor' 
                       WHEN n.nurse_id IS NOT NULL THEN 'Nurse' 
                       ELSE 'Staff' 
                   END as staff_type
               FROM 
                   appointments a
               JOIN 
                   users u_patient ON a.patient_id = u_patient.user_id
               JOIN 
                   users u_staff ON a.doctor_id = u_staff.user_id
               LEFT JOIN 
                   doctors d ON a.doctor_id = d.user_id
               LEFT JOIN 
                   nurses n ON a.doctor_id = n.user_id
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
        // Simplified query for old schema
        $sql = "SELECT 
                   a.appointment_id,
                   a.appointment_date,
                   a.status,
                   CONCAT(u_patient.first_name, ' ', u_patient.last_name) as patient_name,
                   CONCAT(u_staff.first_name, ' ', u_staff.last_name) as staff_name,
                   'Medical Staff' as staff_type
               FROM 
                   appointments a
               JOIN 
                   users u_patient ON a.patient_id = u_patient.user_id
               JOIN 
                   users u_staff ON a.doctor_id = u_staff.user_id
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
$recent_appointments = get_recent_appointments($conn, 5);
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
    <style>
        .admin-dashboard {
            padding: 2.5rem 2rem 2rem 2rem;
            margin-top: 5rem;
            min-height: 100vh;
            background: #f8f9fb;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 1.5rem 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
        }
        .stat-header {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .stat-title {
            font-size: 1rem;
            font-weight: 600;
            color: #555;
        }
        .stat-icon {
            font-size: 1.5rem;
            background: #f0f4ff;
            color: #3b82f6;
            border-radius: 50%;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-value {
            font-size: 2.1rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 0.5rem;
        }
        .stat-change {
            font-size: 0.95rem;
            margin-top: 0.2rem;
        }
        .stat-change.positive { color: #22c55e; }
        .stat-change.negative { color: #ef4444; }
        .btn.btn-primary.btn-sm.mt-2 { margin-top: 1rem !important; }
        .activity-section, .quick-actions, .inventory-table-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 1.5rem 1.2rem;
            margin-bottom: 2rem;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 0.7rem;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #222;
        }
        .table-responsive { width: 100%; overflow-x: auto; }
        .data-table th, .data-table td {
            padding: 0.7rem 1rem;
        }
        .data-table th {
            background: #f8f9fa;
            color: #333;
        }
        .data-table tr:hover { background: #f4f7fa; }
        .badge-status {
            border-radius: 12px;
            padding: 0.3em 0.8em;
            font-size: 0.95em;
            font-weight: 600;
        }
        .badge-success { background: #22c55e22; color: #22c55e; }
        .badge-primary { background: #3b82f622; color: #3b82f6; }
        .badge-danger { background: #ef444422; color: #ef4444; }
        .badge-warning { background: #facc1522; color: #facc15; }
        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr; }
            .admin-dashboard { padding: 1rem; }
        }
    </style>
</head>
<body>
    <?php include_once "../../../../includes/header.php"; ?>
    
    <div class="admin-dashboard">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</h1>
                <p>Manage your healthcare facility, track activity, and maintain system resources from this central dashboard.</p>
            </div>
            <div class="welcome-image">
                <img src="/medical/assets/img/admin-dashboard.svg" alt="Admin Dashboard" onerror="this.src='/medical/assets/img/default-banner.png'">
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
                    <h4 class="stat-title">Inventory</h4>
                    <div class="stat-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['inventory']['total_items']; ?></h2>
                <div class="stat-change <?php echo $stats['inventory']['low_stock'] > 0 ? 'negative' : 'positive'; ?>">
                    <i class="fas fa-<?php echo $stats['inventory']['low_stock'] > 0 ? 'exclamation-triangle' : 'check'; ?>"></i>
                    <span><?php echo $stats['inventory']['low_stock'] > 0 ? 'Low stock items' : 'All stocked'; ?></span>
                </div>
                <a href="/medical/src/modules/dashboard/admin/inventory_management.php" class="btn btn-primary btn-sm mt-2" style="width:100%;">Manage Inventory</a>
            </div>
        </div>

        <div class="row">
                <!-- Inventory Status (real-time) -->
                <div class="activity-section" id="inventory-status-section">
                    <div class="section-header">
                        <h3 class="section-title">Inventory Status</h3>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="stat-card" style="margin-bottom:0">
                                <p class="stat-title">Total Items</p>
                                <h3 class="stat-value" id="inv-total-items">0</h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card" style="margin-bottom:0">
                                <p class="stat-title">Low Stock</p>
                                <h3 class="stat-value" id="inv-low-stock">0</h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card" style="margin-bottom:0">
                                <p class="stat-title">Out of Stock</p>
                                <h3 class="stat-value" id="inv-out-stock">0</h3>
                            </div>
                        </div>
                    </div>
                    <h6 class="mb-3">Low Stock Items</h6>
                    <div class="table-responsive">
                        <table class="data-table" id="low-stock-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Min Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Low stock items will be loaded here via AJAX -->
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

                <!-- Add Inventory Trends Chart -->
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Inventory Trends</h3>
                    </div>
                    <div class="chart-container" style="position: relative; height:250px;">
                        <canvas id="inventory-trend-chart"></canvas>
                    </div>
                </div>

                <!-- Add Inventory Table -->
                <div class="activity-section">
                    <div class="section-header">
                        <h3 class="section-title">Inventory Items</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" id="dashboard-inventory-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Min Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Inventory data will be loaded here via AJAX -->
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

        // Real-time inventory chart and table update
        function fetchInventoryData() {
            fetch('/medical/api/inventory_dashboard.php')
                .then(res => res.json())
                .then(data => {
                    // Update chart
                    if(window.inventoryTrendChart) window.inventoryTrendChart.destroy();
                    const ctx = document.getElementById('inventory-trend-chart').getContext('2d');
                    window.inventoryTrendChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Total Items',
                                data: data.totals,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59,130,246,0.1)',
                                tension: 0.4
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                    // Update table
                    const tbody = document.querySelector('#dashboard-inventory-table tbody');
                    tbody.innerHTML = data.items.map(item => `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.category}</td>
                            <td>${item.quantity}</td>
                            <td>${item.min_quantity}</td>
                            <td>${item.status}</td>
                        </tr>
                    `).join('');
                    // Update Inventory Status
                    let total = 0, low = 0, out = 0;
                    let lowStockRows = [];
                    data.items.forEach(item => {
                        total++;
                        if(item.status === 'Out of Stock') out++;
                        else if(item.status === 'Low') low++;
                        if(item.status === 'Low') {
                            lowStockRows.push(`
                                <tr>
                                    <td>${item.name}</td>
                                    <td>${item.category}</td>
                                    <td>${item.quantity}</td>
                                    <td>${item.min_quantity}</td>
                                </tr>
                            `);
                        }
                    });
                    document.getElementById('inv-total-items').textContent = total;
                    document.getElementById('inv-low-stock').textContent = low;
                    document.getElementById('inv-out-stock').textContent = out;
                    document.querySelector('#low-stock-table tbody').innerHTML = lowStockRows.join('') || '<tr><td colspan="4" class="text-center">No low stock items</td></tr>';
                });
        }
        setInterval(fetchInventoryData, 5000); // Update every 5 seconds
        fetchInventoryData();
    </script>

  
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
