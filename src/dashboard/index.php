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
<html>

<head>
    <title>Dashboard - Medical Management</title>
    <link rel="stylesheet" href="../../src/styles/tutorial.css">
    <link rel="stylesheet" href="../../src/styles/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>
    <?php include_once('../includes/header.php'); ?>
    <!--section for redability-->
    <section data-user-role="<?php echo htmlspecialchars($role); ?>">
        <div class="content">
            <!-- Stats Cards Section -->
            <div class="stats-container">
                <?php if (in_array('manage_appointments', $permissions)): ?>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Today's Appointments</h3>
                                <div class="number">
                                    <?php
                                    $today = date('Y-m-d');
                                    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = '$today'");
                                    echo $result->fetch_assoc()['count'];
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Patients</h3>
                            <div class="number">
                                <?php
                                $result = $conn->query("SELECT COUNT(*) as count FROM patients");
                                echo $result->fetch_assoc()['count'];
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Revenue</h3>
                            <div class="number">$5,240</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="quick-actions-container">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="action-buttons">
                    <a href="../appointments/create.php" class="action-btn primary">
                        <span>New Appointment</span>
                    </a>
                    
                    <a href="../patients/create.php" class="action-btn success">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Patient</span>
                    </a>
                    
                    <a href="../prescriptions/create.php" class="action-btn info">
                        <i class="fas fa-file-medical"></i>
                        <span>New Prescription</span>
                    </a>
                    
                    <a href="../reports/create.php" class="action-btn warning">
                        <i class="fas fa-chart-bar"></i>
                        <span>Generate Report</span>
                    </a>
                    
                    <a href="../inventory/add.php" class="action-btn primary">
                        <i class="fas fa-boxes"></i>
                        <span>Add Inventory</span>
                    </a>
                    
                    <a href="../schedule/manage.php" class="action-btn info">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Manage Schedule</span>
                    </a>
                    
                </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="dashboard-main-content">
                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="chart-card">
                        <h2><i class="fas fa-chart-line"></i> Appointments Overview</h2>
                        <canvas id="appointmentsChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h2><i class="fas fa-chart-bar"></i> Revenue Analysis</h2>
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div class="activity-section">
                    <div class="activity-card">
                        <h2><i class="fas fa-history"></i> Recent Activity</h2>
                        <div class="activity-list">
                            <?php
                            $sql = "SELECT 'appointment' as type, 
                                    a.created_at as date,
                                    CONCAT('New appointment for ', p.name) as description,
                                    u.first_name as user
                                FROM appointments a 
                                JOIN patients p ON a.patient_id = p.id
                                JOIN users u ON a.created_by = u.id
                                ORDER BY date DESC LIMIT 5";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p><?php echo htmlspecialchars($row['description']); ?></p>
                                        <span class="activity-time">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('M d, H:i', strtotime($row['date'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Initialize Charts Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartOptions = {
                type: 'line',
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            display: false
                        }
                    },
                    elements: {
                        point: {
                            radius: 0
                        },
                        line: {
                            tension: 0.4
                        }
                    },
                    maintainAspectRatio: false
                }
            };

            // Initialize stat card charts
            const chartData = {
                appointments: [30, 40, 35, 45, 40, 50, 45],
                patients: [120, 130, 125, 135, 140, 135, 145],
                prescriptions: [25, 30, 28, 32, 30, 35, 33],
                revenue: [4000, 4200, 4100, 4300, 4200, 4400, 4500]
            };

            const colors = {
                appointments: '#2563eb',
                patients: '#16a34a',
                prescriptions: '#dc2626',
                revenue: '#8b5cf6'
            };

            Object.keys(chartData).forEach(key => {
                const canvas = document.getElementById(`${key}Chart`);
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    new Chart(ctx, {
                        ...chartOptions,
                        data: {
                            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                            datasets: [{
                                data: chartData[key],
                                borderColor: colors[key],
                                backgroundColor: `${colors[key]}15`,
                                fill: true,
                                borderWidth: 2
                            }]
                        }
                    });
                }
            });
        });
    </script>
    <script>
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
    <script src="../../js/tutorial.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const userRole = document.querySelector('.dashboard-container').dataset.userRole;
            const pendingTutorials = <?php echo $pending_tutorials; ?>;

            if (pendingTutorials > 0) {
                const tutorial = new TutorialSystem(userRole);
                tutorial.init();
            }
        });

        // Initialize appointment chart
        const appointmentCtx = document.getElementById('appointmentsChart');
        if (appointmentCtx) {
            new Chart(appointmentCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Appointments',
                        data: [12, 19, 3, 5, 2, 3, 7],
                        borderColor: '#2563eb',
                        tension: 0.4
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            display: false
                        },
                        x: {
                            display: false
                        }
                    },
                    maintainAspectRatio: false
                }
            });
        }

        // Initialize all charts
        const charts = {
            appointments: {
                id: 'appointmentsChart',
                data: [12, 19, 3, 5, 2, 3, 7]
            },
            patients: {
                id: 'patientsChart',
                data: [5, 8, 12, 15, 20, 25, 30]
            },
            prescriptions: {
                id: 'prescriptionsChart',
                data: [10, 15, 8, 12, 17, 14, 11]
            },
            revenue: {
                id: 'revenueChart',
                data: [1200, 1900, 1300, 1500, 2000, 1800, 2200]
            }
        };

        Object.entries(charts).forEach(([key, chart]) => {
            const ctx = document.getElementById(chart.id);
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            data: chart.data,
                            borderColor: '#2563eb',
                            tension: 0.4,
                            fill: true,
                            backgroundColor: 'rgba(37, 99, 235, 0.1)'
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                display: false
                            },
                            x: {
                                display: false
                            }
                        },
                        maintainAspectRatio: false
                    }
                });
            }
        });

        // Analytics Overview Chart
        const analyticsCtx = document.getElementById('analyticsChart');
        if (analyticsCtx) {
            new Chart(analyticsCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Appointments',
                        data: [65, 59, 80, 81, 56, 55],
                        backgroundColor: '#2563eb'
                    }, {
                        label: 'Revenue',
                        data: [28, 48, 40, 19, 86, 27],
                        backgroundColor: '#22c55e'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart configuration
            const chartConfig = {
                appointments: {
                    data: [30, 45, 35, 50, 40, 35, 45],
                    color: '#2563eb'
                },
                patients: {
                    data: [120, 145, 150, 165, 170, 160, 180],
                    color: '#16a34a'
                },
                records: {
                    data: [80, 90, 85, 95, 100, 95, 105],
                    color: '#ea580c'
                },
                monthly: {
                    data: [200, 250, 300, 280, 290, 310, 320],
                    color: '#8b5cf6'
                }
            };

            // Function to create charts
            function createChart(elementId, data, color) {
                const ctx = document.getElementById(elementId);
                if (!ctx) return;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            data: data,
                            borderColor: color,
                            tension: 0.4,
                            fill: true,
                            backgroundColor: `${color}15`,
                            borderWidth: 2,
                            pointRadius: 0
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                display: false
                            },
                            y: {
                                display: false
                            }
                        },
                        responsive: true,
                        maintainAspectRatio: false,
                        elements: {
                            line: {
                                smooth: true
                            }
                        }
                    }
                });
            }

            // Initialize all charts
            createChart('appointmentsChart', chartConfig.appointments.data, chartConfig.appointments.color);
            createChart('patientsChart', chartConfig.patients.data, chartConfig.patients.color);
            createChart('recordsChart', chartConfig.records.data, chartConfig.records.color);
            createChart('monthlyChart', chartConfig.monthly.data, chartConfig.monthly.color);
        });
    </script>
</body>

</html>