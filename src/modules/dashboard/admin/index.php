<?php
session_start();
require_once '../../../../config/db.php';
require_once '../../../includes/header.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Get current user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get counts for dashboard stats
$totalUsersQuery = "SELECT COUNT(*) as count FROM users";
$totalUsers = $conn->query($totalUsersQuery)->fetch_assoc()['count'];

$totalDoctorsQuery = "SELECT COUNT(*) as count FROM users u 
                     JOIN roles r ON u.role_id = r.role_id 
                     WHERE r.role_name = 'Doctor'";
$totalDoctors = $conn->query($totalDoctorsQuery)->fetch_assoc()['count'];

$totalNursesQuery = "SELECT COUNT(*) as count FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE r.role_name = 'Nurse'";
$totalNurses = $conn->query($totalNursesQuery)->fetch_assoc()['count'];

$totalTeachersQuery = "SELECT COUNT(*) as count FROM users u 
                      JOIN roles r ON u.role_id = r.role_id 
                      WHERE r.role_name = 'Teacher'";
$totalTeachers = $conn->query($totalTeachersQuery)->fetch_assoc()['count'];

$totalStudentsQuery = "SELECT COUNT(*) as count FROM users u 
                      JOIN roles r ON u.role_id = r.role_id 
                      WHERE r.role_name = 'Student'";
$totalStudents = $conn->query($totalStudentsQuery)->fetch_assoc()['count'];

// Get recent system logs
$logsQuery = "SELECT sl.*, u.username, u.first_name, u.last_name 
             FROM system_logs sl
             JOIN users u ON sl.user_id = u.user_id
             ORDER BY sl.created_at DESC LIMIT 10";
$logs = $conn->query($logsQuery)->fetch_all(MYSQLI_ASSOC);

// Pass the role to be used in the sidebar
$role = 'Admin';
?>

<div class="admin-dashboard">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <!-- Total Users Card -->
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Users</span>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>12% from last month</span>
            </div>
        </div>

        <!-- Active Staff Card -->
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Active Staff</span>
                <div class="stat-icon">
                    <i class="fas fa-user-md"></i>
                </div>
            </div>
            <div class="stat-value"><?= $totalDoctors ?></div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>4 new this week</span>
            </div>
        </div>

        <!-- Total Appointments Card -->
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Appointments</span>
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
            <div class="stat-value">1,234</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>23% this week</span>
            </div>
        </div>

        <!-- System Health Card -->
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">System Health</span>
                <div class="stat-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
            </div>
            <div class="stat-value">98%</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>2% improvement</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="quick-actions">
        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h3 class="action-title">Add User</h3>
            <p class="action-description">Create new user accounts</p>
        </div>

        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <h3 class="action-title">Schedule</h3>
            <p class="action-description">Manage staff schedules</p>
        </div>

        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="action-title">Reports</h3>
            <p class="action-description">View analytics & reports</p>
        </div>

        <div class="action-card">
            <div class="action-icon">
                <i class="fas fa-cog"></i>
            </div>
            <h3 class="action-title">Settings</h3>
            <p class="action-description">System configuration</p>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="activity-section">
        <div class="section-header">
            <h2 class="section-title">Recent Activity</h2>
        </div>
        <div class="activity-list">
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">New user registered</div>
                    <div class="activity-time">2 minutes ago</div>
                </div>
            </div>

            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">Appointment scheduled</div>
                    <div class="activity-time">15 minutes ago</div>
                </div>
            </div>

            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-file-medical"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">Medical record updated</div>
                    <div class="activity-time">1 hour ago</div>
                </div>
            </div>

            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">New staff member added</div>
                    <div class="activity-time">2 hours ago</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
