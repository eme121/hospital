<?php
session_start();
date_default_timezone_set('Africa/Lagos');
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Auto-Migration: Ensure columns exist
$tables = ['appointments', 'telemedicine_appointments'];
foreach ($tables as $table) {
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE 'is_archived'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE $table ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
    }
    
    // Update ENUM for status if needed
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE 'status'");
    if ($res && $row = $res->fetch_assoc()) {
        if (strpos($row['Type'], 'Completed') === false) {
            $new_enum = "ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed', 'No-show')";
            $conn->query("ALTER TABLE $table MODIFY COLUMN status $new_enum DEFAULT 'Pending'");
        }
    }
}

// Get filter parameters
$view = $_GET['view'] ?? 'active'; // 'active' or 'archived'
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'date';
$order = $_GET['order'] ?? 'desc';
$date_range = $_GET['date_range'] ?? '';

// Update Individual Status Logic
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['type'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $type = $_GET['type'];
    
    $status = 'Pending';
    if ($action == 'confirm') $status = ($type == 'Virtual') ? 'Accepted' : 'Confirmed';
    if ($action == 'cancel') $status = 'Cancelled';
    if ($action == 'complete') $status = 'Completed';
    if ($action == 'noshow') $status = 'No-show';
    if ($action == 'archive') {
        $table = ($type == 'Virtual') ? 'telemedicine_appointments' : 'appointments';
        $conn->query("UPDATE $table SET is_archived = 1 WHERE id = $id");
        
        // Trigger Real-Time Sync Signal
        require_once '../includes/sync_helper.php';
        SyncManager::signal('patient_queue', 'UPDATE', $id);
        
        header('Location: dashboard.php?notif=archived');
        exit;
    }

    $table = ($type == 'Virtual') ? 'telemedicine_appointments' : 'appointments';
    $sql = "UPDATE $table SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        // Trigger Real-Time Sync Signal
        require_once '../includes/sync_helper.php';
        SyncManager::signal('patient_queue', 'UPDATE', $id);

        // Fetch patient details for notification
        $get_patient = $conn->prepare("SELECT patient_id, patient_name, email, phone, appointment_date, appointment_time FROM $table WHERE id = ?");
        $get_patient->bind_param("i", $id);
        $get_patient->execute();
        $patient = $get_patient->get_result()->fetch_assoc();

        if ($patient && in_array($status, ['Confirmed', 'Cancelled', 'Accepted', 'Declined'])) {
            require_once '../includes/notifications_helper.php';
            NotificationService::setConnection($conn);

            $date = $patient['appointment_date'];
            $time = $patient['appointment_time'];
            $name = $patient['patient_name'];

            // Notify Patient
            $subj = "Appointment $status - Hope Haven Hospital";
            $msg = "Dear $name, your $type appointment for $date at $time has been $status.";
            if ($status === 'Confirmed' && $type === 'Virtual') {
                $room_id = md5($id . 'hopehaven');
                // Switched to meet.ffmuc.net to avoid meet.jit.si 5-minute guest limit
                $msg .= "\nJoin video call: https://meet.ffmuc.net/$room_id";
            }
            
            NotificationService::send('patient', $patient['patient_id'], 'appointment_update', $subj, $msg, 'patient_dashboard.php', [
                'email' => $patient['email'],
                'phone' => $patient['phone']
            ]);
        }
    }
    header('Location: dashboard.php');
    exit;
}

// Construct Query
$where_active = ($view === 'archived') ? "is_archived = 1" : "is_archived = 0";
$conditions = [$where_active];

