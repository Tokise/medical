<<<<<<< HEAD
<<<<<<< HEAD
<header class="dashboard-header">
    <div class="header-left">
        <div class="logo-wrapper">
            <img src="/medical/assets/img/logo.png" alt="Logo" class="logo">
            <span class="logo-text">MedMS</span>
        </div>
    </div>

    <div class="header-center">
        <div class="nav-container">
            <ul class="main-nav">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" title="Dashboard">
                    <a href="/medical/src/modules/dashboard/index.php">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                
                <?php
                // Get current user role from session
                $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
                
                // Admin-specific menu items
                if ($userRole === 'admin'): 
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'user') !== false ? 'active' : ''; ?>" title="User Management">
                        <a href="/medical/src/modules/dashboard/user/add.php">
                            <i class="fas fa-users"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>" title="Reports">
                        <a href="/medical/src/modules/dashboard/admin/reports.php">
                            <i class="fas fa-chart-bar"></i>
                        </a>
                    </li>
                  
                <?php
                // Doctor-specific menu items
                elseif ($userRole === 'doctor'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'appointment') !== false ? 'active' : ''; ?>" title="Appointments">
                        <a href="/medical/src/modules/appointment/appointment.php">
                            <i class="fas fa-calendar-check"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : ''; ?>" title="Consultations">
                        <a href="/medical/src/modules/consultation/list.php">
                            <i class="fas fa-stethoscope"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : ''; ?>" title="Prescriptions">
                        <a href="/medical/src/modules/prescription/create.php">
                            <i class="fas fa-prescription"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/chat/bot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
                
                <?php
                // Nurse-specific menu items
                elseif ($userRole === 'nurse'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'walkin') !== false ? 'active' : ''; ?>" title="Walk-ins">
                        <a href="/medical/src/modules/walkin/index.php">
                            <i class="fas fa-walking"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/chat/bot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
               
                <?php
                // Student user menus
                elseif ($userRole === 'student'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'appointment') !== false ? 'active' : ''; ?>" title="Appointments">
                        <a href="/medical/src/modules/dashboard/patient/student/appointment.php">
                            <i class="fas fa-calendar-check"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : ''; ?>" title="Prescriptions">
                        <a href="/medical/src/modules/dashboard/patient/student/prescription.php">
                            <i class="fas fa-prescription"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : ''; ?>" title="Consultations">
                        <a href="/medical/src/modules/dashboard/patient/student/consultation.php">
                            <i class="fas fa-stethoscope"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/dashboard/patient/student/chatbot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'history') !== false ? 'active' : ''; ?>" title="Medical History">
                        <a href="/medical/src/modules/dashboard/patient/student/history.php">
                            <i class="fas fa-history"></i>
                        </a>
                    </li>
                
                <?php
                // Teacher user menus 
                elseif ($userRole === 'teacher'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'appointment') !== false ? 'active' : ''; ?>" title="Appointments">
                        <a href="/medical/src/modules/dashboard/patient/teacher/appointment.php">
                            <i class="fas fa-calendar-check"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : ''; ?>" title="Prescriptions">
                        <a href="/medical/src/modules/dashboard/patient/teacher/prescription.php">
                            <i class="fas fa-prescription"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : ''; ?>" title="Consultations">
                        <a href="/medical/src/modules/dashboard/patient/teacher/consultation.php">
                            <i class="fas fa-stethoscope"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/dashboard/patient/teacher/chatbot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'history') !== false ? 'active' : ''; ?>" title="Medical History">
                        <a href="/medical/src/modules/dashboard/patient/teacher/history.php">
                            <i class="fas fa-history"></i>
                        </a>
                    </li>
                 
                <?php
                // Staff user menus
                elseif ($userRole === 'staff'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'appointment') !== false ? 'active' : ''; ?>" title="Appointments">
                        <a href="/medical/src/modules/dashboard/patient/staff/appointment.php">
                            <i class="fas fa-calendar-check"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : ''; ?>" title="Prescriptions">
                        <a href="/medical/src/modules/dashboard/patient/staff/prescription.php">
                            <i class="fas fa-prescription"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : ''; ?>" title="Consultations">
                        <a href="/medical/src/modules/dashboard/patient/staff/consultation.php">
                            <i class="fas fa-stethoscope"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/dashboard/patient/staff/chatbot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'history') !== false ? 'active' : ''; ?>" title="Medical History">
                        <a href="/medical/src/modules/dashboard/patient/staff/history.php">
                            <i class="fas fa-history"></i>
                        </a>
                    </li>
                   
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="header-right">
        <div class="search-wrapper">
            <div class="search-toggler">
                <i class="fas fa-search"></i>
            </div>
            <div class="search-input">
                <input type="text" placeholder="Search...">
                <button type="button" class="search-close"><i class="fas fa-times"></i></button>
            </div>
        </div>
    
        <div class="notifications">
            <button class="notification-btn" onclick="toggleNotifications()">
                <i class="far fa-bell"></i>
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
                <img src="/medical/assets/img/user-icon.png" alt="User Avatar" class="avatar">
            </button>
            <div class="user-dropdown">
                <div class="user-dropdown-header">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION["full_name"] ?? $_SESSION["username"] ?? 'User'); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars(ucfirst($_SESSION["role"] ?? 'User')); ?></div>
                </div>
                <a href="/medical/src/modules/settings/profile.php">
                    <i class="fas fa-user-circle"></i> View Profile
                </a>
                <a href="/medical/src/modules/settings/account.php">
                    <i class="fas fa-cog"></i> Account Settings
                </a>
                <a href="/medical/src/modules/settings/preferences.php">
                    <i class="fas fa-sliders-h"></i> Preferences
                </a>
                <hr>
                <a href="/medical/src/auth/logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</header>

