<nav class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-wrapper">
            <img src="../assets/logo.png" alt="Logo" class="logo">
            <span class="sidebar-logo-text">Healthcare</span>
        </div>
    </div>

    <div class="sidebar-content">
        <div class="menu-section">
            <span class="menu-title">MAIN MENU</span>
            <ul class="menu">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <?php if (in_array('manage_users', $permissions)): ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'users.php') !== false ? 'active' : ''; ?>">
                        <a href="../admin/users.php"><i class="fas fa-users"></i> Users</a>
                    </li>
                <?php endif; ?>
                <?php if (in_array('manage_appointments', $permissions)): ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'appointments') !== false ? 'active' : ''; ?>">
                        <a href="appointments/"><i class="fas fa-calendar-check"></i> Appointments</a>
                    </li>
                <?php endif; ?>
                <?php if (in_array('view_records', $permissions)): ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'records') !== false ? 'active' : ''; ?>">
                        <a href="records/"><i class="fas fa-file-medical"></i> Medical Records</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>


        </ul>
    </div>
    </div>

</nav>