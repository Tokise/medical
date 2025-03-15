<header class="dashboard-header">
    <div class="header-left">
        <button class="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search...">
        </div>
    </div>
    <div class="header-right">
        <div class="notifications">
            <button class="notification-btn" onclick="toggleNotifications()">
            <i class="fa-regular fa-bell"></i>
                <span class="badge">3</span>
            </button>
            <div class="notification-dropdown">
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <button class="mark-all-read">Mark all as read</button>
                </div>
                <div class="notification-list">
                    <div class="notification-item unread">
                        <i class="fas fa-user-plus notification-icon"></i>
                        <div class="notification-content">
                            <p>New patient registration</p>
                            <span class="notification-time">5 minutes ago</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <i class="fas fa-calendar notification-icon"></i>
                        <div class="notification-content">
                            <p>Appointment rescheduled</p>
                            <span class="notification-time">1 hour ago</span>
                        </div>
                    </div>
                </div>
                <a href="#" class="view-all">View all notifications</a>
            </div>
        </div>
        <div class="user-menu">
            <button class="user-menu-btn" onclick="toggleUserMenu()">
                <img src="../assets/user-icon.png" alt="User Avatar" class="avatar">
            </button>
            <div class="user-dropdown">
                <div class="user-dropdown-header">
                    <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($role); ?></div>
                </div>
                <a href="../settings/profile.php">
                    <i class="fas fa-user-circle"></i> View Profile
                </a>
                <a href="../settings/account.php">
                    <i class="fas fa-cog"></i> Account Settings
                </a>
                <a href="../settings/preferences.php">
                    <i class="fas fa-sliders-h"></i> Preferences
                </a>
                <hr>
                <a href="../../auth/logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</header>

<script>
function toggleNotifications() {
    document.querySelector('.notification-dropdown').classList.toggle('active');
}

function toggleUserMenu() {
    const dropdown = document.querySelector('.user-dropdown');
    dropdown.classList.toggle('active');
    
    // Close notifications if open
    document.querySelector('.notification-dropdown').classList.remove('active');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.notifications') && !e.target.closest('.notification-dropdown')) {
        document.querySelector('.notification-dropdown').classList.remove('active');
    }
    if (!e.target.closest('.user-menu') && !e.target.closest('.user-dropdown')) {
        document.querySelector('.user-dropdown').classList.remove('active');
    }
});
</script>
