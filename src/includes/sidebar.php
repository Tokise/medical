<?php
// Determine which sidebar class to use based on user role
$sidebarClass = '';
if (isset($role)) {
    switch ($role) {
        case 'Admin':
            $sidebarClass = 'admin-sidebar';
            break;
        case 'Doctor':
            $sidebarClass = 'doctor-sidebar';
            break;
        case 'Nurse':
            $sidebarClass = 'nurse-sidebar';
            break;
        case 'Teacher':
            $sidebarClass = 'teacher-sidebar';
            break;
        case 'Student':
            $sidebarClass = 'student-sidebar';
            break;
        default:
            $sidebarClass = '';
    }
}
?>

<aside class="sidebar <?= $sidebarClass ?>">
    <nav class="sidebar-nav">
        <?php if ($role === 'Admin'): ?>
            <!-- Admin Menu -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/admin/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/user/index.php">
                            <i class="fas fa-users"></i>
                            <span>User Management</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Management</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'staff') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/staff/index.php">
                            <i class="fas fa-user-md"></i>
                            <span>Staff Availability</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/reports/index.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports & Statistics</span>
                        </a>
                    </li>
                </ul>
            </div>
            
        <?php elseif ($role === 'Doctor'): ?>
            <!-- Doctor Menu -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/doctor/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'appointment') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/appointment/appointment.php">
                            <i class="fas fa-calendar-check"></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Patient Care</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'search') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/search/index.php">
                            <i class="fas fa-search"></i>
                            <span>Patient Search</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/prescription/index.php">
                            <i class="fas fa-prescription"></i>
                            <span>Prescriptions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'consultation') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/consultation/index.php">
                            <i class="fas fa-notes-medical"></i>
                            <span>Consultations</span>
                        </a>
                    </li>
                </ul>
            </div>
            
        <?php elseif ($role === 'Nurse'): ?>
            <!-- Nurse Menu -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/nurse/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'vitals') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/vitals/index.php">
                            <i class="fas fa-heartbeat"></i>
                            <span>Vital Signs</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Patient Care</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'search') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/search/index.php">
                            <i class="fas fa-search"></i>
                            <span>Patient Search</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'walkin') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/walkin/index.php">
                            <i class="fas fa-walking"></i>
                            <span>Walk-ins</span>
                        </a>
                    </li>
                </ul>
            </div>
            
        <?php elseif ($role === 'Teacher'): ?>
            <!-- Teacher Menu -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/teacher/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'history') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/history/index.php">
                            <i class="fas fa-history"></i>
                            <span>Medical History</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Health</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'medication') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/medication/index.php">
                            <i class="fas fa-pills"></i>
                            <span>Medications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/prescription/index.php">
                            <i class="fas fa-prescription"></i>
                            <span>Prescriptions</span>
                        </a>
                    </li>
                </ul>
            </div>
            
        <?php elseif ($role === 'Student'): ?>
            <!-- Student Menu -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/student/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'history') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/history/index.php">
                            <i class="fas fa-history"></i>
                            <span>Medical History</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Health</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'medication') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/medication/index.php">
                            <i class="fas fa-pills"></i>
                            <span>Medications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'prescription') !== false ? 'active' : '' ?>" 
                           href="/medical/src/modules/prescription/index.php">
                            <i class="fas fa-prescription"></i>
                            <span>Prescriptions</span>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </nav>
</aside>

<!-- Demo Tutorial Modal moved to header.php for automatic display after login -->


