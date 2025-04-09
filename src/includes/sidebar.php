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

// Function to check if current URL matches the given path
function isActiveLink($path) {
    $currentPath = $_SERVER['PHP_SELF'];
    return strpos($currentPath, $path) !== false;
}

// Function to get current section
function getCurrentSection() {
    $currentPath = $_SERVER['PHP_SELF'];
    if (strpos($currentPath, '/dashboard/') !== false) return 'dashboard';
    if (strpos($currentPath, '/user/') !== false) return 'user';
    if (strpos($currentPath, '/staff/') !== false) return 'staff';
    if (strpos($currentPath, '/reports/') !== false) return 'reports';
    if (strpos($currentPath, '/appointment/') !== false) return 'appointment';
    if (strpos($currentPath, '/search/') !== false) return 'search';
    if (strpos($currentPath, '/prescription/') !== false) return 'prescription';
    if (strpos($currentPath, '/consultation/') !== false) return 'consultation';
    if (strpos($currentPath, '/vitals/') !== false) return 'vitals';
    if (strpos($currentPath, '/walkin/') !== false) return 'walkin';
    if (strpos($currentPath, '/history/') !== false) return 'history';
    if (strpos($currentPath, '/medication/') !== false) return 'medication';
    return '';
}

$currentSection = getCurrentSection();
?>

<aside class="sidebar <?= $sidebarClass ?>">
    <nav class="sidebar-nav">
        <?php if ($role === 'Admin'): ?>
            <!-- Admin Menu -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'dashboard' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/admin/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'user' ? 'active' : '' ?>" 
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
                        <a class="nav-link <?= $currentSection === 'staff' ? 'active' : '' ?>" 
                           href="/medical/src/modules/staff/index.php">
                            <i class="fas fa-user-md"></i>
                            <span>Staff Availability</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'reports' ? 'active' : '' ?>" 
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
                        <a class="nav-link <?= $currentSection === 'dashboard' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/doctor/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'appointment' ? 'active' : '' ?>" 
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
                        <a class="nav-link <?= $currentSection === 'search' ? 'active' : '' ?>" 
                           href="/medical/src/modules/search/index.php">
                            <i class="fas fa-search"></i>
                            <span>Patient Search</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'prescription' ? 'active' : '' ?>" 
                           href="/medical/src/modules/prescription/index.php">
                            <i class="fas fa-prescription"></i>
                            <span>Prescriptions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'consultation' ? 'active' : '' ?>" 
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
                        <a class="nav-link <?= $currentSection === 'dashboard' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/nurse/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'vitals' ? 'active' : '' ?>" 
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
                        <a class="nav-link <?= $currentSection === 'search' ? 'active' : '' ?>" 
                           href="/medical/src/modules/search/index.php">
                            <i class="fas fa-search"></i>
                            <span>Patient Search</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'walkin' ? 'active' : '' ?>" 
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
                        <a class="nav-link <?= $currentSection === 'dashboard' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/teacher/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'history' ? 'active' : '' ?>" 
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
                        <a class="nav-link <?= $currentSection === 'medication' ? 'active' : '' ?>" 
                           href="/medical/src/modules/medication/index.php">
                            <i class="fas fa-pills"></i>
                            <span>Medications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'prescription' ? 'active' : '' ?>" 
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
                        <a class="nav-link <?= $currentSection === 'dashboard' ? 'active' : '' ?>" 
                           href="/medical/src/modules/dashboard/student/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'history' ? 'active' : '' ?>" 
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
                        <a class="nav-link <?= $currentSection === 'medication' ? 'active' : '' ?>" 
                           href="/medical/src/modules/medication/index.php">
                            <i class="fas fa-pills"></i>
                            <span>Medications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentSection === 'prescription' ? 'active' : '' ?>" 
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


