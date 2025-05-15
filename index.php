<?php
// Initialize the session
session_start();

// Check if the user is already logged in, redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: src/modules/dashboard/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Portal - Medical Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="src/styles/variables.css">
    <link rel="stylesheet" href="src/styles/global.css">
    <style>
        /* Navbar */
        .navbar {
            background-color: var(--bg-white);
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: var(--z-index-fixed);
        }
        
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .navbar-logo img {
            height: 40px;
        }
        
        .navbar-logo-text {
            font-weight: var(--font-weight-semibold);
            font-size: var(--font-size-lg);
            color: var(--primary-color);
        }
        
        .navbar-menu {
            display: flex;
            gap: 1.5rem;
        }
        
        .navbar-menu a {
            color: var(--text-secondary);
            font-weight: var(--font-weight-medium);
        }
        
        .navbar-menu a:hover {
            color: var(--primary-color);
        }
        
        .navbar-cta {
            display: flex;
            gap: 1rem;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: var(--font-size-lg);
            color: var(--text-secondary);
        }
        
        /* Hero Section */
        .hero-section {
            padding: 8rem 0 5rem;
            position: relative;
            overflow: hidden;
            background-color: var(--bg-white);
        }
        
        .hero-container {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .hero-content {
            flex: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: var(--font-weight-bold);
            line-height: var(--line-height-tight);
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .hero-title span {
            color: var(--primary-color-dark);
        }
        
        .hero-subtitle {
            font-size: var(--font-size-lg);
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .hero-cta {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .hero-image {
            flex: 0.8;
            display: flex;
            justify-content: center;
          
        }
        
        .hero-image img {
            max-width: 55%;
            border-radius: var(--border-radius-lg);
            transform: scaleX(-1);
            margin-right : 150px auto;
        }
        
        /* Features */
        .features-section {
            padding: 5rem 0;
            background-color: var(--bg-light);
        }
        
        .features-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .features-title h2 {
            font-size: var(--font-size-xl);
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .features-title p {
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background-color: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            text-align: center;
            transition: transform var(--transition-normal), box-shadow var(--transition-normal);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: var(--font-size-xl);
        }
        
        .feature-title {
            font-size: var(--font-size-lg);
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .feature-description {
            color: var(--text-secondary);
        }
        
        /* Footer */
        .footer {
            background-color: var(--primary-color);
            color: var(--text-white);
            padding: 4rem 0 2rem;
        }
        
        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .footer-logo img {
            height: 40px;
        }
        
        .footer-logo-text {
            font-weight: var(--font-weight-semibold);
            font-size: var(--font-size-lg);
            color: var(--text-white);
        }
        
        .footer-description {
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }
        
        .footer-title {
            font-size: var(--font-size-md);
            margin-bottom: 1.5rem;
            font-weight: var(--font-weight-semibold);
        }
        
        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .footer-links a {
            color: var(--text-white);
            opacity: 0.8;
            transition: opacity var(--transition-normal);
        }
        
        .footer-links a:hover {
            opacity: 1;
        }
        
        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .footer-social a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-white);
            transition: background-color var(--transition-normal);
        }
        
        .footer-social a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .footer-bottom {
            margin-top: 3rem;
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-bottom p {
            opacity: 0.8;
            font-size: var(--font-size-sm);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .hero-container {
                flex-direction: column;
            }
            
            .hero-content, .hero-image {
                flex: none;
                width: 100%;
            }
            
            .hero-image {
                justify-content: center;
                margin-top: 2rem;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-menu, .navbar-cta {
                display: none;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .menu-active .navbar-menu, .menu-active .navbar-cta {
                display: flex;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background-color: var(--bg-white);
                padding: 1rem;
                box-shadow: var(--shadow-md);
            }
            
            .hero-cta {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container navbar-container">
            <a href="index.php" class="navbar-logo">
                <img src="assets/img/logo.png" alt="Healthcare Logo">
                <span class="navbar-logo-text">MedMS</span>
            </a>
            
            <button class="menu-toggle" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="navbar-menu">
                <a href="#features">Features</a>
                <a href="#about">About Us</a>
                <a href="#services">Services</a>
                <a href="#contact">Contact</a>
            </div>
            
            <div class="navbar-cta">
                <a href="src/auth/login.php" class="btn btn-outline">Login</a>
             
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Modern Healthcare <span>Management</span> System</h1>
                <p class="hero-subtitle">Streamline your medical facility operations with our comprehensive healthcare management platform. Designed for hospitals, clinics, and educational institutions.</p>
                <div class="hero-cta">
                    <a href="src/auth/login.php" class="btn btn-primary">Get Started</a>
                    <a href="#features" class="btn btn-outline">Learn More</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="assets/img/hero.png" alt="Healthcare Management System">
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="features-title">
                <h2>Powerful Features for Healthcare Management</h2>
                <p>Our healthcare management system offers a comprehensive suite of tools to streamline your medical facility operations.</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="feature-title">Appointment Scheduling</h3>
                    <p class="feature-description">Efficiently manage appointments and reduce wait times with our intuitive scheduling system.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <h3 class="feature-title">Patient Records</h3>
                    <p class="feature-description">Keep detailed patient records securely stored and easily accessible when needed.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <h3 class="feature-title">Prescription Management</h3>
                    <p class="feature-description">Create, manage, and track prescriptions to ensure patients receive proper medication.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Health Analytics</h3>
                    <p class="feature-description">Access insightful analytics and reports to improve patient care and facility operations.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3 class="feature-title">Staff Management</h3>
                    <p class="feature-description">Track staff schedules, assignments, and performance to optimize resource allocation.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3 class="feature-title">Notifications & Alerts</h3>
                    <p class="feature-description">Receive timely alerts for important events and patient care reminders.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-container">
            <div>
                <div class="footer-logo">
                    <img src="assets/img/logo.png" alt="Healthcare Logo">
                    <span class="footer-logo-text">MedMS</span>
                </div>
                <p class="footer-description">Modern healthcare management system designed for medical facilities and educational institutions.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            
            <div>
                <h3 class="footer-title">Quick Links</h3>
                <div class="footer-links">
                    <a href="#features">Features</a>
                    <a href="#about">About Us</a>
                    <a href="#services">Services</a>
                    <a href="#contact">Contact</a>
                </div>
            </div>
            
            <div>
                <h3 class="footer-title">Resources</h3>
                <div class="footer-links">
                    <a href="#">Help Center</a>
                    <a href="#">Documentation</a>
                    <a href="#">API Reference</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
            
            <div>
                <h3 class="footer-title">Contact Us</h3>
                <div class="footer-links">
                    <a href="#"><i class="fas fa-map-marker-alt"></i> 123 Medical Avenue, City</a>
                    <a href="#"><i class="fas fa-phone"></i> +1 (555) 123-4567</a>
                    <a href="#"><i class="fas fa-envelope"></i> info@healthcare.com</a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date("Y"); ?> Healthcare Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.querySelector('.navbar-container').classList.toggle('menu-active');
        });
    </script>
</body>
</html>

