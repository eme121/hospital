<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/clinical_helper.php';

if (!isset($_SESSION['doctor_id'])) {
    header('Location: staff_login.php');
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];
$doctor_pix = $_SESSION['doctor_pix'];
$doctor_type = $_SESSION['doctor_type'] ?? 'telemedicine';

// Find associated IDs
$email_sql = ($doctor_type === 'telemedicine') ? "SELECT email FROM telemedicine_doctors WHERE id = ?" : "SELECT email FROM doctors WHERE id = ?";
$email_stmt = $conn->prepare($email_sql);
$email_stmt->bind_param("i", $doctor_id);
$email_stmt->execute();
$doctor_email = $email_stmt->get_result()->fetch_assoc()['email'] ?? '';

$all_doctor_ids = [$doctor_id];
if ($doctor_email) {
    $ids_res = $conn->query("SELECT id FROM doctors WHERE email = '$doctor_email' UNION SELECT id FROM telemedicine_doctors WHERE email = '$doctor_email'");
    while($row = $ids_res->fetch_assoc()) { $all_doctor_ids[] = (int)$row['id']; }
}
$ids_list = implode(',', array_unique($all_doctor_ids));

// Stats
$pending_consults = $conn->query("SELECT COUNT(*) as total FROM telemedicine_appointments WHERE (doctor_id IN ($ids_list)) AND appointment_date = CURDATE() AND status = 'Confirmed'")->fetch_assoc()['total'];
$active_cases = $conn->query("SELECT COUNT(*) as total FROM telemedicine_cases WHERE status != 'Closed'")->fetch_assoc()['total'];

$view = $_GET['view'] ?? 'dashboard';
$case_filter = $_GET['status'] ?? 'Open';
$show_dismissed_visits = isset($_GET['show_dismissed_visits']) && $_GET['show_dismissed_visits'] == 1;

// Consultations (Filtered by View - Unified Physical & Virtual)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$consult_limit = ($view === 'visits') ? 30 : 10;
$offset = ($page - 1) * $consult_limit;

$archived_filter = $show_dismissed_visits ? "" : "AND a.is_archived = 0";
$ta_archived_filter = $show_dismissed_visits ? "" : "AND ta.is_archived = 0";

$appt_sql = "
    (SELECT a.id, a.patient_id, a.doctor_id, a.appointment_date, a.appointment_time, a.status, p.full_name as patient_name, de.name as department_name, 'Physical' as type, a.is_paid as is_paid, a.is_archived
     FROM appointments a 
     JOIN patients p ON a.patient_id = p.id
     LEFT JOIN departments de ON a.department_id = de.id 
     WHERE (a.doctor_id IN ($ids_list)) AND a.status != 'Cancelled' $archived_filter)
    UNION ALL
    (SELECT ta.id, ta.patient_id, ta.doctor_id, ta.appointment_date, ta.appointment_time, ta.status, ta.patient_name, de.name as department_name, 'Virtual' as type, ta.is_paid as is_paid, ta.is_archived
     FROM telemedicine_appointments ta 
     LEFT JOIN departments de ON ta.department_id = de.id 
     WHERE (ta.doctor_id IN ($ids_list)) AND ta.status != 'Cancelled' $ta_archived_filter)
    ORDER BY appointment_date DESC, appointment_time DESC LIMIT $consult_limit OFFSET $offset";
$consultations = $conn->query($appt_sql);

// Calculate Total Pages for Consultation Pagination
$total_res = $conn->query("SELECT (
    (SELECT COUNT(*) FROM appointments a WHERE (a.doctor_id IN ($ids_list)) AND a.status != 'Cancelled' $archived_filter) +
    (SELECT COUNT(*) FROM telemedicine_appointments ta WHERE (ta.doctor_id IN ($ids_list)) AND ta.status != 'Cancelled' $ta_archived_filter)
) as total");
$total_res_data = $total_res ? $total_res->fetch_assoc() : ['total' => 0];
$total_consults = $total_res_data['total'];
$total_pages = ceil($total_consults / $consult_limit);

// Physical Patient Queue (New) - Centralized via ClinicalHelper to avoid duplication
$physical_queue = ClinicalHelper::getPatientQueue($conn, 'Doctor', ['Consultation', 'Lab'], $doctor_id);

