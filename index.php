<?php
session_start();
require_once 'config/db.php';

?>

<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedMS - Medical Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/MedMS/styles/variables.css">
    <link rel="stylesheet" href="/MedMS/styles/global.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            primary: 'var(--primary-dark)',
                            secondary: 'var(--secondary-dark)',
                            accent: 'var(--accent-dark)',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="gradient-bg text-gray-100 font-sans min-h-screen">
    <!-- Navigation -->
    <nav class="fixed w-full z-50 bg-gray-950/95 backdrop-blur-sm border-b border-gray-800/50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <a href="index.php" class="flex items-center space-x-3">
                    <img src="../medical/assets/img/logo.png" alt="MedMS Logo" class="h-12 w-12">
                    <span class="text-xl font-bold bg-gradient-to-r from-indigo-400 to-indigo-600 bg-clip-text text-transparent">MedMS</span>
                </a>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#about" class="nav-link">About</a>
                    <a href="#blog" class="nav-link">Blog</a>
                    <div class="flex items-center space-x-4">
                        <a href="../medical/auth/login.php" class="btn-primary">Log In</a>
                        <a href="../medical/auth/signup.php" class="border border-indigo-600 text-indigo-400 px-6 py-2 rounded-lg hover:bg-indigo-600 hover:text-white transition-all">Sign Up</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="min-h-[80vh] flex items-center pt-28 pb-16 overflow-hidden relative">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950 via-gray-900/95 to-gray-950 pointer-events-none"></div>
        <div class="container mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div class="relative flex justify-center items-center px-4 lg:px-0 order-2 lg:order-1 mt-50">
                    <div class="absolute -top-20 -left-20 w-85-85  bg-indigo-600/20 rounded-full filter blur-3xl"></div>
                    <img src="../medical/assets/img/hero.png" alt="Healthcare Illustration" 
                         class="relative z-10 w-full max-w-xs mx-auto transform hover:scale-105 transition-transform duration-500 ">
                </div>
                <div class="space-y-6 text-left relative z-10 px-4 lg:px-0 order-1 lg:order-2">
                    <div class="absolute -top-40 -left-40 w-80 h-80 bg-indigo-600/30 rounded-full filter blur-3xl"></div>
                    <h1 class="text-4xl lg:text-5xl font-bold leading-tight text-white">
                        Medical Management
                        <span class="bg-gradient-to-r from-indigo-400 to-indigo-600 bg-clip-text text-transparent">System</span>
                        <br>for Schools
                    </h1>
                    <p class="text-lg text-gray-200 max-w-xl font-medium">
                        A comprehensive solution for managing health services, medical records, and prescriptions in educational institutions.
                    </p>
                    <div class="flex items-center space-x-6 pt-4">
                        <a href="../medical/auth/login.php" class="btn-primary">Get Started</a>
                        <a href="#features" class="group flex items-center space-x-2 text-indigo-300 hover:text-indigo-200 transition-colors font-medium">
                            <span>Learn more</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="relative py-20">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950/95 via-gray-900/90 to-gray-950/95"></div>
        <div class="container mx-auto relative z-10">
            <h2 class="text-3xl font-bold text-center mb-12">Key Features</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gray-900/50 backdrop-blur-sm p-6 rounded-lg shadow-lg text-center border border-gray-800/50">
                    <div class="bg-gray-700 p-4 rounded-full mx-auto mb-4">
                        <i class="fas fa-user-md text-3xl text-gray-300"></i>
                    </div>
                    <h4 class="text-xl font-semibold mb-2">Medical Staff Management</h4>
                    <p class="text-gray-400">Efficiently manage doctors and nurses, track their availability, and schedule appointments.</p>
                </div>
                <div class="bg-gray-900/50 backdrop-blur-sm p-6 rounded-lg shadow-lg text-center border border-gray-800/50">
                    <div class="bg-gray-700 p-4 rounded-full mx-auto mb-4">
                        <i class="fas fa-notes-medical text-3xl text-gray-300"></i>
                    </div>
                    <h4 class="text-xl font-semibold mb-2">Electronic Health Records</h4>
                    <p class="text-gray-400">Securely store and access student and staff medical records, including history and medications.</p>
                </div>
                <div class="bg-gray-900/50 backdrop-blur-sm p-6 rounded-lg shadow-lg text-center border border-gray-800/50">
                    <div class="bg-gray-700 p-4 rounded-full mx-auto mb-4">
                        <i class="fas fa-prescription text-3xl text-gray-300"></i>
                    </div>
                    <h4 class="text-xl font-semibold mb-2">Prescription Management</h4>
                    <p class="text-gray-400">Create, manage, and track prescriptions and medications for students and staff.</p>
                </div>
                <div class="bg-gray-900/50 backdrop-blur-sm p-6 rounded-lg shadow-lg text-center border border-gray-800/50">
                    <div class="bg-gray-700 p-4 rounded-full mx-auto mb-4">
                        <i class="fas fa-robot text-3xl text-gray-300"></i>
                    </div>
                    <h4 class="text-xl font-semibold mb-2">AI Integration</h4>
                    <p class="text-gray-400">Access AI-powered consultations for first aid advice and basic health information.</p>
                </div>
                <div class="bg-gray-900/50 backdrop-blur-sm p-6 rounded-lg shadow-lg text-center border border-gray-800/50">
                    <div class="bg-gray-700 p-4 rounded-full mx-auto mb-4">
                        <i class="fas fa-chart-bar text-3xl text-gray-300"></i>
                    </div>
                    <h4 class="text-xl font-semibold mb-2">Statistics & Reports</h4>
                    <p class="text-gray-400">Generate detailed reports and visualize medical data to improve health services.</p>
                </div>
                <div class="bg-gray-900/50 backdrop-blur-sm p-6 rounded-lg shadow-lg text-center border border-gray-800/50">
                    <div class="bg-gray-700 p-4 rounded-full mx-auto mb-4">
                        <i class="fas fa-lock text-3xl text-gray-300"></i>
                    </div>
                    <h4 class="text-xl font-semibold mb-2">Secure Access</h4>
                    <p class="text-gray-400">Role-based access control ensures that users can only access appropriate information.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="relative py-20">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950/90 via-gray-900/95 to-gray-950/90"></div>
        <div class="container mx-auto relative z-10">
            <h2 class="text-3xl font-bold text-center mb-12">About MedMS</h2>
            <div class="flex flex-col lg:flex-row items-center gap-12">
                <div class="lg:w-1/2">
                    <div class="relative rounded-xl overflow-hidden h-[400px] group">
                        <div class="absolute inset-0 bg-indigo-600/10 group-hover:bg-indigo-600/20 transition-colors duration-300"></div>
                        <img src="../medical/assets/img/logo.png" 
                             alt="About MedMS" 
                             class="w-full h-full object-cover rounded-xl object-center">
                    </div>
                </div>
                <div class="lg:w-1/2 space-y-6 relative z-10">
                    <h3 class="text-3xl font-bold text-white">Why Choose MedMS?</h3>
                    <p class="text-base md:text-lg text-white/90 leading-relaxed">
                        MedMS is designed specifically for educational institutions to streamline their medical management processes. Our system helps schools provide better healthcare services to students and staff.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-indigo-400 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-white/90">Easy-to-use interface for all user roles</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-indigo-400 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-white/90">Comprehensive medical record management</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-indigo-400 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-white/90">Advanced reporting and analytics capabilities</span>
                        </li>
                    </ul>
                    <div class="pt-6">
                        <a href="../medical/auth/signup.php" class="btn-primary inline-flex items-center space-x-2">
                            <span>Get Started</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section id="blog" class="relative py-20">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950/95 via-gray-900/95 to-gray-950/95"></div>
        <div class="container mx-auto relative z-10">
            <h2 class="text-3xl font-bold text-center mb-12">Latest Updates</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Blog Card 1 -->
                <div class="bg-gray-900/50 backdrop-blur-sm p-6 rounded-lg shadow-lg border border-gray-800/50 hover:border-indigo-600/50 transition-all group">
                    <div class="aspect-video rounded-lg mb-4 overflow-hidden">
                        <img src="../medical/assets/img/logo.png" 
                             alt="Digital Health Records" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-all duration-500">
                    </div>
                    <div class="space-y-3">
                        <span class="text-indigo-400 text-sm font-medium">Healthcare</span>
                        <h3 class="text-xl font-semibold text-white">Digital Health Records</h3>
                        <p class="text-gray-300">Learn how digital health records are revolutionizing school healthcare management...</p>
                        <a href="#" class="inline-flex items-center text-indigo-400 hover:text-indigo-300 font-medium group-hover:gap-2 transition-all">
                            Read More 
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Blog Card 2 -->
                <div class="bg-gray-900/50 backdrop-blur-sm p-6 rounded-lg shadow-lg border border-gray-800/50 hover:border-indigo-600/50 transition-all group">
                    <div class="aspect-video rounded-lg mb-4 overflow-hidden">
                        <img src="../medical/assets/img/logo.png" 
                             alt="AI in Healthcare" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-all duration-500">
                    </div>
                    <div class="space-y-3">
                        <span class="text-indigo-400 text-sm font-medium">Technology</span>
                        <h3 class="text-xl font-semibold text-white">AI Integration Benefits</h3>
                        <p class="text-gray-300">Explore how AI is transforming healthcare management in educational settings...</p>
                        <a href="#" class="inline-flex items-center text-indigo-400 hover:text-indigo-300 font-medium group-hover:gap-2 transition-all">
                            Read More 
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Blog Card 3 -->
                <div class="bg-gray-900/50 backdrop-blur-sm p-6 rounded-lg shadow-lg border border-gray-800/50 hover:border-indigo-600/50 transition-all group">
                    <div class="aspect-video rounded-lg mb-4 overflow-hidden">
                        <img src="../medical/assets/img/logo.png" 
                             alt="Medical Safety" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-all duration-500">
                    </div>
                    <div class="space-y-3">
                        <span class="text-indigo-400 text-sm font-medium">Safety</span>
                        <h3 class="text-xl font-semibold text-white">School Health Protocols</h3>
                        <p class="text-gray-300">Best practices for implementing health protocols in educational institutions...</p>
                        <a href="#" class="inline-flex items-center text-indigo-400 hover:text-indigo-300 font-medium group-hover:gap-2 transition-all">
                            Read More 
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="relative py-12">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-950/95 via-gray-900/90 to-gray-950/95"></div>
        <div class="container mx-auto px-4 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="space-y-4">
                    <h5 class="text-xl font-bold bg-gradient-to-r from-indigo-400 to-indigo-600 bg-clip-text text-transparent">MedMS</h5>
                    <p class="text-gray-400">Medical Management System for Schools</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-indigo-600 hover:text-white transition-all">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-indigo-600 hover:text-white transition-all">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-indigo-600 hover:text-white transition-all">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-indigo-600 hover:text-white transition-all">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                <div class="space-y-4">
                    <h5 class="text-xl font-bold text-gray-100">Quick Links</h5>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-indigo-400 transition-colors">Features</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-indigo-400 transition-colors">About</a></li>
                        <li><a href="#blog" class="text-gray-400 hover:text-indigo-400 transition-colors">Blog</a></li>
                        <li><a href="../medical/auth/login.php" class="text-gray-400 hover:text-indigo-400 transition-colors">Login</a></li>
                    </ul>
                </div>
                <div class="space-y-4">
                    <h5 class="text-xl font-bold text-gray-100">Legal</h5>
                    <ul class="space-y-2">
                        <li><a href="/privacy" class="text-gray-400 hover:text-indigo-400 transition-colors">Privacy Policy</a></li>
                        <li><a href="/terms" class="text-gray-400 hover:text-indigo-400 transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="space-y-4">
                    <h5 class="text-xl font-bold text-gray-100">Contact</h5>
                    <ul class="space-y-2">
                        <li class="flex items-center space-x-2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                            <span>support@medms.com</span>
                        </li>
                        <li class="flex items-center space-x-2 text-gray-400">
                            <i class="fas fa-phone"></i>
                            <span>+1 234 567 890</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> MedMS. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
