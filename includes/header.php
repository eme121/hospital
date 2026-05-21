<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hope Haven Hospital | Premium Healthcare</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- PWA -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/logo.png">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo BASE_URL; ?>/service-worker.js')
                    .then(reg => console.log('Service Worker registered!', reg))
                    .catch(err => console.log('Service Worker registration failed: ', err));
            });
        }
    </script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; scroll-behavior: smooth; }
        .glass-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        .hero-gradient {
            background: radial-gradient(circle at 70% 30%, #eff6ff 0%, #ffffff 60%, #f8fafc 100%);
        }
        .btn-premium {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -10px rgba(37, 99, 235, 0.4);
        }
        .service-card {
            transition: all 0.5s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        .service-card:hover {
            transform: translateY(-12px);
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
        }
        .clinic-img {
            transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .clinic-img:hover {
            transform: scale(1.08);
        }
        .faq-button:after {
            content: '+';
            font-size: 1.5rem;
            color: #3b82f6;
            transition: transform 0.3s;
        }
        .faq-button.active:after {
            content: '-';
            transform: rotate(180deg);
        }
        .active-nav {
            color: #2563eb !important;
            border-bottom: 2px solid #2563eb;
        }
    </style>
</head>
<body class="bg-white text-slate-900">

    <!-- Top Bar -->
    <div class="bg-blue-600 text-white py-2 hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-[13px] font-medium uppercase tracking-wider">
            <div class="flex items-center space-x-6">
                <span class="flex items-center"><svg class="w-3.5 h-3.5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path></svg> +234 123 456 7890</span>
                <span class="flex items-center"><svg class="w-3.5 h-3.5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg> info@hopehaven.ng</span>
            </div>
            <div class="flex items-center space-x-6">
                <?php if(isset($_SESSION['patient_id'])): ?>
                    <a href="<?php echo BASE_URL; ?>/patient_dashboard.php" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-black hover:bg-blue-700 transition-all flex items-center gap-2 shadow-lg shadow-blue-900/20">
                        <div class="w-5 h-5 bg-white/20 rounded flex items-center justify-center text-[10px]">
                            <?php echo strtoupper(substr($_SESSION['patient_name'] ?? 'P', 0, 1)); ?>
                        </div>
                        DASHBOARD
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/patient_login.php" class="hover:text-blue-200 transition-colors">Patient Login</a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/staff_portal.php" class="hover:text-blue-200 transition-colors">Staff Portal</a>
                <a href="<?php echo BASE_URL; ?>/donate.php" class="px-3 py-1 bg-white text-blue-600 rounded font-bold hover:bg-blue-50">Donate</a>
            </div>
        </div>
    </div>

    <!-- Sticky Navigation -->
    <nav class="glass-nav sticky top-0 w-full z-50 py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center cursor-pointer group" onclick="window.location.href='<?php echo BASE_URL; ?>/index.php'">
                    <div class="relative w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center overflow-hidden mr-4 group-hover:scale-105 transition-transform duration-300 shadow-sm border border-slate-100">
                        <img src="<?php echo BASE_URL; ?>/logo.png" alt="Hope Haven Logo" class="w-full h-full object-cover">
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black tracking-tighter leading-none text-blue-700">HOPE HAVEN <span class="text-amber-600">HOSPITAL</span></span>
                    </div>
                </div>
                
                <div class="hidden lg:flex items-center space-x-10">
                    <a href="<?php echo BASE_URL; ?>/index.php" class="text-sm font-bold text-slate-700 hover:text-blue-600 transition-colors">Home</a>
                    <a href="<?php echo BASE_URL; ?>/about.php" class="text-sm font-bold text-slate-700 hover:text-blue-600 transition-colors">About</a>
                    <a href="<?php echo BASE_URL; ?>/telemedicine.php" class="text-sm font-bold text-slate-700 hover:text-blue-600 transition-colors">Telemedicine</a>
                    <a href="<?php echo BASE_URL; ?>/events.php" class="text-sm font-bold text-slate-700 hover:text-blue-600 transition-colors">Events</a>
                    <a href="<?php echo BASE_URL; ?>/impact.php" class="text-sm font-bold text-slate-700 hover:text-blue-600 transition-colors">Our Impact</a>
                    <a href="<?php echo BASE_URL; ?>/calculator.php" class="text-sm font-bold text-slate-700 hover:text-blue-600 transition-colors">Medical Tools</a>
                    <a href="<?php echo BASE_URL; ?>/contact.php" class="text-sm font-bold text-slate-700 hover:text-blue-600 transition-colors">Contact</a>
                    <a href="<?php echo BASE_URL; ?>/appointment.php" class="bg-blue-600 text-white px-6 py-2.5 rounded-2xl text-xs font-black btn-premium uppercase tracking-widest">Appointment</a>
                </div>
                
                <div class="lg:hidden">
                    <button id="mobile-menu-btn" class="text-slate-700 p-2"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg></button>
                </div>
            </div>
        </div>
        <!-- Mobile Menu Drawer -->
        <div id="mobile-menu" class="hidden lg:hidden bg-white border-b border-slate-100 px-4 py-8 space-y-6 shadow-xl animate-fade-in-down">
            <a href="<?php echo BASE_URL; ?>/index.php" class="block text-lg font-black text-slate-700">Home</a>
            <a href="<?php echo BASE_URL; ?>/about.php" class="block text-lg font-black text-slate-700">Why Choose Us</a>
            <a href="<?php echo BASE_URL; ?>/telemedicine.php" class="block text-lg font-black text-slate-700">Telemedicine</a>
            <a href="<?php echo BASE_URL; ?>/events.php" class="block text-lg font-black text-slate-700">Events</a>
            <a href="<?php echo BASE_URL; ?>/impact.php" class="block text-lg font-black text-slate-700">Our Impact</a>
            <a href="<?php echo BASE_URL; ?>/calculator.php" class="block text-lg font-black text-blue-600">Medical Tools</a>
            <a href="<?php echo BASE_URL; ?>/contact.php" class="block text-lg font-black text-slate-700">Contact</a>
            
            <hr class="border-slate-100">
            
            <?php if(isset($_SESSION['patient_id'])): ?>
                <a href="<?php echo BASE_URL; ?>/patient_dashboard.php" class="block text-lg font-black text-blue-600">Patient Dashboard</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/patient_login.php" class="block text-lg font-black text-slate-700">Patient Login</a>
            <?php endif; ?>
            
            <a href="<?php echo BASE_URL; ?>/staff_portal.php" class="block text-lg font-black text-slate-700">Staff Portal</a>
            <a href="<?php echo BASE_URL; ?>/donate.php" class="block text-lg font-black text-blue-600">Donate Now</a>
            
            <a href="<?php echo BASE_URL; ?>/appointment.php" class="block bg-blue-600 text-white px-8 py-4 rounded-2xl text-center font-extrabold shadow-lg shadow-blue-200">Book Appointment</a>
        </div>
    </nav>