<script>
function toggleNotifications() {
    document.querySelector('.notification-dropdown').classList.toggle('active');
    // Close user menu if open
    document.querySelector('.user-dropdown').classList.remove('active');
    // Close search if open
    document.querySelector('.search-input').classList.remove('active');
}

function toggleUserMenu() {
    document.querySelector('.user-dropdown').classList.toggle('active');
    // Close notifications if open
    document.querySelector('.notification-dropdown').classList.remove('active');
    // Close search if open
    document.querySelector('.search-input').classList.remove('active');
}

// Search functionality
document.querySelector('.search-toggler').addEventListener('click', function() {
    document.querySelector('.search-input').classList.add('active');
    document.querySelector('.search-input input').focus();
});

document.querySelector('.search-close').addEventListener('click', function() {
    document.querySelector('.search-input').classList.remove('active');
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.notifications') && !e.target.closest('.notification-dropdown')) {
        document.querySelector('.notification-dropdown').classList.remove('active');
    }
    if (!e.target.closest('.user-menu') && !e.target.closest('.user-dropdown')) {
        document.querySelector('.user-dropdown').classList.remove('active');
    }
    if (!e.target.closest('.search-wrapper')) {
        document.querySelector('.search-input').classList.remove('active');
    }
});
</script>
=======
=======
>>>>>>> 6555137 (Added my changes)
<header class="dashboard-header">
    <div class="header-left">
        <div class="logo-wrapper">
            <img src="/medical/assets/img/logo.png" alt="Logo" class="logo">
            <span class="logo-text">MedMS</span>
        </div>
    </div>

    <div class="header-center">
        <div class="nav-container">
            <ul class="main-nav">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" title="Dashboard">
                    <a href="/medical/src/modules/dashboard/index.php">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                
                <?php
                // Get current user role from session
                $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
                
                // Admin-specific menu items
                if ($userRole === 'admin'): 
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'user') !== false ? 'active' : ''; ?>" title="User Management">
                        <a href="/medical/src/modules/dashboard/user/add.php">
                            <i class="fas fa-users"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>" title="Reports">
                        <a href="/medical/src/modules/dashboard/admin/reports.php">
                            <i class="fas fa-chart-bar"></i>
                        </a>
                    </li>
                  
                <?php
                // Doctor-specific menu items
                elseif ($userRole === 'doctor'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'appointment') !== false ? 'active' : ''; ?>" title="Appointments">
                        <a href="/medical/src/modules/appointment/appointment.php">
                            <i class="fas fa-calendar-check"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : ''; ?>" title="Consultations">
                        <a href="/medical/src/modules/consultation/list.php">
                            <i class="fas fa-stethoscope"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : ''; ?>" title="Prescriptions">
                        <a href="/medical/src/modules/prescription/create.php">
                            <i class="fas fa-prescription"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/chat/bot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
                
                <?php
                // Nurse-specific menu items
                elseif ($userRole === 'nurse'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'walkin') !== false ? 'active' : ''; ?>" title="Walk-ins">
                        <a href="/medical/src/modules/walkin/index.php">
                            <i class="fas fa-walking"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/chat/bot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
               
                <?php
                // Student user menus
                elseif ($userRole === 'student'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'appointment') !== false ? 'active' : ''; ?>" title="Appointments">
                        <a href="/medical/src/modules/dashboard/patient/appointments/schedule.php">
                            <i class="fas fa-calendar-check"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : ''; ?>" title="Prescriptions">
                        <a href="/medical/src/modules/dashboard/patient/student/prescription.php">
                            <i class="fas fa-prescription"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : ''; ?>" title="Consultations">
                        <a href="/medical/src/modules/dashboard/patient/student/consultation.php">
                            <i class="fas fa-stethoscope"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/dashboard/patient/student/chatbot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'history') !== false ? 'active' : ''; ?>" title="Medical History">
                        <a href="/medical/src/modules/dashboard/patient/student/history.php">
                            <i class="fas fa-history"></i>
                        </a>
                    </li>
                
                <?php
                // Teacher user menus 
                elseif ($userRole === 'teacher'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'appointment') !== false ? 'active' : ''; ?>" title="Appointments">
                        <a href="/medical/src/modules/dashboard/patient/teacher/appointment.php">
                            <i class="fas fa-calendar-check"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : ''; ?>" title="Prescriptions">
                        <a href="/medical/src/modules/dashboard/patient/teacher/prescription.php">
                            <i class="fas fa-prescription"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : ''; ?>" title="Consultations">
                        <a href="/medical/src/modules/dashboard/patient/teacher/consultation.php">
                            <i class="fas fa-stethoscope"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/dashboard/patient/teacher/chatbot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'history') !== false ? 'active' : ''; ?>" title="Medical History">
                        <a href="/medical/src/modules/dashboard/patient/teacher/history.php">
                            <i class="fas fa-history"></i>
                        </a>
                    </li>
                 
                <?php
                // Staff user menus
                elseif ($userRole === 'staff'):
                ?>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'appointment') !== false ? 'active' : ''; ?>" title="Appointments">
                        <a href="/medical/src/modules/dashboard/patient/staff/appointment.php">
                            <i class="fas fa-calendar-check"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : ''; ?>" title="Prescriptions">
                        <a href="/medical/src/modules/dashboard/patient/staff/prescription.php">
                            <i class="fas fa-prescription"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : ''; ?>" title="Consultations">
                        <a href="/medical/src/modules/dashboard/patient/staff/consultation.php">
                            <i class="fas fa-stethoscope"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'chatbot') !== false ? 'active' : ''; ?>" title="AI Chatbot">
                        <a href="/medical/src/modules/dashboard/patient/staff/chatbot.php">
                            <i class="fas fa-robot"></i>
                        </a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'history') !== false ? 'active' : ''; ?>" title="Medical History">
                        <a href="/medical/src/modules/dashboard/patient/staff/history.php">
                            <i class="fas fa-history"></i>
                        </a>
                    </li>
                   
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="header-right">
        <div class="search-wrapper">
            <div class="search-toggler">
                <i class="fas fa-search"></i>
            </div>
            <div class="search-input">
                <input type="text" placeholder="Search...">
                <button type="button" class="search-close"><i class="fas fa-times"></i></button>
            </div>
        </div>
    
        <div class="notifications">
            <button class="notification-btn" onclick="toggleNotifications()">
                <i class="far fa-bell"></i>
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
                <img src="/medical/assets/img/user-icon.png" alt="User Avatar" class="avatar">
            </button>
            <div class="user-dropdown">
                <div class="user-dropdown-header">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION["full_name"] ?? $_SESSION["username"] ?? 'User'); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars(ucfirst($_SESSION["role"] ?? 'User')); ?></div>
                </div>
                <a href="/medical/src/modules/settings/profile.php">
                    <i class="fas fa-user-circle"></i> View Profile
                </a>
                <a href="/medical/src/modules/settings/account.php">
                    <i class="fas fa-cog"></i> Account Settings
                </a>
                <a href="/medical/src/modules/settings/preferences.php">
                    <i class="fas fa-sliders-h"></i> Preferences
                </a>
                <hr>
                <a href="/medical/src/auth/logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</header>

