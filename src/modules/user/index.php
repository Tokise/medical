<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get all users with their roles
$usersQuery = "SELECT u.*, r.role_name, 
               CASE 
                   WHEN r.role_name IN ('Doctor', 'Nurse') THEN ms.availability_status 
                   ELSE NULL 
               END as availability_status
               FROM users u 
               JOIN roles r ON u.role_id = r.role_id 
               LEFT JOIN medical_staff ms ON u.user_id = ms.user_id
               WHERE r.role_name != 'Admin'
               ORDER BY r.role_name, u.last_name, u.first_name";
$users = $conn->query($usersQuery)->fetch_all(MYSQLI_ASSOC);

// Pass the role to be used in the header
$role = 'Admin';

require_once '../../includes/header.php';
?>

<link rel="stylesheet" href="../user/styles/user.css">
<div class="user-management">
    <div class="section-header">
        <h2>User Management</h2>
        <div class="header-actions">
            <div class="filter">
                <div class="search-box">
                    <input type="text" id="userSearch" placeholder="Search users...">
                    <i class="fas fa-search"></i>
                </div>
                <select id="roleFilter">
                    <option value="">All Roles</option>
                    <option value="Doctor">Doctors</option>
                    <option value="Nurse">Nurses</option>
                    <option value="Teacher">Teachers</option>
                    <option value="Student">Students</option>
                </select>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['school_id']) ?></td>
                    <td>
                        <div class="user-info">
                            <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '/medical/assets/img/user-icon.png' ?>" 
                                 alt="Profile" class="user-avatar">
                            <div>
                                <div class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                <div class="user-email"><?= htmlspecialchars($user['username']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge <?= strtolower($user['role_name']) ?>"><?= $user['role_name'] ?></span></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php if (in_array($user['role_name'], ['Doctor', 'Nurse'])): ?>
                            <span class="status-badge <?= strtolower($user['availability_status'] ?? 'offline') ?>">
                                <?= $user['availability_status'] ?? 'Offline' ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y H:i', strtotime($user['updated_at'])) ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon view-user" data-userid="<?= $user['user_id'] ?>" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($user['role_name'] === 'Doctor' || $user['role_name'] === 'Nurse'): ?>
                            <button class="btn-icon view-schedule" data-userid="<?= $user['user_id'] ?>" title="View Schedule">
                                <i class="fas fa-calendar"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- User Details Modal -->
    <div class="modal" id="userDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">User Details</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="user-details">
                    <!-- Details will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSearch = document.getElementById('userSearch');
    const roleFilter = document.getElementById('roleFilter');
    const userTable = document.querySelector('table tbody');
    const modal = document.getElementById('userDetailsModal');
    const closeModal = modal.querySelector('.modal-close');

    // Search and filter functionality
    function filterTable() {
        const searchTerm = userSearch.value.toLowerCase();
        const roleValue = roleFilter.value.toLowerCase();
        const rows = userTable.getElementsByTagName('tr');

        for (let row of rows) {
            const name = row.querySelector('.user-name').textContent.toLowerCase();
            const email = row.querySelector('.user-email').textContent.toLowerCase();
            const role = row.querySelector('.role-badge').textContent.toLowerCase();
            const id = row.cells[0].textContent.toLowerCase();

            const matchesSearch = name.includes(searchTerm) || 
                                email.includes(searchTerm) || 
                                id.includes(searchTerm);
            const matchesRole = !roleValue || role === roleValue;

            row.style.display = matchesSearch && matchesRole ? '' : 'none';
        }
    }

    userSearch.addEventListener('input', filterTable);
    roleFilter.addEventListener('change', filterTable);

    // View user details
    document.querySelectorAll('.view-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userid;
            // Fetch and display user details
            modal.classList.add('active');
        });
    });

    // View schedule
    document.querySelectorAll('.view-schedule').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userid;
            // Redirect to schedule view or open schedule modal
            window.location.href = `/medical/src/modules/staff/index.php?user_id=${userId}`;
        });
    });

    // Close modal
    closeModal.addEventListener('click', () => {
        modal.classList.remove('active');
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>