if ($status_filter) $conditions[] = "status = '$status_filter'";
if ($search) {
    $s = $conn->real_escape_string($search);
    $conditions[] = "(patient_name LIKE '%$s%' OR email LIKE '%$s%')";
}
if ($date_range) {
    if ($date_range == 'today') $conditions[] = "appointment_date = CURDATE()";
    if ($date_range == 'week') $conditions[] = "appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    if ($date_range == 'month') $conditions[] = "appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    if ($date_range == 'old') $conditions[] = "appointment_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

if ($type_filter) {
    $conditions[] = "appt_type = '$type_filter'";
}

$where_clause = "WHERE " . implode(" AND ", $conditions);

// Sorting Logic
$sort_col = "appointment_date $order, appointment_time $order";
if ($sort == 'name') $sort_col = "patient_name $order";
if ($sort == 'doctor') $sort_col = "doctor_name $order";
if ($sort == 'status') $sort_col = "status $order";

$sql = "SELECT * FROM (
            SELECT a.id, a.patient_name, a.email, a.phone, a.appointment_date, a.appointment_time, a.status, a.is_archived, a.created_at, d.name as doctor_name, de.name as department_name, 'Physical' as appt_type 
            FROM appointments a 
            LEFT JOIN doctors d ON a.doctor_id = d.id 
            LEFT JOIN departments de ON a.department_id = de.id
            UNION ALL
            SELECT ta.id, ta.patient_name, ta.email, ta.phone, ta.appointment_date, ta.appointment_time, ta.status, ta.is_archived, ta.created_at, 
                   COALESCE(td.name, d2.name) as doctor_name, de2.name as department_name, 'Virtual' as appt_type 
            FROM telemedicine_appointments ta 
            LEFT JOIN telemedicine_doctors td ON ta.doctor_id = td.id 
            LEFT JOIN doctors d2 ON ta.doctor_id = d2.id
            LEFT JOIN departments de2 ON ta.department_id = de2.id
        ) as combined
        $where_clause
        ORDER BY $sort_col";