<script>
function toggleNotifications() {
    document.querySelector('.notification-dropdown').classList.toggle('active');
    // Close user menu if open
    document.querySelector('.user-dropdown').classList.remove('active');
    // Close search if open
    document.querySelector('.search-input').classList.remove('active');
}

function toggleUserMenu() {
    document.querySelector('.user-dropdown').classList.toggle('active');
    // Close notifications if open
    document.querySelector('.notification-dropdown').classList.remove('active');
    // Close search if open
    document.querySelector('.search-input').classList.remove('active');
}

// Search functionality
document.querySelector('.search-toggler').addEventListener('click', function() {
    document.querySelector('.search-input').classList.add('active');
    document.querySelector('.search-input input').focus();
});

document.querySelector('.search-close').addEventListener('click', function() {
    document.querySelector('.search-input').classList.remove('active');
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.notifications') && !e.target.closest('.notification-dropdown')) {
        document.querySelector('.notification-dropdown').classList.remove('active');
    }
    if (!e.target.closest('.user-menu') && !e.target.closest('.user-dropdown')) {
        document.querySelector('.user-dropdown').classList.remove('active');
    }
    if (!e.target.closest('.search-wrapper')) {
        document.querySelector('.search-input').classList.remove('active');
    }
});
</script>
<<<<<<< HEAD
>>>>>>> 6555137 (Added my changes)
=======
>>>>>>> 6555137 (Added my changes)
