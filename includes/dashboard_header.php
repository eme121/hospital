<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | Hope Haven Hospital</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; scroll-behavior: smooth; }
        .glass-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
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
        .active-nav {
            color: #2563eb !important;
            position: relative;
        }
        .active-nav::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #2563eb;
            border-radius: 2px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">

    <!-- Dashboard Navigation -->
    <nav class="glass-nav sticky top-0 w-full z-50 py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center cursor-pointer group" onclick="window.location.href='patient_dashboard.php'">
                    <div class="relative w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center overflow-hidden mr-3 group-hover:scale-105 transition-transform duration-300 shadow-sm border border-slate-100">
                        <img src="logo.png" alt="Hope Haven Logo" class="w-full h-full object-cover">
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xl font-black text-slate-900 tracking-tighter leading-none">DASHBOARD</span>
                        <span class="text-[9px] font-bold text-blue-600 tracking-[0.2em] uppercase mt-1">Patient Portal</span>
                    </div>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden lg:flex items-center space-x-8">
                    <a href="patient_dashboard.php" class="text-sm font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'patient_dashboard.php' ? 'active-nav' : 'text-slate-500 hover:text-blue-600'; ?> transition-colors">Overview</a>
                    <a href="appointment.php" class="text-sm font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'appointment.php' ? 'active-nav' : 'text-slate-500 hover:text-blue-600'; ?> transition-colors">Book Appointment</a>
                    <a href="telemedicine_dashboard_patient.php" class="text-sm font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'telemedicine_dashboard_patient.php' ? 'active-nav' : 'text-slate-500 hover:text-blue-600'; ?> transition-colors">Virtual Care</a>
                    <a href="patient_settings.php" class="text-sm font-bold <?php echo basename($_SERVER['PHP_SELF']) == 'patient_settings.php' ? 'active-nav' : 'text-slate-500 hover:text-blue-600'; ?> transition-colors">Settings</a>
                </div>

                <!-- User Profile & Logout -->
                <div class="hidden lg:flex items-center space-x-6">
                    <div class="flex items-center px-4 py-2 bg-slate-100 rounded-2xl border border-slate-200">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white text-xs font-black mr-3">
                            <?php echo strtoupper(substr($_SESSION['patient_name'] ?? 'P', 0, 1)); ?>
                        </div>
                        <span class="text-xs font-black text-slate-700 uppercase tracking-wider"><?php echo htmlspecialchars($_SESSION['patient_name'] ?? 'Patient'); ?></span>
                    </div>
                    <a href="logout.php" class="px-6 py-3 bg-red-50 text-red-600 rounded-xl text-xs font-black hover:bg-red-600 hover:text-white transition-all">Logout</a>
                </div>
                
                <!-- Mobile Menu Button -->
                <div class="lg:hidden">
                    <button id="mobile-menu-btn" class="text-slate-700 p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden lg:hidden bg-white border-b border-slate-100 px-6 py-8 space-y-6 shadow-xl">
            <a href="patient_dashboard.php" class="block text-lg font-black text-slate-700">Overview</a>
            <a href="appointment.php" class="block text-lg font-black text-slate-700">Book Appointment</a>
            <a href="telemedicine_dashboard_patient.php" class="block text-lg font-black text-slate-700">Virtual Care</a>
            <a href="patient_settings.php" class="block text-lg font-black text-slate-700">Settings</a>
            <hr class="border-slate-100">
            <div class="flex items-center justify-between">
                <span class="font-black text-slate-900"><?php echo htmlspecialchars($_SESSION['patient_name'] ?? 'Patient'); ?></span>
                <a href="logout.php" class="text-red-600 font-black">Logout</a>
            </div>
        </div>
    </nav>

    <script>
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if(menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>