// Recent Cases (Board Room Aware - Filtered by Dismissed status unless viewing specific category)
$show_dismissed = isset($_GET['show_dismissed']) && $_GET['show_dismissed'] == 1;
$case_status_tab = $_GET['case_status'] ?? 'Open'; // Default to Open
$cases = ClinicalHelper::getDoctorCases($conn, $ids_list, $case_status_tab, $show_dismissed);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Command Center | Hope Haven</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Real-Time Sync Engine -->
    <script> window.APP_BASE_URL = '<?php echo BASE_URL; ?>'; </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/sync_engine.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/telemedicine_signals.js?v=<?php echo time(); ?>"></script>

    <style>
        :root {
            --war-bg: #020617;
            --war-panel: #0f172a;
            --war-border: #1e293b;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--war-bg); color: #f1f5f9; }
        .sidebar { background-color: var(--war-panel); border-right: 1px solid var(--war-border); }
        .sidebar-item.active { background-color: #1e293b; color: #38bdf8; border-right: 4px solid #38bdf8; }
        .war-card { background-color: var(--war-panel); border: 1px solid var(--war-border); border-radius: 24px; transition: all 0.3s ease; }
        .war-card:hover { border-color: #38bdf8; transform: translateY(-2px); }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; height: 100vh; width: 280px; }
            .sidebar.open { transform: translateX(0); box-shadow: 20px 0 50px rgba(0,0,0,0.5); }
            #sidebar-overlay.show { display: block; }
        }
        @media (min-width: 769px) {
            .sidebar { width: 280px; position: relative; transform: none; }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--war-bg); }
        ::-webkit-scrollbar-thumb { background: var(--war-border); border-radius: 10px; }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden">

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-50 sidebar transition-transform duration-300 md:relative md:transform-none">
        <div class="p-6 flex flex-col h-full">
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center text-slate-900 font-black">C</div>
                <h1 class="text-lg font-black text-white tracking-tighter uppercase">Command<span class="text-sky-500">Center</span></h1>
            </div>

            <nav class="space-y-1 flex-1">
                <a href="telemedicine_dashboard.php?view=dashboard" class="sidebar-item <?php echo $view === 'dashboard' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-xs text-slate-400 hover:bg-slate-800 transition-all uppercase tracking-widest">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                </a>
                <a href="?view=visits" class="sidebar-item <?php echo $view === 'visits' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-xs text-slate-400 hover:bg-slate-800 transition-all uppercase tracking-widest">
                    <i data-lucide="video" class="w-4 h-4"></i> Virtual Visits
                </a>
                <a href="?view=cases" class="sidebar-item <?php echo $view === 'cases' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-xs text-slate-400 hover:bg-slate-800 transition-all uppercase tracking-widest">
                    <i data-lucide="folder-kanban" class="w-4 h-4"></i> Clinical Cases
                </a>
                <a href="doctor_settings.php" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-xs text-slate-400 hover:bg-slate-800 transition-all uppercase tracking-widest">
                    <i data-lucide="settings" class="w-4 h-4"></i> Settings
                </a>
            </nav>

            <div class="mt-auto pt-6 border-t border-slate-800">
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-xs text-rose-400 hover:bg-rose-500/10 transition-all uppercase tracking-widest">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Terminate Session
                </a>
            </div>
        </div>
    </aside>

    <!-- Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden transition-opacity duration-300"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="bg-[#0f172a] border-b border-slate-800 h-16 flex items-center justify-between px-4 md:px-6 shrink-0">
            <div class="flex items-center gap-2 md:gap-4">
                <button id="mobile-toggle" class="md:hidden p-2 text-slate-400 hover:text-white transition-colors"><i data-lucide="menu"></i></button>
                <h2 class="hidden sm:block text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Operational Overview</h2>
            </div>
            <div class="flex items-center gap-3 md:gap-6">
                <!-- Notifications -->
                <div class="relative">
                    <button id="notif-toggle" class="p-2 bg-slate-800 rounded-xl text-slate-400 hover:text-sky-400 transition-all relative">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span id="notif-badge" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-rose-500 rounded-full border-2 border-[#0f172a] flex items-center justify-center text-[8px] font-black text-white">0</span>
                    </button>
                    <!-- Notification Dropdown -->
                    <div id="notif-dropdown" class="hidden absolute right-0 mt-4 w-72 md:w-80 bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-[100] overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                        <div class="p-5 border-b border-slate-800 flex justify-between items-center bg-slate-800/50">
                            <h3 class="text-[10px] font-black text-white uppercase tracking-widest">Clinical Alerts</h3>
                            <button onclick="markAllRead()" class="text-[9px] font-bold text-slate-500 hover:text-white uppercase tracking-tighter">Clear All</button>
                        </div>
                        <div id="notif-list" class="max-h-96 overflow-y-auto divide-y divide-slate-800/50">
                            <div class="p-8 text-center">
                                <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest italic">No unread alerts</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="syncStatus" class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="hidden lg:block text-[10px] font-black text-emerald-500 uppercase">System Linked</span>
                </div>
                <div class="flex items-center gap-3 border-l border-slate-800 pl-4 md:pl-6">
                    <div class="hidden md:block text-right">
                        <p class="text-[10px] font-black text-white uppercase tracking-wider">Dr. <?php echo htmlspecialchars($doctor_name); ?></p>
                        <p class="text-[9px] font-bold text-sky-500 uppercase tracking-tighter">On-Call Specialist</p>
                    </div>
                    <img src="<?php echo $doctor_pix; ?>" class="w-8 h-8 md:w-9 md:h-9 rounded-xl object-cover border border-slate-700">
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 space-y-10">
            <!-- Stats -->
            <?php if ($view === 'dashboard'): ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="war-card p-6 bg-gradient-to-br from-sky-600 to-blue-700 border-0 shadow-lg shadow-sky-900/20">
                    <p class="text-[10px] font-black text-sky-100 uppercase tracking-widest mb-1">Assigned Tele-Visits</p>
                    <h4 class="text-3xl font-black text-white"><?php echo $pending_consults; ?></h4>
                </div>
                <div class="war-card p-6">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Active Collaborative Cases</p>
                    <h4 class="text-3xl font-black text-white"><?php echo $active_cases; ?></h4>
                </div>
                <div class="war-card p-6">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">System Health</p>
                    <h4 class="text-3xl font-black text-emerald-500 uppercase tracking-tighter">Optimal</h4>
                </div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 <?php echo $view === 'dashboard' ? 'lg:grid-cols-3' : ''; ?> gap-10">
                
                <!-- Physical Queue (New) -->
                <?php if ($view === 'dashboard'): ?>
                <div class="war-card overflow-hidden flex flex-col max-h-[700px]">
                    <div class="p-6 border-b border-slate-800 bg-slate-900/50 shrink-0">
                        <div class="flex justify-between items-center">
                            <h4 class="text-[11px] font-black text-white uppercase tracking-widest flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-emerald-500"></i> Physical Queue
                            </h4>
                            <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-500 rounded text-[8px] font-black uppercase tracking-tighter">Live</span>
                        </div>
                    </div>
                    <div class="p-6 space-y-4 overflow-y-auto custom-scrollbar flex-1">
                        <?php if($physical_queue->num_rows == 0): ?>
                            <div class="p-12 text-center text-slate-600 font-bold uppercase text-[10px] tracking-widest italic border border-dashed border-slate-800 rounded-2xl">Queue is currently empty</div>
                        <?php else: while($q = $physical_queue->fetch_assoc()): 
                            $pay_status = $q['payment_status'] ?? 'Pending';
                            $pay_color = $pay_status === 'Paid' ? 'bg-emerald-500' : 'bg-rose-500';
                        ?>
                            <div class="p-4 bg-slate-900/50 rounded-2xl border border-slate-800 hover:border-emerald-500 transition-all group">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <p class="text-xs font-black text-white uppercase tracking-tight"><?php echo htmlspecialchars($q['patient_name']); ?></p>
                                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter"><?php echo $q['file_number']; ?> • <?php echo $q['gender']; ?>, <?php echo $q['age']; ?>y</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="w-2 h-2 rounded-full <?php echo $pay_color; ?> animate-pulse" title="Payment: <?php echo $pay_status; ?>"></span>
                                        <span class="text-[8px] font-black text-slate-500 uppercase tracking-tighter"><?php echo date('h:i A', strtotime($q['updated_at'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <?php if($pay_status === 'Paid'): ?>
                                        <button onclick="startConsultation(<?php echo $q['patient_id']; ?>)" class="flex-1 py-2 bg-emerald-600 text-white rounded-lg font-black text-[8px] uppercase tracking-widest hover:bg-emerald-500 transition-all">Attend Now</button>
                                    <?php else: ?>
                                        <button onclick="bypassPayment(<?php echo $q['patient_id']; ?>)" class="flex-1 py-2 bg-slate-800 text-slate-400 rounded-lg font-black text-[8px] uppercase tracking-widest hover:text-white transition-all border border-slate-700">Bypass & Attend</button>
                                    <?php endif; ?>
                                    <button class="p-2 bg-slate-800 text-slate-400 rounded-lg hover:text-white transition-all border border-slate-700">
                                        <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Collaborative Cases -->
                <?php if ($view === 'dashboard' || $view === 'cases'): ?>
                <div class="war-card overflow-hidden flex flex-col <?php echo $view === 'dashboard' ? 'max-h-[700px]' : 'min-h-[600px]'; ?>">
                    <div class="p-6 border-b border-slate-800 bg-slate-900/50 shrink-0">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="text-[11px] font-black text-white uppercase tracking-widest flex items-center gap-2">
                                <i data-lucide="folder-kanban" class="w-4 h-4 text-sky-500"></i> Clinical Cases
                            </h4>
                            <div class="flex gap-2">
                                <?php if($show_dismissed): ?>
                                    <a href="?view=<?php echo $view; ?>&case_status=<?php echo $case_status_tab; ?>&show_dismissed=0" class="px-3 py-1.5 bg-slate-800 text-slate-400 rounded-lg font-bold text-[9px] uppercase tracking-tighter hover:text-white transition-all border border-slate-700">Hide Dismissed</a>
                                <?php else: ?>
                                    <a href="?view=<?php echo $view; ?>&case_status=<?php echo $case_status_tab; ?>&show_dismissed=1" class="px-3 py-1.5 bg-slate-800 text-slate-400 rounded-lg font-bold text-[9px] uppercase tracking-tighter hover:text-white transition-all border border-slate-700">Show Dismissed</a>
                                <?php endif; ?>
                                <button onclick="openNewCaseModal()" class="px-4 py-2 bg-sky-600 text-white rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-sky-500 transition-all shadow-lg shadow-sky-900/20">+ Initiate</button>
                            </div>
                        </div>
                        
                        <!-- Mini Tabs -->
                        <div class="flex gap-2 p-1 bg-slate-950 rounded-xl border border-slate-800">
                            <a href="?view=<?php echo $view; ?>&case_status=Open&show_dismissed=<?php echo $show_dismissed ? 1 : 0; ?>" class="flex-1 text-center py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all <?php echo $case_status_tab === 'Open' ? 'bg-sky-500 text-slate-900' : 'text-slate-500 hover:text-white'; ?>">Open Cases</a>
                            <a href="?view=<?php echo $view; ?>&case_status=Closed&show_dismissed=<?php echo $show_dismissed ? 1 : 0; ?>" class="flex-1 text-center py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all <?php echo $case_status_tab === 'Closed' ? 'bg-sky-500 text-slate-900' : 'text-slate-500 hover:text-white'; ?>">Closed</a>
                        </div>
                    </div>

                    <div class="p-6 space-y-4 overflow-y-auto custom-scrollbar flex-1">
                        <?php if($cases->num_rows == 0): ?>
                            <div class="p-12 text-center text-slate-600 font-bold uppercase text-[10px] tracking-widest italic border border-dashed border-slate-800 rounded-2xl">No <?php echo $case_status_tab; ?> Cases Found</div>
                        <?php else: while($c = $cases->fetch_assoc()): 
                            $priority_color = $c['triage_priority'] === 'Emergency' ? 'text-rose-500' : ($c['triage_priority'] === 'High' ? 'text-amber-500' : 'text-sky-400');
                        ?>
                            <div class="flex items-center justify-between p-4 bg-slate-900/50 rounded-2xl border border-slate-800 hover:border-sky-500 transition-all group relative <?php echo $c['is_dismissed'] ? 'opacity-50 grayscale' : ''; ?>">
                                <a href="telemedicine_case.php?id=<?php echo $c['id']; ?>" class="flex items-center gap-4 flex-1 <?php echo $c['invitation_status'] === 'pending' ? 'opacity-80' : ''; ?>">
                                    <div class="w-10 h-10 bg-slate-800 rounded-xl flex items-center justify-center text-slate-500 font-black text-base border border-slate-700"><?php echo substr($c['patient_name_or_id'], 0, 1); ?></div>
                                    <div>
                                        <p class="text-xs font-black text-white group-hover:text-sky-400 transition-colors uppercase tracking-tight"><?php echo htmlspecialchars($c['patient_name_or_id']); ?></p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[8px] font-black <?php echo $priority_color; ?> uppercase tracking-tighter"><?php echo $c['triage_priority']; ?></span>
                                            <span class="text-[8px] font-black text-slate-500 uppercase tracking-tighter">#<?php echo $c['id']; ?></span>
                                            <?php if($c['invitation_status'] === 'pending'): ?>
                                                <span class="text-[8px] font-black text-amber-500 uppercase tracking-tighter ml-2 bg-amber-500/10 px-2 py-0.5 rounded italic">Invite Pending</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                                
                                <div class="flex items-center gap-2">
                                    <?php if($c['invitation_status'] === 'pending'): ?>
                                        <button onclick="acceptInvitation(<?php echo $c['id']; ?>)" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg font-black text-[8px] uppercase tracking-widest hover:bg-emerald-500 transition-all shadow-lg shadow-emerald-900/20">Accept</button>
                                    <?php endif; ?>
                                    
                                    <!-- Dismiss Button -->
                                    <button onclick="dismissCase(<?php echo $c['id']; ?>, <?php echo $c['is_dismissed'] ? 0 : 1; ?>)" class="p-2 text-slate-600 hover:text-rose-500 transition-colors" title="<?php echo $c['is_dismissed'] ? 'Restore to Dashboard' : 'Dismiss from Dashboard'; ?>">
                                        <i data-lucide="<?php echo $c['is_dismissed'] ? 'eye' : 'eye-off'; ?>" class="w-4 h-4"></i>
                                    </button>

                                    <a href="telemedicine_case.php?id=<?php echo $c['id']; ?>" class="p-2 text-slate-700 hover:text-sky-400 group-hover:translate-x-1 transition-all">
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Virtual Visits -->
                <?php if ($view === 'dashboard' || $view === 'visits'): ?>
                <div class="war-card overflow-hidden flex flex-col <?php echo $view === 'dashboard' ? 'max-h-[700px]' : 'min-h-[600px]'; ?>">
                    <div class="p-6 border-b border-slate-800 bg-slate-900/50 shrink-0">
                        <div class="flex justify-between items-center">
                            <h4 class="text-[11px] font-black text-white uppercase tracking-widest flex items-center gap-2">
                                <i data-lucide="video" class="w-4 h-4 text-sky-500"></i> Upcoming Visits
                            </h4>
                            <div class="flex items-center gap-3">
                                <?php if($show_dismissed_visits): ?>
                                    <a href="?view=<?php echo $view; ?>&show_dismissed_visits=0" class="text-[9px] font-bold text-slate-400 hover:text-white uppercase tracking-tighter">Hide Dismissed</a>
                                <?php else: ?>
                                    <a href="?view=<?php echo $view; ?>&show_dismissed_visits=1" class="text-[9px] font-bold text-slate-400 hover:text-white uppercase tracking-tighter">Show Dismissed</a>
                                <?php endif; ?>
                                <?php if($view === 'dashboard'): ?>
                                    <a href="?view=visits" class="text-[9px] font-black text-sky-500 uppercase hover:underline tracking-widest">Full Schedule</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-800 overflow-y-auto custom-scrollbar flex-1">
                        <?php if($consultations->num_rows == 0): ?>
                            <div class="p-12 text-center text-slate-600 font-bold uppercase text-[10px] tracking-widest italic border border-dashed border-slate-800 rounded-2xl m-6">No scheduled visits</div>
                        <?php else: while($appt = $consultations->fetch_assoc()): ?>
                            <div class="p-6 flex items-center justify-between hover:bg-slate-800/30 transition-colors <?php echo $appt['is_archived'] ? 'opacity-50 grayscale bg-slate-950/50' : ''; ?>">
                                <div class="flex items-center gap-5">
                                    <div class="w-10 h-10 bg-sky-500/10 text-sky-500 rounded-xl flex items-center justify-center font-black text-[10px] uppercase border border-sky-500/20"><?php echo date('M d', strtotime($appt['appointment_date'])); ?></div>
                                    <div>
                                        <p class="text-sm font-black text-white"><?php echo htmlspecialchars($appt['patient_name']); ?></p>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1"><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?> • <?php echo $appt['department_name'] ?? 'Telemedicine'; ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php if ($appt['is_archived']): ?>
                                        <button onclick="manageAppointment(<?php echo $appt['id']; ?>, '<?php echo $appt['type']; ?>', 'Restore')" class="p-3 text-emerald-500 hover:bg-emerald-500/10 rounded-xl transition-all" title="Restore to Dashboard">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>
                                    <?php elseif ($appt['status'] === 'Accepted' || $appt['status'] === 'Confirmed'): ?>
                                        <div class="flex gap-1">
                                            <a href="https://meet.jit.si/HopeHaven-Huddle-Case-<?php echo $appt['id']; ?>-<?php echo substr(md5($appt['appointment_date']), 0, 8); ?>" target="_blank" class="p-3 bg-sky-600 text-white rounded-xl hover:bg-sky-500 transition-all shadow-lg shadow-sky-900/20">
                                                <i data-lucide="video" class="w-4 h-4"></i>
                                            </a>
                                            <button onclick="manageAppointment(<?php echo $appt['id']; ?>, '<?php echo $appt['type']; ?>', 'Dismissed')" class="p-3 text-slate-500 hover:text-rose-400 transition-colors" title="Dismiss from view">
                                                <i data-lucide="eye-off" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    <?php elseif ($appt['status'] === 'Pending'): ?>
                                        <div class="flex gap-1">
                                            <button onclick="manageAppointment(<?php echo $appt['id']; ?>, 'Virtual', 'Accepted')" class="px-3 py-1.5 bg-emerald-500/10 text-emerald-500 rounded-lg text-[8px] font-black uppercase tracking-widest border border-emerald-500/20 hover:bg-emerald-500 hover:text-white transition-all">Accept</button>
                                            <button onclick="manageAppointment(<?php echo $appt['id']; ?>, 'Virtual', 'Cancelled')" class="px-3 py-1.5 bg-rose-500/10 text-rose-500 rounded-lg text-[8px] font-black uppercase tracking-widest border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all">Decline</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-black uppercase px-3 py-1.5 bg-slate-800 text-slate-500 rounded-lg border border-slate-700 tracking-widest"><?php echo $appt['status']; ?></span>
                                            <button onclick="manageAppointment(<?php echo $appt['id']; ?>, '<?php echo $appt['type']; ?>', 'Dismissed')" class="p-2 text-slate-600 hover:text-rose-400 transition-colors" title="Dismiss">
                                                <i data-lucide="eye-off" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; endif; ?>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                    <div class="p-4 border-t border-slate-800 bg-slate-900/30 shrink-0">
                        <div class="flex items-center justify-between">
                            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Page <?php echo $page; ?> of <?php echo $total_pages; ?></p>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?view=<?php echo $view; ?>&page=<?php echo $page - 1; ?>" class="px-3 py-1 bg-slate-800 text-slate-400 rounded-lg hover:text-white transition-all border border-slate-700 text-[10px] font-black uppercase tracking-tighter flex items-center gap-1">
                                        <i data-lucide="chevron-left" class="w-3 h-3"></i> Prev
                                    </a>
                                <?php endif; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?view=<?php echo $view; ?>&page=<?php echo $page + 1; ?>" class="px-3 py-1 bg-slate-800 text-slate-400 rounded-lg hover:text-white transition-all border border-slate-700 text-[10px] font-black uppercase tracking-tighter flex items-center gap-1">
                                        Next <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <!-- New Case Modal -->
    <div id="newCaseModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
        <div class="bg-white rounded-[40px] w-full max-w-xl p-10 shadow-2xl">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-900">Initiate New Case</h3>
                    <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mt-1">Specialist collaboration</p>
                </div>
                <button onclick="closeNewCaseModal()" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form id="newCaseForm" class="space-y-6 text-slate-900">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Select Registered Patient</label>
                    <select name="patient_id" id="patientSelect" class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 font-bold outline-none focus:ring-2 focus:ring-blue-500 text-slate-900">
                        <option value="0">-- Search Patient --</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Patient Name/ID (if not listed)</label>
                    <input type="text" name="patient_name_or_id" id="patientManual" placeholder="External Patient Reference" class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 font-bold outline-none focus:ring-2 focus:ring-blue-500 text-slate-900">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Initial Symptoms</label>
                    <textarea name="symptoms" rows="3" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium text-slate-900" placeholder="Describe the current clinical presentation..."></textarea>
                </div>

                <div class="flex gap-4">
                    <button type="button" onclick="closeNewCaseModal()" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black">Cancel</button>
                    <button type="submit" class="flex-2 px-10 py-4 bg-blue-600 text-white rounded-2xl font-black shadow-lg shadow-blue-200">Start Discussion</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clinical Debug Console -->
    <div id="debugConsole" class="fixed bottom-10 left-4 w-64 bg-slate-900/90 border border-slate-700 rounded-xl p-3 z-[2500] font-mono text-[9px] text-slate-300 pointer-events-none opacity-50">
        <div class="flex justify-between items-center mb-2 border-b border-slate-800 pb-1">
            <span class="text-sky-500 font-bold uppercase">Dashboard Signaling</span>
        </div>
        <div id="debugOutput" class="space-y-1 max-h-32 overflow-hidden text-[8px]">
            <div class="text-slate-500 italic">Hub Monitoring Active...</div>
        </div>
    </div>

    <script>
        function clinicalLog(msg, color = 'text-slate-400') {
            const out = document.getElementById('debugOutput');
            const entry = document.createElement('div');
            entry.className = color;
            entry.textContent = `[${new Date().toLocaleTimeString([], {hour12:false})}] ${msg}`;
            out.prepend(entry);
            if(out.children.length > 10) out.lastElementChild.remove();
        }

        document.getElementById('mobile-toggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebar-overlay').classList.toggle('hidden');
        });

        document.getElementById('sidebar-overlay').addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        });

        // --- Notifications Logic ---
        const notifToggle = document.getElementById('notif-toggle');
        const notifDropdown = document.getElementById('notif-dropdown');
        const notifList = document.getElementById('notif-list');
        const notifBadge = document.getElementById('notif-badge');
        let lastNotifCount = 0;

        if (notifToggle) {
            notifToggle.addEventListener('click', () => {
                notifDropdown.classList.toggle('hidden');
                if (!notifDropdown.classList.contains('hidden')) fetchNotifications();
            });
        }

        function fetchNotifications() {
            fetch('api/notifications.php?action=get&role=doctor')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (data.unread_count > lastNotifCount) {
                            // SHOW POPUP FOR DOCTOR
                            if (data.notifications.length > 0) {
                                const latest = data.notifications[0];
                                Swal.fire({
                                    title: latest.title,
                                    text: latest.message,
                                    icon: 'info',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 5000,
                                    timerProgressBar: true,
                                    customClass: { popup: 'toast-notification' },
                                    didOpen: (toast) => {
                                        toast.addEventListener('mouseenter', Swal.stopTimer);
                                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                                        toast.addEventListener('click', () => {
                                            if(latest.action_url) window.location.href = latest.action_url;
                                        });
                                    }
                                });
                                // Play subtle notification sound
                                const audio = new Audio('https://www.soundjay.com/buttons/beep-07a.mp3');
                                audio.play().catch(e => console.log("Sound blocked"));
                            }
                        }
                        lastNotifCount = data.unread_count;

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
                            notifList.innerHTML = '<div class="p-8 text-center text-slate-400 font-bold italic text-xs">No alerts.</div>';
                        }
                    }
                });
        }

        function manageAppointment(id, type, status) {
            if (!confirm(`Are you sure you want to mark this appointment as ${status}?`)) return;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('type', type);
            formData.append('status', status);

            fetch('api/manage_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }

        function markAllRead() {
            fetch('api/notifications.php?action=mark_read&role=doctor', { method: 'POST' })
                .then(() => {
                    notifBadge.classList.add('hidden');
                    fetchNotifications();
                });
        }

        setInterval(fetchNotifications, 30000);
        fetchNotifications();

        // Real-Time Sync Subscriptions
        if (window.HospitalSync) {
            window.HospitalSync.subscribe('patient_queue', (signal) => {
                if (signal.is_global) return; // Ignore global registry updates, wait for granular signal
                if (signal.sender_id == <?php echo $doctor_id; ?>) return;
                console.log('📡 [Doctor Hub] Patient Queue Updated');
                location.reload(); 
            });
            window.HospitalSync.subscribe('notifications', (signal) => {
                console.log('📡 [Doctor Hub] New Notification Received');
                fetchNotifications();
            });
            window.HospitalSync.subscribe('telemedicine_cases', (signal) => {
                if (signal.is_global) return; // Ignore global registry updates
                if (signal.sender_id == <?php echo $doctor_id; ?>) return;
                console.log('📡 [Doctor Hub] Case Discussions Updated');
                location.reload();
            });
            window.HospitalSync.subscribe('telemedicine_chat', (signal) => {
                console.log('📡 [Doctor Hub] RAW SIGNAL:', signal);
                if (signal.signal_type === 'HUDDLE_START' || signal.signal_type === 'AUDIO_START' || signal.signal_type === 'WEBRTC_OFFER') {
                    if (signal.sender_id == <?php echo $doctor_id; ?>) return;

                    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3');
                    audio.play().catch(e => console.log("Sound blocked by browser"));
                    
                    const initiator = signal.sender_name || 'Dr. Specialist';
                    const isNative = signal.signal_type === 'WEBRTC_OFFER' || signal.signal_type === 'AUDIO_START';
                    
                    Swal.fire({
                        title: isNative ? 'Incoming Audio Call' : 'Incoming Clinical Huddle',
                        text: `${initiator} has started a live ${isNative ? 'audio consultation' : 'huddle'} for Case #${signal.data_id}.`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: isNative ? 'Answer Call' : 'Join Huddle Now',
                        cancelButtonText: 'Ignore',
                        confirmButtonColor: isNative ? '#10b981' : '#0ea5e9',
                        backdrop: `rgba(0,123,255,0.1)`
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.href = `telemedicine_case.php?id=${signal.data_id}&autojoin=true&mode=${isNative?'audio':'video'}`;
                        }
                    });
                }
            });
            // Start AFTER all subscriptions are set
            window.HospitalSync.start();
        }

        function startVideoCall(room) {
            // Switched to meet.ffmuc.net to avoid meet.jit.si 5-minute guest limit
            const url = `https://meet.ffmuc.net/${room}`;
            window.open(url, '_blank', 'width=1000,height=800');
        }

        // --- New Case Logic ---
        function openNewCaseModal() {
            const modal = document.getElementById('newCaseModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Load patients if select is empty
            const select = document.getElementById('patientSelect');
            if (select.options.length <= 1) {
                fetch('api/telemedicine_cases.php?action=get_patients')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            data.patients.forEach(p => {
                                const opt = document.createElement('option');
                                opt.value = p.id;
                                opt.textContent = `${p.full_name} (${p.email})`;
                                select.appendChild(opt);
                            });
                        }
                    });
            }
        }

        function closeNewCaseModal() {
            const modal = document.getElementById('newCaseModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('newCaseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Handle manual name if select is 0
            const patientId = document.getElementById('patientSelect').value;
            const manualName = document.getElementById('patientManual').value;
            if (patientId == "0" && !manualName) {
                alert("Please select a patient or enter a name manually.");
                return;
            }

            if (patientId != "0") {
                const select = document.getElementById('patientSelect');
                formData.set('patient_name_or_id', select.options[select.selectedIndex].text.split(' (')[0]);
            }

            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Initiating...';

            fetch('api/telemedicine_cases.php?action=create', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = `telemedicine_case.php?id=${data.case_id}`;
                } else {
                    alert(data.message);
                    btn.disabled = false;
                    btn.textContent = 'Start Discussion';
                }
            });
        });

        function acceptInvitation(caseId) {
            const fd = new FormData();
            fd.append('case_id', caseId);

            fetch('api/telemedicine_cases.php?action=accept_invitation', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Joined Case',
                        text: 'You have successfully joined the clinical board.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: { popup: 'toast-notification' }
                    }).then(() => {
                        window.location.href = `telemedicine_case.php?id=${caseId}`;
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }

        function startConsultation(patientId) {
            window.location.href = `consultation.php?patient_id=${patientId}`;
        }

        function dismissCase(caseId, dismissState) {
            const fd = new FormData();
            fd.append('case_id', caseId);
            fd.append('dismiss', dismissState);

            fetch('api/telemedicine_cases.php?action=dismiss_case', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }

        function bypassPayment(patientId) {
            Swal.fire({
                title: 'Authorize Clinical Bypass?',
                text: "You are about to proceed with the consultation before payment is confirmed. This should only be done for clinical urgency.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Proceed Anyway',
                customClass: { popup: 'toast-notification' }
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('patient_id', patientId);
                    
                    fetch('api/doctor_actions.php?action=bypass_payment', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Authorized',
                                text: data.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: { popup: 'toast-notification' }
                            }).then(() => {
                                window.location.href = `consultation.php?patient_id=${patientId}`;
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
                }
            });
        }

        lucide.createIcons();

        // --- Doctor Calendar Initialization ---
        <?php if ($view === 'calendar'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('doctor-calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                events: 'api/get_doctor_calendar_events.php',
                height: 'auto',
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                eventClassNames: function(arg) {
                    return ['cursor-pointer', 'font-bold', 'text-xs', 'rounded-lg', 'border-0', 'shadow-sm'];
                },
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    Swal.fire({
                        title: info.event.title,
                        html: `
                            <div class="text-left space-y-4 p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full ${props.type === 'Physical' ? 'bg-blue-500' : 'bg-indigo-500'}"></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">${props.type} Consultation</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Schedule</p>
                                    <p class="font-bold text-slate-900">${new Date(info.event.start).toLocaleString([], {weekday: 'long', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit'})}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Status</p>
                                    <span class="px-3 py-1 bg-slate-100 rounded-full text-[10px] font-black uppercase tracking-wider text-slate-600">${props.status}</span>
                                </div>
                            </div>
                        `,
                        showConfirmButton: props.type === 'Virtual',
                        confirmButtonText: 'Start Video Call',
                        confirmButtonColor: '#2563eb',
                        customClass: { popup: 'toast-notification' }
                    }).then((result) => {
                        if (result.isConfirmed && props.type === 'Virtual') {
                            startVideoCall(window.btoa(info.event.id));
                        }
                    });
                }
            });
            calendar.render();
        });
        <?php endif; ?>
    </script>
</body>
</html>