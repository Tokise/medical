<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/db.php';

// Check if user is logged in, if not and trying to access protected page, redirect to login
$currentPage = basename($_SERVER['PHP_SELF']);
$publicPages = ['login.php', 'signup.php', 'index.php'];

if (!isset($_SESSION['user_id']) && !in_array($currentPage, $publicPages) && $currentPage !== 'index.php') {
    header("Location: ../auth/login.php");
    exit;
}

// Get user data if logged in
$user = null;
$role = null;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userQuery = "SELECT u.*, r.role_name FROM users u 
                  JOIN roles r ON u.role_id = r.role_id 
                  WHERE u.user_id = ?";
    
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $role = $user['role_name'];
}

// Check if demo should be shown 
$showDemo = isset($user['demo_completed']) ? !$user['demo_completed'] : false;
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedMS - Medical Management System</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- IntroJS -->
    <link rel="stylesheet" href="https://unpkg.com/intro.js/minified/introjs.min.css">
    <script src="https://unpkg.com/intro.js/minified/intro.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/styles/variables.css">
    <link rel="stylesheet" href="/medical/styles/components.css">
    <?php
    // Add role-specific CSS if needed
    if (isset($role)) {
        echo '<link rel="stylesheet" href="/medical/src/modules/dashboard/' . strtolower($role) . '/styles/' . strtolower($role) . '.css">';
    }
    ?>
    <!-- Custom JavaScript -->
    <script src="/medical/src/js/main.js" defer></script>
</head>
<body class="dark-theme" data-user-role="<?php echo isset($role) ? htmlspecialchars(strtolower($role)) : ''; ?>">
    <header class="header">
        <div class="brand">
            <button id="mobile-menu-btn" class="nav-link d-md-none">
                <i class="fas fa-bars"></i>
            </button>
            <a class="logo" href="/medical/index.php">
                <img src="/medical/assets/img/logo.png" alt="MedMS Logo" class="brand-logo">
                <span class="brand-name">MedMS</span>
            </a>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <nav class="nav-menu">
                <!-- Theme Toggle -->
                <div class="nav-item">
                    <button id="theme-toggle" class="nav-link">
                        <i id="theme-icon" class="fas fa-sun"></i>
                    </button>
                </div>
                
                <!-- Notifications -->
                <div class="nav-item dropdown">
                    <button class="nav-link">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <h6>Notifications</h6>
                            <a href="#">Mark all as read</a>
                        </div>
                        <div class="dropdown-body">
                            <a class="dropdown-item" href="#"><i class="fas fa-info-circle"></i> New Message</a>
                            <a class="dropdown-item" href="#"><i class="fas fa-calendar"></i> Appointment Update</a>
                            <a class="dropdown-item" href="#"><i class="fas fa-bell"></i> System Alert</a>
                        </div>
                    </div>
                </div>
                
                <!-- Messages -->
                <div class="nav-item dropdown">
                    <button class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span class="badge">2</span>
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <h6>Messages</h6>
                            <a href="#">View all</a>
                        </div>
                        <div class="dropdown-body">
                            <a class="dropdown-item" href="#"><i class="fas fa-envelope"></i> New Message</a>
                            <a class="dropdown-item" href="#"><i class="fas fa-inbox"></i> Inbox</a>
                        </div>
                    </div>
                </div>
                
                <!-- User Profile -->
                <div class="nav-item dropdown">
                    <button class="profile-toggle">
                        <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '/medical/assets/img/user-icon.png' ?>" 
                             alt="Profile" class="profile-img">
                        <span class="profile-name"><?= htmlspecialchars($user['first_name']) ?></span>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="/medical/src/modules/profile/index.php">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a class="dropdown-item" href="/medical/src/modules/settings/index.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a class="dropdown-item" href="#" onclick="startTutorial()">
                            <i class="fas fa-desktop"></i> Try Demo
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="/medical/auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>
        <?php endif; ?>
    </header>

    <!-- Wrapper -->
    <div class="wrapper">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php include_once __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>
        
        <!-- Demo Modal -->
        <?php if ($showDemo): ?>
        <div id="demo-modal" class="modal">
            <div class="modal-content">
                <h2>Welcome to Medical Management System!</h2>
                <p>Would you like to take a quick tour of the system?</p>
                <div class="modal-actions">
                    <button id="start-demo" class="btn btn-primary">Start Tour</button>
                    <button id="skip-demo" class="btn btn-secondary">Skip for Now</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle dropdowns
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('button');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpen = menu.classList.contains('show');
                
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                    if (m !== menu) m.classList.remove('show');
                });
                
                // Toggle this dropdown
                menu.classList.toggle('show');
            });
        });
        
        // Close dropdowns when clicking elsewhere
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
        
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.querySelector('.sidebar');
        
        if (mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
    });
    
    function startTutorial() {
        // Initialize demo tour
        const userRole = document.body.getAttribute('data-user-role');
        // You can define different steps for different roles here
        const steps = getTutorialSteps(userRole);
        
        const tour = introJs();
        tour.setOptions({
            steps: steps,
            showProgress: true,
            showBullets: false,
            exitOnOverlayClick: false,
            exitOnEsc: false,
            doneLabel: 'Finish Tour'
        });
        
        tour.start().oncomplete(function() {
            // Update demo state when completed
            updateDemoState(true);
        });
    }
    
    function getTutorialSteps(role) {
        // Default steps
        const defaultSteps = [
            {
                element: '.sidebar',
                intro: 'This is your navigation menu where you can access different areas of the system.'
            },
            {
                element: '.header',
                intro: 'This header contains notifications, messages, and your profile settings.'
            }
        ];
        
        // Role-specific steps
        if (role === 'admin') {
            return [
                ...defaultSteps,
                {
                    element: '.admin-dashboard',
                    intro: 'This is your admin dashboard with key metrics and actions.'
                }
            ];
        } else if (role === 'doctor') {
            return [
                ...defaultSteps,
                {
                    element: '.doctor-dashboard',
                    intro: 'This is your doctor dashboard with patient information and appointments.'
                }
            ];
        }
        
        return defaultSteps;
    }
    
    function updateDemoState(completed) {
        fetch('/medical/api/update_demo_state.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                completed: completed
            })
        })
        .then(response => response.json())
        .catch(error => console.error('Error updating demo state:', error));
    }
    </script>
</body>
</html>
