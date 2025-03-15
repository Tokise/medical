<?php include_once('layout/header.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Management System</title>
    <link rel="stylesheet" href="../styles/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> -->
</head>
<body>
    <main>
        <!-- Hero Section -->
        <section class="hero-section" style="padding: 10px 0;">
            <div class="hero-background">
                <div class="custom-container hero-content">
                    <div class="hero-text">
                        <h1 class="hero-title">Modern Healthcare Management for Schools</h1>
                        <p class="hero-subtitle">Streamline your school's medical operations with our comprehensive healthcare management system. Designed for efficiency, safety, and care.</p>
                        <div class="hero-buttons">
                            <a href="../../login.php" class="btn-primary">Get Started</a>
                          
                        </div>
                    </div>
                    <div class="hero-image-container">
                        <div class="blob-background">
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="blob-gradient" gradientTransform="rotate(45)">
                                        <stop offset="0%" stop-color="rgba(40, 39, 126, 0.9)" />
                                        <stop offset="100%" stop-color="rgba(59, 130, 246, 0.8)" />
                                    </linearGradient>
                                </defs>
                                <path fill="url(#blob-gradient)" d="M44.9,-76.7C57.5,-69.2,66.9,-55.5,74.1,-41.1C81.3,-26.7,86.3,-11.5,84.7,2.9C83.1,17.3,74.9,31,65.4,42.9C55.9,54.8,45.1,65,32.3,70.7C19.5,76.4,4.7,77.7,-10.2,75.8C-25.1,74,-40.1,69,-53.1,60.1C-66.1,51.2,-77.1,38.4,-83.4,23.3C-89.7,8.2,-91.3,-9.2,-86.6,-24.7C-81.9,-40.2,-70.9,-53.8,-57.3,-61.5C-43.7,-69.2,-27.5,-71,-11.8,-67.8C3.9,-64.6,32.3,-84.2,44.9,-76.7Z" transform="translate(100 100)" />
                            </svg>
                        </div>
                        <img src="../assets/hero.png" alt="Hero Image" class="hero-image">
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="custom-container">
                <h2 class="section-title">Our Features</h2>
                <div class="features-grid">
                    <div class="card-feature">
                        <i class="fa-solid fa-users feature-icon"></i>
                        <h3 class="feature-title">Patient Management</h3>
                        <p class="feature-description">Efficiently manage patient records and appointments</p>
                    </div>
                    <div class="card-feature">
                        <i class="fa-solid fa-file-medical feature-icon"></i>
                        <h3 class="feature-title">Medical Records</h3>
                        <p class="feature-description">Secure and organized medical history tracking</p>
                    </div>
                    <div class="card-feature">
                        <i class="fa-solid fa-robot feature-icon"></i>
                        <h3 class="feature-title">AI Integration</h3>
                        <p class="feature-description">Seemingless Autofill and AI functionality</p>
                    </div>
                    <div class="card-feature">
                        <i class="fa-solid fa-video feature-icon"></i>
                        <h3 class="feature-title">Telemedicine</h3>
                        <p class="feature-description">AI consultations with patients</p>
                    </div>
                    <div class="card-feature">
                        <i class="fa-solid fa-box-archive feature-icon"></i>
                        <h3 class="feature-title">Inventory Management</h3>
                        <p class="feature-description">Track and manage medical supplies</p>
                    </div>
                    <div class="card-feature">
                        <i class="fa-solid fa-chart-line feature-icon"></i>
                        <h3 class="feature-title">Reporting & Analytics</h3>
                        <p class="feature-description">Generate detailed reports and insights</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include_once('layout/footer.php'); ?>
    <script>
        // Enhanced Intersection Observer for scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                } else {
                    // Uncomment the next line if you want elements to animate again when scrolling up
                    // entry.target.classList.remove('visible');
                }
            });
        }, { 
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Observe all feature cards
        document.querySelectorAll('.card-feature').forEach((card) => {
            observer.observe(card);
        });
    </script>
</body>
</html>
