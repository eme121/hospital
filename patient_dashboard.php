<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['patient_id']) || empty($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

// --- OPTIMIZED DATA FETCHING (Senior Architect Fix) ---
// Cache static profile info to avoid repeated queries
if (!isset($_SESSION['cached_patient_profile']) || $_SESSION['cached_patient_id'] !== $patient_id) {
    // Only fetch columns actually used in the dashboard
    $stmt = $conn->prepare("SELECT id, full_name, file_number, email, phone, gender, age, blood_group, allergies, risk_factors, created_at FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $_SESSION['cached_patient_profile'] = $stmt->get_result()->fetch_assoc();
    $_SESSION['cached_patient_id'] = $patient_id;

    $onboarding_res = $conn->query("SELECT status FROM patient_onboarding WHERE patient_id = $patient_id");
    $_SESSION['cached_onboarding_status'] = $onboarding_res->fetch_assoc()['status'] ?? 'Not Started';
}

$patient = $_SESSION['cached_patient_profile'];
$onboarding_status = $_SESSION['cached_onboarding_status'];

// If they have only 'Paid' but NOT filled the form, we used to kick them back.
if (!in_array($onboarding_status, ['Completed', 'Sent to Nursing', 'Verified', 'In Intake', 'Pending Records', 'Paid'])) {
    header("Location: onboarding.php");
    exit();
}
$show_completion_banner = ($onboarding_status === 'Paid');

// Statistics: Consolidated into a single query
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM appointments WHERE patient_id = $patient_id) as appts,
    (SELECT COUNT(*) FROM telemedicine_appointments WHERE patient_id = $patient_id) as tele,
    (SELECT COUNT(*) FROM lab_results WHERE patient_id = $patient_id AND status = 'Released') as labs,
    (SELECT COUNT(*) FROM telemedicine_prescriptions WHERE patient_id = $patient_id) as prescriptions,
    (SELECT SUM(total_amount - paid_amount) FROM invoices WHERE patient_id = $patient_id) as balance";
$stats = $conn->query($stats_query)->fetch_assoc();

$total_appointments = $stats['appts'];
$total_telemedicine = $stats['tele'];
$total_labs = $stats['labs'];
$total_prescriptions = $stats['prescriptions'];
$balance_due = $stats['balance'] ?? 0;

// --- Timeline Data Fetching ---
$timeline = [];
// ... (rest of timeline code) ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | Hope Haven Hospital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Professional Real-Time Sync Configuration -->
    <script>
        window.APP_BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/sync_engine.js?v=<?php echo time(); ?>"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .sidebar-item.active { background-color: #eff6ff; color: #2563eb; border-right: 4px solid #2563eb; }
        .sidebar-transition { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
        .timeline-line::before { content: ''; position: absolute; left: 20px; top: 0; bottom: 0; width: 2px; background: #f1f5f9; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
        }
        /* Custom scrollbar for sidebar */
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="flex min-h-screen">

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 sidebar-transition md:relative md:transform-none">
        <div class="p-6 flex flex-col h-full">
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white font-black shadow-lg shadow-blue-200">H</div>
                <h1 class="text-xl font-black text-slate-900 tracking-tighter">HOPE<span class="text-blue-600">HAVEN</span></h1>
            </div>

            <nav class="sidebar-nav space-y-1 flex-1 overflow-y-auto pr-2">
                <a href="#home" data-section="home" class="sidebar-item active flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </a>
                <a href="#appointments" data-section="appointments" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="calendar" class="w-5 h-5"></i> Appointments
                </a>
                <a href="#vitals" data-section="vitals" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="activity" class="w-5 h-5"></i> Health Vitals
                </a>
                <a href="#labs" data-section="labs" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="test-tube" class="w-5 h-5"></i> Lab Results
                </a>
                <a href="#prescriptions" data-section="prescriptions" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="pill" class="w-5 h-5"></i> Prescriptions
                </a>
                <a href="#health-journey" data-section="health-journey" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="map" class="w-5 h-5"></i> Health Journey
                </a>
                <a href="#billing" data-section="billing" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="credit-card" class="w-5 h-5"></i> Billing & Aid
                </a>
                <a href="#settings" data-section="settings" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="settings" class="w-5 h-5"></i> Profile Settings
                </a>
                <a href="patient_medical_record.php" target="_blank" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="printer" class="w-5 h-5"></i> Print Medical History
                </a>
            </nav>

            <div class="mt-auto pt-6 border-t border-slate-100">
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-red-600 hover:bg-red-50 transition-all">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/20 z-40 hidden md:hidden"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Top Header -->
        <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shrink-0">
            <div class="flex items-center gap-4">
                <button id="mobile-toggle" class="md:hidden p-2 text-slate-600"><i data-lucide="menu"></i></button>
                <h2 id="section-title" class="text-lg font-black text-slate-900 uppercase tracking-tight">Dashboard Overview</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative">
                    <button id="notif-toggle" class="p-2 bg-slate-50 text-slate-600 rounded-xl border border-slate-200 hover:bg-slate-100 transition-all relative">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span id="notif-badge" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-rose-600 text-white text-[8px] font-black rounded-full flex items-center justify-center border-2 border-white animate-bounce">0</span>
                    </button>
                    <!-- Notifications Dropdown -->
                    <div id="notif-dropdown" class="hidden absolute right-0 mt-3 w-80 bg-white rounded-3xl shadow-2xl border border-slate-100 z-[100] overflow-hidden">
                        <div class="p-5 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                            <h4 class="text-xs font-black text-slate-900 uppercase tracking-widest">Notifications</h4>
                            <button onclick="markAllRead()" class="text-[9px] font-black text-blue-600 uppercase hover:underline">Mark all read</button>
                        </div>
                        <div id="notif-list" class="max-h-96 overflow-y-auto divide-y divide-slate-50">
                            <!-- Loaded via JS -->
                            <div class="p-8 text-center text-slate-400 font-bold italic text-xs">No new notifications.</div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-4 py-2 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-[10px] font-black <?php echo $balance_due > 0 ? 'text-rose-600' : 'text-emerald-600'; ?> uppercase tracking-widest">
                        Balance: ₦<?php echo number_format($balance_due); ?>
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-black text-slate-900"><?php echo htmlspecialchars($patient['full_name']); ?></p>
                        <p class="text-[10px] font-bold text-slate-400">#<?php echo htmlspecialchars($patient['file_number']); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-black">
                        <?php echo strtoupper(substr($patient['full_name'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Section Container -->
        <div class="flex-1 overflow-y-auto p-6 space-y-8 scroll-smooth" id="main-content">
            
            <!-- ALERT BANNER: Allergies & Risks -->
            <?php if (!empty($patient['allergies']) || !empty($patient['risk_factors'])): ?>
            <div class="flex flex-col md:flex-row gap-4">
                <?php if (!empty($patient['allergies'])): ?>
                <div class="flex-1 bg-rose-50 border border-rose-100 rounded-2xl p-4 flex items-center gap-4 animate-pulse">
                    <div class="w-10 h-10 bg-rose-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-rose-200">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-black text-rose-600 uppercase tracking-widest">Allergy Alert</h4>
                        <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($patient['allergies']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($patient['risk_factors'])): ?>
                <div class="flex-1 bg-blue-50 border border-blue-100 rounded-2xl p-4 flex items-center gap-4">
                    <div class="w-10 h-10 bg-blue-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Medical Risk Factors</h4>
                        <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($patient['risk_factors']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- HOME SECTION -->
            <section id="home-section" class="content-section space-y-8">
                <!-- Registration Status Banner -->
                <?php if (in_array($onboarding['status'], ['Pending Records', 'Verified', 'Sent to Nursing', 'In Intake'])): ?>
                <div class="bg-blue-50 border border-blue-100 rounded-3xl p-6 flex flex-col md:flex-row items-center justify-between gap-6 animate-in">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-200">
                            <i data-lucide="<?php echo $onboarding['status'] === 'Pending Records' ? 'search' : 'shield-check'; ?>" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight">Registration Status: <?php echo $onboarding['status']; ?></h4>
                            <p class="text-xs font-medium text-slate-500 max-w-lg">
                                <?php 
                                    switch($onboarding['status']) {
                                        case 'Pending Records': echo "Your medical record is currently under review by our administrative team for final approval."; break;
                                        case 'Verified': echo "Your records have been approved! Please proceed to the Nursing Station for your initial vitals intake."; break;
                                        case 'Sent to Nursing': echo "Your file has been sent to the Nursing Station. Please proceed to the vitals desk at the hospital."; break;
                                        case 'In Intake': echo "Your clinical intake is in progress. Your dashboard will fully activate after your first consultation."; break;
                                    }
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="onboarding.php" class="px-6 py-3 bg-white text-slate-900 border border-slate-200 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-50 transition-all">View Details</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- WhatsApp Alert Setup (Only for Testing/Sandbox) -->
                <div class="bg-emerald-50 border border-emerald-100 rounded-3xl p-6 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
                            <i class="fab fa-whatsapp text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-slate-900">WhatsApp Notification Status</h4>
                            <p class="text-xs font-medium text-slate-500">If you aren't receiving alerts, you may need to re-activate the secure channel (Sandbox expires every 72h).</p>
                        </div>
                    </div>
                    <a href="https://wa.me/14155238886?text=join%20numeral-wonderful" target="_blank" class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100">
                        Re-Activate Alerts
                    </a>
                </div>

                <!-- Welcome Banner -->
                <div class="bg-blue-600 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl shadow-blue-100">
                    <div class="relative z-10">
                        <h3 class="text-3xl font-black mb-2">Welcome Back, <?php echo explode(' ', $patient['full_name'])[0]; ?>!</h3>
                        <p class="text-blue-100 font-medium mb-6">You have <?php echo $upcoming_res->num_rows; ?> upcoming appointments this week.</p>
                        <div class="flex flex-wrap gap-3">
                            <a href="appointment.php" class="px-6 py-3 bg-white text-blue-600 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-50 transition-all shadow-lg">Book Appointment</a>
                            <button onclick="document.getElementById('proofModal').classList.remove('hidden')" class="px-6 py-3 bg-blue-500 text-white border border-blue-400 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-400 transition-all">Pay Bill</button>
                        </div>
                    </div>
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm card-hover transition-all">
                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 mb-4"><i data-lucide="calendar"></i></div>
                        <p class="text-xs font-bold text-slate-400 uppercase mb-1">Total Visits</p>
                        <h4 class="text-2xl font-black text-slate-900"><?php echo $total_appointments + $total_telemedicine; ?></h4>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm card-hover transition-all">
                        <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 mb-4"><i data-lucide="test-tube"></i></div>
                        <p class="text-xs font-bold text-slate-400 uppercase mb-1">Lab Results</p>
                        <h4 class="text-2xl font-black text-slate-900"><?php echo $total_labs; ?></h4>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm card-hover transition-all">
                        <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600 mb-4"><i data-lucide="pill"></i></div>
                        <p class="text-xs font-bold text-slate-400 uppercase mb-1">Medications</p>
                        <h4 class="text-2xl font-black text-slate-900"><?php echo $total_prescriptions; ?></h4>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm card-hover transition-all">
                        <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 mb-4"><i data-lucide="credit-card"></i></div>
                        <p class="text-xs font-bold text-slate-400 uppercase mb-1">Balance Due</p>
                        <h4 class="text-2xl font-black text-rose-600">₦<?php echo number_format($balance_due); ?></h4>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Upcoming (Home) -->
                    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                            <h4 class="font-black text-slate-900 flex items-center gap-2"><i data-lucide="clock" class="w-5 h-5 text-blue-600"></i> Next Visits</h4>
                            <a href="#appointments" class="text-[10px] font-black text-blue-600 uppercase tracking-widest hover:underline">View All</a>
                        </div>
                        <div class="divide-y divide-slate-50">
                            <?php if ($upcoming_res->num_rows == 0): ?>
                                <div class="p-8 text-center text-slate-400 font-bold italic">No upcoming appointments.</div>
                            <?php else: while($u = $upcoming_res->fetch_assoc()): ?>
                                <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center font-black text-xs"><?php echo date('d M', strtotime($u['appointment_date'])); ?></div>
                                    <div>
                                        <p class="text-sm font-black text-slate-900">Dr. <?php echo htmlspecialchars($u['doctor']); ?></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase"><?php echo $u['type']; ?> • <?php echo date('h:i A', strtotime($u['appointment_time'])); ?></p>
                                    </div>
                                    </div>
                                    <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[9px] font-black uppercase"><?php echo $u['status']; ?></span>
                                    </div>
                                    <?php endwhile; endif; ?>
                                    </div>
                                    </div>                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 flex flex-col gap-4">
                        <h4 class="font-black text-slate-900 mb-2">Quick Actions</h4>
                        <a href="telemedicine.php" class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl hover:bg-indigo-50 transition-all border border-transparent hover:border-indigo-100 group">
                            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i data-lucide="video" class="w-5 h-5"></i></div>
                            <span class="text-sm font-black text-slate-700">Video Call Doctor</span>
                        </a>
                        <a href="patient_labs.php" class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl hover:bg-emerald-50 transition-all border border-transparent hover:border-emerald-100 group">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i data-lucide="download" class="w-5 h-5"></i></div>
                            <span class="text-sm font-black text-slate-700">Download Reports</span>
                        </a>
                        <a href="financial_aid.php" class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl hover:bg-blue-50 transition-all border border-transparent hover:border-blue-100 group">
                            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i data-lucide="help-circle" class="w-5 h-5"></i></div>
                            <span class="text-sm font-black text-slate-700">Financial Support</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- APPOINTMENTS SECTION -->
            <section id="appointments-section" class="content-section hidden space-y-8">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-100 flex justify-between items-center">
                        <h4 class="text-xl font-black text-slate-900 tracking-tight">Visit History</h4>
                        <a href="appointment.php" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all">Schedule Visit</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Provider</th>
                                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type</th>
                                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date & Time</th>
                                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php 
                                $all_appt_query = "
                                    (SELECT 'Physical' as type, a.appointment_date, a.appointment_time, d.name as doctor, a.status 
                                     FROM appointments a LEFT JOIN doctors d ON a.doctor_id = d.id 
                                     WHERE a.patient_id = $patient_id)
                                    UNION ALL
                                    (SELECT 'Virtual' as type, ta.appointment_date, ta.appointment_time, td.name as doctor, ta.status 
                                     FROM telemedicine_appointments ta LEFT JOIN telemedicine_doctors td ON ta.doctor_id = td.id 
                                     WHERE ta.patient_id = $patient_id)
                                    ORDER BY appointment_date DESC, appointment_time DESC";
                                $all_appt_res = $conn->query($all_appt_query);
                                if($all_appt_res) while($a = $all_appt_res->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-8 py-6">
                                            <p class="text-sm font-black text-slate-900">Dr. <?php echo htmlspecialchars($a['doctor']); ?></p>
                                        </td>
                                        <td class="px-8 py-6">
                                            <span class="text-xs font-bold text-slate-500"><?php echo $a['type']; ?></span>
                                        </td>
                                        <td class="px-8 py-6">
                                            <p class="text-xs font-black text-slate-900"><?php echo date('d M Y', strtotime($a['appointment_date'])); ?></p>
                                            <p class="text-[10px] font-bold text-slate-400"><?php echo $a['appointment_time']; ?></p>
                                        </td>
                                        <td class="px-8 py-6">
                                            <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-[9px] font-black uppercase tracking-widest"><?php echo $a['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- VITALS SECTION -->
            <section id="vitals-section" class="content-section hidden space-y-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="space-y-6">
                        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
                            <div><p class="text-[10px] font-black text-slate-400 uppercase mb-1">Heart Rate</p><h3 id="currentHR" class="text-3xl font-black text-slate-900">--</h3></div>
                            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center"><i data-lucide="heart"></i></div>
                        </div>
                        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
                            <div><p class="text-[10px] font-black text-slate-400 uppercase mb-1">Blood Pressure</p><h3 id="currentBP" class="text-3xl font-black text-slate-900">--/--</h3></div>
                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center"><i data-lucide="activity"></i></div>
                        </div>
                        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
                            <div><p class="text-[10px] font-black text-slate-400 uppercase mb-1">Temperature</p><h3 id="currentTemp" class="text-3xl font-black text-slate-900">--.-</h3></div>
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center"><i data-lucide="thermometer"></i></div>
                        </div>
                    </div>
                    <div class="lg:col-span-2 bg-white p-8 rounded-[40px] border border-slate-200 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="font-black text-slate-900 uppercase tracking-widest text-xs">Vitals Trends</h4>
                            <select id="vitalsMetric" class="text-[10px] font-bold bg-slate-50 border-0 rounded-lg p-2"><option value="hr">Heart Rate</option><option value="bp">Blood Pressure</option><option value="temp">Temperature</option></select>
                        </div>
                        <div class="h-64"><canvas id="vitalsChart"></canvas></div>
                    </div>
                </div>
            </section>

            <!-- HEALTH JOURNEY SECTION -->
            <section id="health-journey-section" class="content-section hidden space-y-8">
                <!-- Journey Overview Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white p-8 rounded-[40px] border border-slate-200 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="font-black text-slate-900 uppercase tracking-widest text-xs">Vitals Progression</h4>
                            <div class="flex gap-2">
                                <span class="flex items-center gap-1 text-[9px] font-black text-blue-600 uppercase"><div class="w-2 h-2 bg-blue-500 rounded-full"></div> Sys</span>
                                <span class="flex items-center gap-1 text-[9px] font-black text-indigo-600 uppercase"><div class="w-2 h-2 bg-indigo-500 rounded-full"></div> Dia</span>
                            </div>
                        </div>
                        <div class="h-64"><canvas id="journeyVitalsChart"></canvas></div>
                    </div>
                    <div class="bg-white p-8 rounded-[40px] border border-slate-200 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="font-black text-slate-900 uppercase tracking-widest text-xs">Glucose & Weight Trends</h4>
                        </div>
                        <div class="h-64"><canvas id="journeyMiscChart"></canvas></div>
                    </div>
                </div>

                <!-- Vertical Milestone Timeline -->
                <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                    <h4 class="text-xl font-black text-slate-900 mb-10 text-center">Your Medical Milestones</h4>
                    <div class="relative timeline-line ml-4 space-y-12">
                        <?php foreach ($timeline as $item): ?>
                            <div class="relative pl-14 group">
                                <div class="absolute left-0 top-0 w-12 h-12 bg-white border-4 border-<?php echo $item['color']; ?>-500 text-<?php echo $item['color']; ?>-600 rounded-2xl flex items-center justify-center shadow-xl shadow-<?php echo $item['color']; ?>-100 z-10 group-hover:scale-110 transition-transform">
                                    <i data-lucide="<?php echo $item['icon']; ?>" class="w-6 h-6"></i>
                                </div>
                                <div class="bg-slate-50 p-8 rounded-[32px] border border-slate-100 group-hover:border-<?php echo $item['color']; ?>-200 group-hover:bg-white transition-all shadow-sm group-hover:shadow-xl group-hover:shadow-slate-200/50">
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                                        <div>
                                            <span class="px-3 py-1 bg-<?php echo $item['color']; ?>-50 text-<?php echo $item['color']; ?>-600 rounded-full text-[10px] font-black uppercase tracking-widest"><?php echo $item['event_type']; ?></span>
                                            <h5 class="text-xl font-black text-slate-900 mt-2"><?php echo htmlspecialchars($item['provider']); ?></h5>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-black text-slate-900"><?php echo date('F d, Y', strtotime($item['date'])); ?></p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase"><?php echo $item['time']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($item['detail'])): ?>
                                        <div class="p-4 bg-white rounded-2xl border border-slate-100 text-sm font-medium text-slate-600 italic leading-relaxed">
                                            "<?php echo htmlspecialchars($item['detail']); ?>"
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-6 flex items-center gap-4">
                                        <span class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500"></i>
                                            <?php echo $item['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- The beginning of the journey -->
                        <div class="relative pl-14">
                            <div class="absolute left-0 top-0 w-12 h-12 bg-slate-900 text-white rounded-2xl flex items-center justify-center shadow-xl z-10">
                                <i data-lucide="flag" class="w-6 h-6"></i>
                            </div>
                            <div class="p-8">
                                <h5 class="text-lg font-black text-slate-900">Joined Hope Haven</h5>
                                <p class="text-sm font-bold text-slate-400"><?php echo date('F d, Y', strtotime($patient['created_at'])); ?></p>
                            </div>
                        </div>

                        <?php if(empty($timeline)): ?>
                            <div class="py-20 text-center">
                                <div class="w-20 h-20 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i data-lucide="map" class="w-10 h-10"></i>
                                </div>
                                <p class="text-slate-400 font-bold italic">Your journey timeline is currently being prepared.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- LABS SECTION -->
            <section id="labs-section" class="content-section hidden space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    $labs_all = $conn->query("SELECT lr.*, lt.test_name, lt.normal_range as reference_range FROM lab_results lr JOIN lab_requests lreq ON lr.request_id = lreq.id JOIN lab_tests lt ON lreq.test_id = lt.id WHERE lr.patient_id = $patient_id ORDER BY lr.released_at DESC");
                    if ($labs_all && $labs_all->num_rows == 0): ?>
                        <div class="col-span-full p-16 text-center text-slate-400 font-bold italic bg-white rounded-3xl border border-slate-200">No laboratory records available.</div>
                    <?php else: if($labs_all) while($lab = $labs_all->fetch_assoc()): ?>
                        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm card-hover transition-all">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center"><i data-lucide="microscope"></i></div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?php echo date('d M Y', strtotime($lab['released_at'])); ?></span>
                            </div>
                            <h4 class="text-lg font-black text-slate-900 mb-2"><?php echo htmlspecialchars($lab['test_name']); ?></h4>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 mb-6 text-sm font-bold text-slate-700 italic">"<?php echo htmlspecialchars($lab['findings']); ?>"</div>
                            <div class="flex items-center justify-between pt-6 border-t border-slate-50">
                                <div class="text-[10px] font-bold text-slate-400">Ref: <?php echo htmlspecialchars($lab['reference_range'] ?? 'N/A'); ?></div>
                                <a href="patient_labs.php" class="text-blue-600 hover:underline text-xs font-black uppercase tracking-widest">View PDF</a>
                            </div>
                        </div>
                    <?php endwhile; endif; ?>
                </div>
            </section>

            <!-- PRESCRIPTIONS SECTION -->
            <section id="prescriptions-section" class="content-section hidden space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php 
                    $presc_all = $conn->query("SELECT p.*, COALESCE(td.name, dr.name, 'Medical Staff') as doctor_name FROM telemedicine_prescriptions p LEFT JOIN telemedicine_doctors td ON p.doctor_id = td.id LEFT JOIN doctors dr ON p.doctor_id = dr.id WHERE p.patient_id = $patient_id ORDER BY p.created_at DESC");
                    if ($presc_all && $presc_all->num_rows == 0): ?>
                        <div class="col-span-full py-20 text-center bg-white rounded-[40px] border border-slate-100 shadow-sm">
                            <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-3xl flex items-center justify-center mx-auto mb-6">
                                <i data-lucide="pill" class="w-10 h-10"></i>
                            </div>
                            <p class="text-slate-400 font-bold italic">No prescriptions found in your records.</p>
                        </div>
                    <?php else: if($presc_all) while($p = $presc_all->fetch_assoc()): ?>
                        <div class="p-8 bg-white rounded-[40px] border border-slate-200 shadow-sm card-hover transition-all">
                            <div class="flex justify-between items-center mb-6">
                                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Dr. <?php echo htmlspecialchars($p['doctor_name']); ?></span>
                                <span class="text-[10px] font-bold text-slate-400"><?php echo date('d M Y', strtotime($p['created_at'])); ?></span>
                            </div>
                            
                            <?php if (!empty($p['medications_json'])): 
                                $meds = json_decode($p['medications_json'], true);
                                if (is_array($meds)): foreach($meds as $m): ?>
                                    <div class="mb-4">
                                        <h5 class="text-xl font-black text-slate-900 mb-2"><?php echo htmlspecialchars($m['drug'] ?? 'Medication'); ?></h5>
                                        <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 text-sm font-black text-emerald-700">
                                            <?php echo htmlspecialchars($m['dosage'] ?? ''); ?> 
                                            <?php if(!empty($m['duration'])) echo " • " . htmlspecialchars($m['duration']) . " Days"; ?>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>
                            <?php else: ?>
                                <h5 class="text-xl font-black text-slate-900 mb-4"><?php echo htmlspecialchars($p['medications'] ?? 'Prescription'); ?></h5>
                                <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 mb-4 text-sm font-black text-emerald-700"><?php echo htmlspecialchars($p['dosage'] ?? 'N/A'); ?></div>
                            <?php endif; ?>

                            <?php if(!empty($p['notes'])): ?>
                                <p class="text-xs text-slate-500 font-medium italic mt-4">"<?php echo htmlspecialchars($p['notes']); ?>"</p>
                            <?php endif; ?>
                            
                            <?php if(!empty($p['dosage_times'])): ?>
                                <div class="mt-6 pt-6 border-t border-slate-50 flex items-center gap-2">
                                    <i data-lucide="clock" class="w-3 h-3 text-slate-400"></i>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Schedule: <?php echo htmlspecialchars($p['dosage_times']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; endif; ?>
                </div>
            </section>

            <!-- BILLING SECTION -->
            <section id="billing-section" class="content-section hidden space-y-8">
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="bg-white p-10 rounded-[40px] border border-slate-200 shadow-sm text-center">
                        <p class="text-xs font-black text-slate-400 uppercase mb-2">Total Balance Due</p>
                        <h3 class="text-5xl font-black text-rose-600 mb-8">₦<?php echo number_format($balance_due); ?></h3>
                        <div class="flex flex-col gap-3">
                            <button onclick="document.getElementById('proofModal').classList.remove('hidden')" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all">Settle Payment</button>
                            <a href="financial_aid.php" class="w-full py-4 bg-slate-50 text-slate-600 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-slate-100 transition-all border border-slate-200">Request Financial Aid</a>
                        </div>
                    </div>
                    
                    <!-- My Aid Requests -->
                    <div class="bg-white p-10 rounded-[40px] border border-slate-200 shadow-sm">
                        <h4 class="font-black text-slate-900 mb-6 flex items-center justify-between">
                            My Aid Requests
                            <span class="text-[10px] bg-blue-50 text-blue-600 px-3 py-1 rounded-full uppercase">Status</span>
                        </h4>
                        <div class="space-y-4">
                            <?php 
                            $my_aid = $conn->query("SELECT * FROM financial_aid_requests WHERE patient_id = $patient_id ORDER BY created_at DESC");
                            if($my_aid && $my_aid->num_rows > 0): 
                                while($aid = $my_aid->fetch_assoc()):
                                    $prog = ($aid['current_amount'] / $aid['amount']) * 100;
                            ?>
                                <div class="p-5 bg-slate-50 rounded-3xl border border-slate-100">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <p class="text-sm font-black text-slate-900"><?php echo htmlspecialchars($aid['name']); ?></p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase">Target: ₦<?php echo number_format($aid['amount']); ?></p>
                                        </div>
                                        <span class="px-3 py-1 <?php echo $aid['status'] == 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'; ?> rounded-full text-[9px] font-black uppercase"><?php echo $aid['status']; ?></span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-[9px] font-black uppercase tracking-tighter">
                                            <span class="text-blue-600">Raised: ₦<?php echo number_format($aid['current_amount']); ?></span>
                                            <span class="text-slate-400"><?php echo round($prog); ?>%</span>
                                        </div>
                                        <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-600" style="width: <?php echo $prog; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; else: ?>
                                <div class="py-10 text-center border-2 border-dashed border-slate-100 rounded-3xl">
                                    <p class="text-slate-400 font-bold italic text-xs">No active aid requests.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-10 rounded-[40px] border border-slate-200 shadow-sm">
                    <h4 class="font-black text-slate-900 mb-6">Recent Invoices</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php 
                        $inv_res = $conn->query("SELECT * FROM invoices WHERE patient_id = $patient_id ORDER BY created_at DESC LIMIT 6");
                        if($inv_res) while($inv = $inv_res->fetch_assoc()): 
                            $is_unpaid = ($inv['total_amount'] > $inv['paid_amount']);
                        ?>
                            <div class="flex flex-col p-5 bg-slate-50 rounded-2xl border border-slate-100 hover:border-blue-200 transition-all">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <p class="text-sm font-black text-slate-900">#INV-<?php echo $inv['id']; ?></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase"><?php echo date('d M Y', strtotime($inv['created_at'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-black text-slate-700">₦<?php echo number_format($inv['total_amount']); ?></p>
                                        <span class="text-[9px] font-bold <?php echo $is_unpaid ? 'text-rose-500' : 'text-emerald-500'; ?> uppercase">
                                            <?php echo $is_unpaid ? 'Unpaid' : 'Paid'; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if($is_unpaid): ?>
                                    <button onclick="payOnline(<?php echo $inv['id']; ?>)" id="btn-dash-<?php echo $inv['id']; ?>" class="w-full py-2 bg-blue-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all">
                                        Pay Now
                                    </button>
                                <?php else: ?>
                                    <div class="w-full py-2 bg-emerald-50 text-emerald-600 rounded-xl text-[9px] font-black uppercase tracking-widest text-center border border-emerald-100">
                                        Settled
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <a href="patient_billing.php" class="block text-center text-[10px] font-black text-blue-600 uppercase tracking-widest pt-8 hover:underline">View Detailed Billing History</a>
                </div>
            </section>

            <!-- SETTINGS SECTION -->
            <section id="settings-section" class="content-section hidden space-y-8">
                <div class="max-w-2xl bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                    <h4 class="text-xl font-black text-slate-900 mb-8">Profile Information</h4>
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div><label class="text-[10px] font-black text-slate-400 uppercase">Full Name</label><p class="text-sm font-bold text-slate-900 mt-1"><?php echo htmlspecialchars($patient['full_name']); ?></p></div>
                            <div><label class="text-[10px] font-black text-slate-400 uppercase">Email</label><p class="text-sm font-bold text-slate-900 mt-1"><?php echo htmlspecialchars($patient['email']); ?></p></div>
                            <div><label class="text-[10px] font-black text-slate-400 uppercase">Phone</label><p class="text-sm font-bold text-slate-900 mt-1"><?php echo htmlspecialchars($patient['phone']); ?></p></div>
                            <div><label class="text-[10px] font-black text-slate-400 uppercase">File ID</label><p class="text-sm font-bold text-slate-900 mt-1"><?php echo htmlspecialchars($patient['file_number']); ?></p></div>
                        </div>
                        <a href="patient_settings.php" class="inline-block mt-8 px-8 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition-all">Edit Profile Settings</a>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Modals -->
    <div id="proofModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-6">
        <div class="bg-white max-w-lg w-full rounded-[40px] p-10 shadow-2xl">
            <div class="flex justify-between items-center mb-8"><h2 class="text-2xl font-black text-slate-900">Upload Payment</h2><button onclick="document.getElementById('proofModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x"></i></button></div>
            <form id="proofForm" class="space-y-6">
                <input type="number" name="amount" placeholder="Amount Paid (₦)" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold outline-none ring-2 ring-transparent focus:ring-blue-600 transition-all">
                <input type="text" name="description" placeholder="Payment Description" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold outline-none ring-2 ring-transparent focus:ring-blue-600 transition-all">
                <input type="file" name="proof" accept="image/*" required class="w-full text-xs font-bold text-slate-400">
                <button type="submit" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl">Submit Proof</button>
            </form>
        </div>
    </div>

    <script>
        // --- Sidebar & Nav ---
        const sidebarItems = document.querySelectorAll('.sidebar-item');
        const sections = document.querySelectorAll('.content-section');
        const sectionTitle = document.getElementById('section-title');

        function showSection(sectionId) {
            sidebarItems.forEach(i => {
                i.classList.toggle('active', i.getAttribute('data-section') === sectionId);
            });
            sections.forEach(s => s.classList.add('hidden'));
            const target = document.getElementById(`${sectionId}-section`);
            if (target) {
                target.classList.remove('hidden');
                sectionTitle.textContent = sectionId === 'home' ? 'Dashboard Overview' : sectionId.replace(/^\w/, c => c.toUpperCase());
                window.location.hash = sectionId;
            }
        }

        sidebarItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const sectionId = item.getAttribute('data-section');
                showSection(sectionId);
                if (window.innerWidth < 768) { 
                    document.getElementById('sidebar').classList.remove('open'); 
                    document.getElementById('sidebar-overlay').classList.add('hidden'); 
                }
            });
        });

        // Initialize from Hash
        window.addEventListener('load', () => {
            const hash = window.location.hash.replace('#', '');
            if (hash && document.getElementById(`${hash}-section`)) {
                showSection(hash);
            }
        });
        document.getElementById('mobile-toggle').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });
        document.getElementById('sidebar-overlay').addEventListener('click', () => { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebar-overlay').classList.add('hidden'); });

        // --- Vitals Chart Logic ---
        let vitalsChart;
        function initVitalsChart(data, metric) {
            const ctx = document.getElementById('vitalsChart').getContext('2d');
            if (vitalsChart) vitalsChart.destroy();
            const filteredData = data.filter(v => v[metric === 'bp' ? 'blood_pressure_sys' : (metric === 'hr' ? 'heart_rate' : 'temperature')]);
            vitalsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: filteredData.map(v => new Date(v.recorded_at).toLocaleDateString()).reverse(),
                    datasets: [{
                        label: metric.toUpperCase(),
                        data: filteredData.map(v => metric === 'bp' ? v.blood_pressure_sys : (metric === 'hr' ? v.heart_rate : v.temperature)).reverse(),
                        borderColor: '#2563eb',
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(37, 99, 235, 0.1)'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { grid: { display: false } }, x: { grid: { display: false } } } }
            });
        }
        function fetchVitals() {
            fetch(`api/vitals.php?action=get&patient_id=<?php echo $patient_id; ?>&limit=10`).then(r => r.json()).then(data => {
                if (data.success && data.vitals.length > 0) {
                    const latest = data.vitals[0];
                    document.getElementById('currentHR').textContent = latest.heart_rate || '--';
                    document.getElementById('currentBP').textContent = `${latest.blood_pressure_sys || '--'}/${latest.blood_pressure_dia || '--'}`;
                    document.getElementById('currentTemp').textContent = `${latest.temperature || '--.-'}°C`;
                    window.vitalsData = data.vitals;
                    initVitalsChart(data.vitals, 'hr');
                    initJourneyCharts(data.vitals);
                }
            });
        }

        let journeyVitalsChart, journeyMiscChart;
        function initJourneyCharts(data) {
            const vitalsCtx = document.getElementById('journeyVitalsChart').getContext('2d');
            const miscCtx = document.getElementById('journeyMiscChart').getContext('2d');
            const labels = data.map(v => new Date(v.recorded_at).toLocaleDateString()).reverse();

            if (journeyVitalsChart) journeyVitalsChart.destroy();
            journeyVitalsChart = new Chart(vitalsCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Systolic', data: data.map(v => v.blood_pressure_sys).reverse(), borderColor: '#3b82f6', tension: 0.4, fill: false },
                        { label: 'Diastolic', data: data.map(v => v.blood_pressure_dia).reverse(), borderColor: '#6366f1', tension: 0.4, fill: false }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });

            if (journeyMiscChart) journeyMiscChart.destroy();
            journeyMiscChart = new Chart(miscCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Weight (kg)', data: data.map(v => v.weight).reverse(), borderColor: '#10b981', tension: 0.4, fill: false, yAxisID: 'y' },
                        { label: 'Glucose (mg/dL)', data: data.map(v => v.fasting_blood_sugar).reverse(), borderColor: '#3b82f6', tension: 0.4, fill: false, yAxisID: 'y1' }
                    ]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    scales: { 
                        y: { type: 'linear', display: true, position: 'left' },
                        y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false } }
                    }
                }
            });
        }
        document.getElementById('vitalsMetric').addEventListener('change', (e) => initVitalsChart(window.vitalsData, e.target.value));

        // --- Modals Form Submit ---
        document.getElementById('proofForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('api/submit_payment_proof.php', { method: 'POST', body: formData }).then(r => r.json()).then(data => {
                Swal.fire({ icon: data.status === 'success' ? 'success' : 'error', title: data.message });
                if(data.status === 'success') location.reload();
            });
        });

        // --- Notifications Logic ---
        const notifToggle = document.getElementById('notif-toggle');
        const notifDropdown = document.getElementById('notif-dropdown');
        const notifList = document.getElementById('notif-list');
        const notifBadge = document.getElementById('notif-badge');

        notifToggle.addEventListener('click', () => {
            notifDropdown.classList.toggle('hidden');
            if (!notifDropdown.classList.contains('hidden')) fetchNotifications();
        });

        function fetchNotifications() {
            fetch('api/notifications.php?action=get&role=patient')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (data.unread_count > 0) {
                            notifBadge.textContent = data.unread_count;
                            notifBadge.classList.remove('hidden');
                        } else {
                            notifBadge.classList.add('hidden');
                        }

                        if (data.notifications.length > 0) {
                            notifList.innerHTML = data.notifications.map(n => `
                                <div class="p-4 ${n.status === 'unread' ? 'bg-blue-50/50' : ''} hover:bg-slate-50 transition-colors">
                                    <div class="flex justify-between items-start mb-1">
                                        <h5 class="text-xs font-black text-slate-900">${n.title}</h5>
                                        <span class="text-[9px] font-bold text-slate-400">${new Date(n.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                    </div>
                                    <p class="text-[10px] font-medium text-slate-600 leading-relaxed">${n.message}</p>
                                    ${n.action_url ? `<a href="${n.action_url}" class="text-[9px] font-black text-blue-600 uppercase mt-2 block hover:underline">View Details</a>` : ''}
                                </div>
                            `).join('');
                        } else {
                            notifList.innerHTML = '<div class="p-8 text-center text-slate-400 font-bold italic text-xs">No notifications.</div>';
                        }
                    }
                });
        }

        function markAllRead() {
            fetch('api/notifications.php?action=mark_read&role=patient', { method: 'POST' })
                .then(() => {
                    notifBadge.classList.add('hidden');
                    fetchNotifications();
                });
        }

        // Poll for notifications (kept as fallback, but sync handles the trigger)
        setInterval(fetchNotifications, 30000);
        fetchNotifications();

        // Real-Time Sync Subscriptions
        let reloadTimeout = null;
        function throttledReload() {
            if (reloadTimeout) return;
            reloadTimeout = setTimeout(() => {
                location.reload();
            }, 2000);
        }

        if (window.HospitalSync) {
            window.HospitalSync.subscribe('notifications', (signal) => {
                console.log('📡 [Patient Hub] New Notification Signal');
                fetchNotifications();
            });
            window.HospitalSync.subscribe('lab_requests', (signal) => {
                console.log('📡 [Patient Hub] Lab Result Signal');
                throttledReload(); // Refresh to update labs/timeline
            });
            window.HospitalSync.subscribe('prescriptions', (signal) => {
                console.log('📡 [Patient Hub] New Prescription Signal');
                throttledReload();
            });
            window.HospitalSync.subscribe('billing', (signal) => {
                console.log('📡 [Patient Hub] Billing Update Signal');
                throttledReload();
            });
        }

        function payOnline(invoiceId) {
            const btn = document.getElementById('btn-dash-' + invoiceId);
            const originalText = btn.innerText;
            
            btn.innerText = 'Initializing...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('invoice_id', invoiceId);

            fetch('api/initialize_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = data.authorization_url;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Payment Error',
                        text: data.message || 'Could not initialize payment.'
                    });
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Debug Error: ' + error);
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'An unexpected error occurred. Please try again later.'
                });
                btn.innerText = originalText;
                btn.disabled = false;
            });
        }

        // Init
        lucide.createIcons();
        fetchVitals();
    </script>
</body>
</html>