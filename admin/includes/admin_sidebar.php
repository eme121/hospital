
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Navigation -->
<aside id="sidebar" class="fixed left-0 top-0 h-full bg-[#0f172a] text-slate-400 w-[280px] transition-all duration-300 z-[60] overflow-y-auto custom-scrollbar no-print">
    <div class="p-6 flex flex-col h-full">
        <!-- Logo Section -->
        <div class="flex items-center gap-3 mb-10 px-2 cursor-pointer" onclick="window.location.href='dashboard.php'">
            <div class="w-11 h-11 bg-blue-600 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-lg shadow-blue-500/20">
                <i class="fas fa-hospital-alt text-xl"></i>
            </div>
            <div class="logo-text transition-all duration-300 overflow-hidden whitespace-nowrap">
                <span class="block text-lg font-black tracking-tighter leading-none text-blue-400">HOPE HAVEN <span class="text-amber-500">HOSPITAL</span></span>
                <span class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest mt-1">Medical Center</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 space-y-1.5">
            <!-- Dashboard Link -->
            <a href="dashboard.php" class="nav-link flex items-center gap-4 px-4 py-3.5 rounded-2xl hover:bg-slate-800 hover:text-white transition-all group <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-th-large w-5 text-lg"></i>
                <span class="text-sm font-bold tracking-tight">Dashboard</span>
            </a>

            <!-- Appointments Group -->
            <div class="group-wrapper">
                <button class="submenu-trigger w-full flex items-center justify-between px-4 py-3.5 rounded-2xl hover:bg-slate-800 hover:text-white transition-all group">
                    <div class="flex items-center gap-4">
                        <i class="fas fa-calendar-check w-5 text-lg"></i>
                        <span class="text-sm font-bold tracking-tight">Appointments</span>
                    </div>
                    <i class="fas fa-chevron-right text-[10px] transition-transform duration-300 opacity-50"></i>
                </button>
                <div class="submenu overflow-hidden pl-12 space-y-1 mt-1 transition-all duration-300">
                    <a href="dashboard.php" class="block py-2 text-sm font-medium hover:text-white transition-colors">Physical Visits</a>
                    <a href="telemedicine.php" class="block py-2 text-sm font-medium hover:text-white transition-colors">Virtual Consults</a>
                    <a href="manage_insurance.php" class="block py-2 text-sm font-medium hover:text-white transition-colors <?php echo ($current_page == 'manage_insurance.php') ? 'text-blue-400' : ''; ?>">Insurance Partners</a>
                </div>
            </div>

            <!-- Doctors Group -->
            <div class="group-wrapper">
                <button class="submenu-trigger w-full flex items-center justify-between px-4 py-3.5 rounded-2xl hover:bg-slate-800 hover:text-white transition-all group">
                    <div class="flex items-center gap-4">
                        <i class="fas fa-user-md w-5 text-lg"></i>
                        <span class="text-sm font-bold tracking-tight">Medical Staff</span>
                    </div>
                    <i class="fas fa-chevron-right text-[10px] transition-transform duration-300 opacity-50"></i>
                </button>
                <div class="submenu overflow-hidden pl-12 space-y-1 mt-1 transition-all duration-300">
                    <a href="manage_doctors.php" class="block py-2 text-sm font-medium hover:text-white transition-colors">Manage Doctors</a>
                    <a href="specialists.php" class="block py-2 text-sm font-medium hover:text-white transition-colors">Specialist Care</a>
                    <a href="manage_availability.php" class="block py-2 text-sm font-medium hover:text-white transition-colors">Work Schedule</a>
                </div>
            </div>

            <!-- Patients Group -->
            <div class="group-wrapper">
                <button class="submenu-trigger w-full flex items-center justify-between px-4 py-3.5 rounded-2xl hover:bg-slate-800 hover:text-white transition-all group">
                    <div class="flex items-center gap-4">
                        <i class="fas fa-users w-5 text-lg"></i>
                        <span class="text-sm font-bold tracking-tight">Patient Care</span>
                    </div>
                    <i class="fas fa-chevron-right text-[10px] transition-transform duration-300 opacity-50"></i>
                </button>
                <div class="submenu overflow-hidden pl-12 space-y-1 mt-1 transition-all duration-300">
                    <a href="manage_patients.php" class="block py-2 text-sm font-medium hover:text-white transition-colors">Patient Registry</a>
                    <a href="records_dashboard.php" class="block py-2 text-sm font-medium hover:text-white transition-colors">Records Dashboard</a>
                    <a href="manage_medical_records.php" class="block py-2 text-sm font-medium hover:text-white transition-colors">Medical History</a>
                    <a href="manage_aid.php" class="block py-2 text-sm font-medium hover:text-white transition-colors">Financial Aid</a>
                </div>
            </div>

            <!-- Events -->
            <a href="manage_events.php" class="nav-link flex items-center gap-4 px-4 py-3.5 rounded-2xl hover:bg-slate-800 hover:text-white transition-all group <?php echo ($current_page == 'manage_events.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt w-5 text-lg"></i>
                <span class="text-sm font-bold tracking-tight">Hospital Events</span>
            </a>

            <!-- Services -->
            <a href="manage_services.php" class="nav-link flex items-center gap-4 px-4 py-3.5 rounded-2xl hover:bg-slate-800 hover:text-white transition-all group <?php echo ($current_page == 'manage_services.php') ? 'active' : ''; ?>">
                <i class="fas fa-stethoscope w-5 text-lg"></i>
                <span class="text-sm font-bold tracking-tight">Services</span>
            </a>

            <!-- HR -->
            <a href="manage_hr.php" class="nav-link flex items-center gap-4 px-4 py-3.5 rounded-2xl hover:bg-slate-800 hover:text-white transition-all group <?php echo ($current_page == 'manage_hr.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-tie w-5 text-lg"></i>
                <span class="text-sm font-bold tracking-tight">Human Resources</span>
            </a>

            <!-- Analytics -->
            <a href="reports.php" class="nav-link flex items-center gap-4 px-4 py-3.5 rounded-2xl hover:bg-slate-800 hover:text-white transition-all group <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie w-5 text-lg"></i>
                <span class="text-sm font-bold tracking-tight">System Reports</span>
            </a>

            <!-- Configuration -->
            <a href="settings.php" class="nav-link flex items-center gap-4 px-4 py-3.5 rounded-2xl hover:bg-slate-800 hover:text-white transition-all group <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog w-5 text-lg"></i>
                <span class="text-sm font-bold tracking-tight">System Settings</span>
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="mt-auto pt-6 border-t border-slate-800/50">
            <a href="logout.php" class="flex items-center gap-4 px-4 py-4 rounded-2xl text-rose-500 hover:bg-rose-500/10 transition-all group">
                <i class="fas fa-sign-out-alt w-5 text-lg"></i>
                <span class="text-sm font-black uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </div>
</aside>
