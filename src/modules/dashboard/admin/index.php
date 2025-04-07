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

// Get all roles for the user creation form
$rolesQuery = "SELECT * FROM roles WHERE role_name IN ('Doctor', 'Nurse', 'Teacher', 'Student')";
$roles = $conn->query($rolesQuery)->fetch_all(MYSQLI_ASSOC);

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
        <div class="action-card" id="addUserBtn">
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
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?= htmlspecialchars($log['action']) ?> by <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></div>
                            <div class="activity-time"><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">No recent activity</div>
                        <div class="activity-time">System is ready for use</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Add New User</h2>
            <button class="modal-close" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addUserForm" action="../../controllers/user_controller.php" method="POST">
                <input type="hidden" name="action" value="create_user">
                
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="school_id">School ID</label>
                    <input type="text" id="school_id" name="school_id" required placeholder="Enter student/teacher ID">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select id="role_id" name="role_id" required>
                        <option value="">Select a role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['role_id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('addUserModal');
        const addUserBtn = document.getElementById('addUserBtn');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        
        // Open modal
        addUserBtn.addEventListener('click', function() {
            modal.classList.add('active');
        });
        
        // Close modal
        function closeModalFunc() {
            modal.classList.remove('active');
        }
        
        closeModal.addEventListener('click', closeModalFunc);
        cancelBtn.addEventListener('click', closeModalFunc);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModalFunc();
            }
        });
        
        // Form submission
        const addUserForm = document.getElementById('addUserForm');
        addUserForm.addEventListener('submit', function(event) {
            // Form validation can be added here
            // If validation passes, the form will submit to the controller
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>