$result = $conn->query($sql);
if (!$result) {
    die("Database Query Failed: " . $conn->error . "<br>SQL: " . $sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Hope Haven Hospital</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include 'includes/header_scripts.php'; ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <!-- Header & Stats -->
        <div class="mb-10 flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 no-print">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">
                    <?php echo $view === 'archived' ? 'Archived Records' : 'Patient Appointments'; ?>
                </h1>
                <p class="text-slate-500 font-medium">Manage, filter and organize hospital bookings.</p>
            </div>
            
            <div class="flex flex-wrap gap-4 items-center w-full lg:w-auto">
                <!-- Search -->
                <form class="relative flex-1 md:flex-none min-w-[250px]">
                    <input type="hidden" name="view" value="<?php echo $view; ?>">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email..." 
                           class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </form>

                <div class="flex gap-2">
                    <a href="?view=<?php echo $view === 'active' ? 'archived' : 'active'; ?>" class="px-6 py-3 bg-white border border-slate-200 rounded-2xl font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 12h14m-7 4h7"></path></svg>
                        <?php echo $view === 'active' ? 'View Archive' : 'View Active'; ?>
                    </a>
                    <button onclick="window.print()" class="bg-white text-slate-600 border border-slate-200 px-6 py-3 rounded-2xl font-bold hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="mb-8 bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm flex flex-wrap items-center gap-6 no-print">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Filter By:</span>
                <select onchange="updateFilter('status', this.value)" class="bg-slate-50 border-0 rounded-xl px-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">All Status</option>
                    <option value="Pending" <?php if($status_filter == 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Confirmed" <?php if($status_filter == 'Confirmed') echo 'selected'; ?>>Confirmed</option>
                    <option value="Completed" <?php if($status_filter == 'Completed') echo 'selected'; ?>>Completed</option>
                    <option value="Cancelled" <?php if($status_filter == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                    <option value="No-show" <?php if($status_filter == 'No-show') echo 'selected'; ?>>No-show</option>
                </select>
                <select onchange="updateFilter('type', this.value)" class="bg-slate-50 border-0 rounded-xl px-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">All Types</option>
                    <option value="Physical" <?php if($type_filter == 'Physical') echo 'selected'; ?>>Physical</option>
                    <option value="Virtual" <?php if($type_filter == 'Virtual') echo 'selected'; ?>>Virtual</option>
                </select>
                <select onchange="updateFilter('date_range', this.value)" class="bg-slate-50 border-0 rounded-xl px-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Any Time</option>
                    <option value="today" <?php if($date_range == 'today') echo 'selected'; ?>>Today</option>
                    <option value="week" <?php if($date_range == 'week') echo 'selected'; ?>>Last 7 Days</option>
                    <option value="month" <?php if($date_range == 'month') echo 'selected'; ?>>Last 30 Days</option>
                    <option value="old" <?php if($date_range == 'old') echo 'selected'; ?>>Older than 30 Days</option>
                </select>
            </div>

            <div class="h-8 w-px bg-slate-100 hidden md:block"></div>

            <!-- Bulk Actions -->
            <div id="bulkActions" class="hidden flex items-center gap-2 animate-fade-in">
                <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest mr-2"><span id="selectedCount">0</span> Selected:</span>
                <?php if($view === 'active'): ?>
                    <button onclick="bulkAction('archive')" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-blue-600 transition-all">Archive</button>
                    <button onclick="bulkAction('status_update', 'Completed')" class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition-all">Mark Completed</button>
                <?php else: ?>
                    <button onclick="bulkAction('restore')" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-blue-600 transition-all">Restore</button>
                <?php endif; ?>
                <button onclick="bulkAction('delete')" class="px-4 py-2 bg-rose-50 text-rose-600 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-rose-600 hover:text-white transition-all">Delete Forever</button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
            <!-- ... table content ... -->
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse" id="appointmentsTable">
                    <!-- ... table headers ... -->
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-5 w-12 text-center no-print">
                                <input type="checkbox" id="selectAll" class="w-5 h-5 rounded-lg border-slate-200 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">
                                <a href="?sort=name&order=<?php echo $order == 'asc' ? 'desc' : 'asc'; ?>" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                    Patient Details
                                    <?php if($sort == 'name') echo $order == 'asc' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Type</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">
                                <a href="?sort=doctor&order=<?php echo $order == 'asc' ? 'desc' : 'asc'; ?>" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                    Physician
                                    <?php if($sort == 'doctor') echo $order == 'asc' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">
                                <a href="?sort=date&order=<?php echo $order == 'asc' ? 'desc' : 'asc'; ?>" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                    Schedule
                                    <?php if($sort == 'date') echo $order == 'asc' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Booked At</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">
                                <a href="?sort=status&order=<?php echo $order == 'asc' ? 'desc' : 'asc'; ?>" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                    Status
                                    <?php if($sort == 'status') echo $order == 'asc' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group appointment-row" data-id="<?php echo $row['id']; ?>" data-type="<?php echo $row['appt_type']; ?>">
                                    <td class="px-6 py-6 text-center no-print">
                                        <input type="checkbox" class="row-checkbox w-5 h-5 rounded-lg border-slate-200 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($row['patient_name']); ?></p>
                                        <p class="text-xs text-slate-400 font-medium mt-0.5"><?php echo htmlspecialchars($row['email']); ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider <?php echo ($row['appt_type'] == 'Virtual') ? 'bg-purple-50 text-purple-600 border border-purple-100' : 'bg-blue-50 text-blue-600 border border-blue-100'; ?>">
                                            <?php echo $row['appt_type']; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($row['doctor_name']); ?></p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5"><?php echo htmlspecialchars($row['department_name']); ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-sm font-bold text-slate-900"><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></p>
                                        <p class="text-xs text-slate-400 font-medium mt-0.5"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs font-bold text-slate-600"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></p>
                                        <p class="text-[10px] text-slate-400 font-medium"><?php echo date('h:i:s A', strtotime($row['created_at'])); ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <?php 
                                            $color = "bg-amber-50 text-amber-600 border-amber-100";
                                            if($row['status'] == 'Confirmed') $color = "bg-emerald-50 text-emerald-600 border-emerald-100";
                                            if($row['status'] == 'Cancelled') $color = "bg-rose-50 text-rose-600 border-rose-100";
                                            if($row['status'] == 'Completed') $color = "bg-blue-50 text-blue-600 border-blue-100";
                                            if($row['status'] == 'No-show') $color = "bg-slate-100 text-slate-600 border-slate-200";
                                        ?>
                                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $color; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 no-print">
                                        <div class="flex justify-center gap-2">
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <a href="?action=confirm&id=<?php echo $row['id']; ?>&type=<?php echo $row['appt_type']; ?>" class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Confirm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></a>
                                                <a href="?action=cancel&id=<?php echo $row['id']; ?>&type=<?php echo $row['appt_type']; ?>" class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Cancel"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg></a>
                                            <?php elseif($row['status'] == 'Confirmed'): ?>
                                                <a href="?action=complete&id=<?php echo $row['id']; ?>&type=<?php echo $row['appt_type']; ?>" class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Mark Completed"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4"></path></svg></a>
                                                <a href="?action=noshow&id=<?php echo $row['id']; ?>&type=<?php echo $row['appt_type']; ?>" class="p-2.5 bg-slate-100 text-slate-500 rounded-xl hover:bg-slate-600 hover:text-white transition-all shadow-sm" title="No-show"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></a>
                                            <?php endif; ?>
                                            
                                            <?php if($row['is_archived'] == 0): ?>
                                                <a href="?action=archive&id=<?php echo $row['id']; ?>&type=<?php echo $row['appt_type']; ?>" class="p-2.5 bg-slate-50 text-slate-400 rounded-xl hover:bg-slate-900 hover:text-white transition-all shadow-sm" title="Archive"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 12h14m-7 4h7"></path></svg></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="px-8 py-32 text-center text-slate-400 font-bold italic">No records found matching your filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
            <!-- Pharmacy Alerts -->
            <div class="bg-white rounded-[40px] border-2 border-rose-100 shadow-xl shadow-rose-50/50 overflow-hidden">
                <div class="p-10 bg-rose-50/50 border-b border-rose-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black text-rose-900">Critical Stock Alerts</h3>
                        <p class="text-xs font-bold text-rose-600 uppercase tracking-widest mt-1">Pharmacy ERP Oversight</p>
                    </div>
                    <button onclick="syncInventory()" class="p-3 bg-rose-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-rose-200 hover:scale-105 transition-transform group">
                        <svg class="w-6 h-6 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                </div>
                <div class="p-10">
                    <?php 
                    $low_stock_query = $conn->query("SELECT * FROM medications WHERE stock_quantity <= reorder_level ORDER BY stock_quantity ASC LIMIT 4");
                    if ($low_stock_query->num_rows > 0): ?>
                        <div class="space-y-4">
                            <?php while($item = $low_stock_query->fetch_assoc()): ?>
                                <div class="flex items-center justify-between p-5 bg-slate-50 rounded-3xl border border-slate-100">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-white rounded-xl border border-slate-100 flex items-center justify-center text-rose-600 font-black text-xs"><?php echo substr($item['name'], 0, 1); ?></div>
                                        <div>
                                            <p class="font-black text-slate-900 text-sm"><?php echo $item['name']; ?></p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Current: <?php echo $item['stock_quantity']; ?> <?php echo $item['unit']; ?>s</p>
                                        </div>
                                    </div>
                                    <span class="px-4 py-1.5 bg-rose-100 text-rose-700 rounded-xl text-[10px] font-black uppercase tracking-widest">Restock Needed</span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="py-12 text-center">
                            <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-3xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <p class="text-slate-400 font-bold italic">All medication levels are healthy.</p>
                        </div>
                    <?php endif; ?>
                    <a href="manage_hr.php" class="block w-full text-center py-5 mt-8 bg-slate-900 text-white rounded-3xl font-black text-xs uppercase tracking-widest hover:bg-rose-600 transition-all shadow-lg shadow-slate-200">Contact Pharmacist</a>
                </div>
            </div>

            <!-- Financial Aid Oversight -->
            <div class="bg-white rounded-[40px] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
                <div class="p-10 bg-blue-50/50 border-b border-slate-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black text-blue-900">Pending Financial Aid</h3>
                        <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mt-1">Patient Support Requests</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="p-10">
                    <?php 
                    $pending_aid = $conn->query("SELECT * FROM financial_aid_requests WHERE status = 'pending' ORDER BY created_at DESC LIMIT 4");
                    if ($pending_aid && $pending_aid->num_rows > 0): ?>
                        <div class="space-y-4">
                            <?php while($aid = $pending_aid->fetch_assoc()): ?>
                                <div class="flex items-center justify-between p-5 bg-slate-50 rounded-3xl border border-slate-100">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-white rounded-xl border border-slate-100 flex items-center justify-center text-blue-600 font-black text-xs"><?php echo substr($aid['name'], 0, 1); ?></div>
                                        <div>
                                            <p class="font-black text-slate-900 text-sm"><?php echo htmlspecialchars($aid['name']); ?></p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">₦<?php echo number_format($aid['amount']); ?> &bull; <?php echo date('d M', strtotime($aid['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <a href="manage_aid.php" class="px-4 py-1.5 bg-blue-100 text-blue-700 rounded-xl text-[10px] font-black uppercase tracking-widest">Review</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="py-12 text-center">
                            <div class="w-16 h-16 bg-slate-50 text-slate-300 rounded-3xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <p class="text-slate-400 font-bold italic">No pending requests.</p>
                        </div>
                    <?php endif; ?>
                    <a href="manage_aid.php" class="block w-full text-center py-5 mt-8 bg-blue-600 text-white rounded-3xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">Go to Financial Aid</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Checkbox Logic
        const selectAll = document.getElementById('selectAll');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');

        function updateBulkBar() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            selectedCount.textContent = checked.length;
            bulkActions.classList.toggle('hidden', checked.length === 0);
        }

        selectAll.addEventListener('change', () => {
            rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkBar();
        });

        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkBar);
        });

        // Update URL Filters
        function updateFilter(key, value) {
            const url = new URL(window.location.href);
            if (value) url.searchParams.set(key, value);
            else url.searchParams.delete(key);
            window.location.href = url.toString();
        }

        // Bulk API Actions
        async function bulkAction(action, status = null) {
            if (!confirm(`Are you sure you want to ${action} the selected items?`)) return;

            const selectedItems = [];
            document.querySelectorAll('.row-checkbox:checked').forEach(cb => {
                const row = cb.closest('.appointment-row');
                selectedItems.push({
                    id: row.dataset.id,
                    type: row.dataset.type
                });
            });

            try {
                const response = await fetch('api/manage_appointments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, items: selectedItems, status })
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred during the bulk action.');
            }
        }

        async function syncInventory() {
            const btn = event.currentTarget;
            btn.classList.add('animate-spin');
            try {
                const response = await fetch('../api/inventory_alerts.php');
                const text = await response.text();
                console.log(text);
                alert('Inventory Sync Complete: Donation page updated based on current stock levels.');
            } catch (err) {
                console.error(err);
                alert('Sync Failed. Check console for details.');
            } finally {
                btn.classList.remove('animate-spin');
                location.reload();
            }
        }

        // Real-Time Sync Subscriptions
        let reloadTimeout = null;
        function throttledReload() {
            if (reloadTimeout) return;
            reloadTimeout = setTimeout(() => {
                location.reload();
            }, 2000); // Wait 2 seconds before reloading to batch signals
        }

        if (window.HospitalSync) {
            window.HospitalSync.subscribe('patient_queue', (signal) => {
                console.log('📡 [Admin] Patient Queue Updated');
                throttledReload();
            });
            window.HospitalSync.subscribe('billing', (signal) => {
                console.log('📡 [Admin] Billing Activity Received');
                throttledReload();
            });
            window.HospitalSync.subscribe('lab_requests', (signal) => {
                console.log('📡 [Admin] Lab Activity Received');
                throttledReload();
            });
            window.HospitalSync.subscribe('prescriptions', (signal) => {
                console.log('📡 [Admin] Prescription Activity Received');
                throttledReload();
            });
            window.HospitalSync.subscribe('notifications', (signal) => {
                console.log('📡 [Admin] New Global Notification');
                if (typeof fetchAdminNotifications === 'function') fetchAdminNotifications();
            });
        }
    </script>
    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>