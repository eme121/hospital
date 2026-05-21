<?php
session_start();
require_once '../includes/db_connect.php';

// Auto-Migration: Ensure columns exist
$check_p = $conn->query("SHOW COLUMNS FROM doctors LIKE 'allow_physical'");
if ($check_p && $check_p->num_rows == 0) {
    $conn->query("ALTER TABLE doctors ADD COLUMN allow_physical TINYINT(1) DEFAULT 1");
}
$check_v = $conn->query("SHOW COLUMNS FROM doctors LIKE 'allow_virtual'");
if ($check_v && $check_v->num_rows == 0) {
    $conn->query("ALTER TABLE doctors ADD COLUMN allow_virtual TINYINT(1) DEFAULT 0");
}
$check_phone = $conn->query("SHOW COLUMNS FROM doctors LIKE 'phone'");
if ($check_phone && $check_phone->num_rows == 0) {
    $conn->query("ALTER TABLE doctors ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
}
$check_d = $conn->query("SHOW COLUMNS FROM doctors LIKE 'is_deleted'");
if ($check_d && $check_d->num_rows == 0) {
    $conn->query("ALTER TABLE doctors ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
}

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Handle Add Doctor
if (isset($_POST['add_doctor'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $dept_id = intval($_POST['department_id']);
    $image_url = $_POST['image_url']; 
    $allow_physical = isset($_POST['allow_physical']) ? 1 : 0;
    $allow_virtual = isset($_POST['allow_virtual']) ? 1 : 0;

    if (isset($_FILES['doctor_image']) && $_FILES['doctor_image']['error'] == 0) {
        $upload_dir = "../assets/images/doctors/";
        $file_ext = pathinfo($_FILES['doctor_image']['name'], PATHINFO_EXTENSION);
        $unique_name = uniqid('dr_') . '.' . $file_ext;
        $target_file = $upload_dir . $unique_name;
        
        if (move_uploaded_file($_FILES['doctor_image']['tmp_name'], $target_file)) {
            $image_url = "assets/images/doctors/" . $unique_name;
        }
    }
    
    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&q=80&w=200';
    }

    $stmt = $conn->prepare("INSERT INTO doctors (name, email, phone, password, department_id, image_url, allow_physical, allow_virtual, is_deleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("ssssisii", $name, $email, $phone, $password, $dept_id, $image_url, $allow_physical, $allow_virtual);
    $stmt->execute();
    header('Location: manage_doctors.php?success=1');
    exit;
}

// Handle Actions
if (isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'archive') {
        $stmt = $conn->prepare("UPDATE doctors SET is_deleted = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header('Location: manage_doctors.php?archived=1');
    } elseif ($action === 'restore') {
        $stmt = $conn->prepare("UPDATE doctors SET is_deleted = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header('Location: manage_doctors.php?restored=1');
    } elseif ($action === 'delete') {
        try {
            $conn->begin_transaction();
            
            $stmt1 = $conn->prepare("UPDATE appointments SET doctor_id = NULL WHERE doctor_id = ?");
            $stmt1->bind_param("i", $id);
            $stmt1->execute();
            
            $stmt2 = $conn->prepare("UPDATE telemedicine_appointments SET doctor_id = NULL WHERE doctor_id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            
            $stmt3 = $conn->prepare("DELETE FROM doctors WHERE id = ?");
            $stmt3->bind_param("i", $id);
            $stmt3->execute();
            
            $conn->commit();
            header('Location: manage_doctors.php?deleted=1');
        } catch (Exception $e) {
            $conn->rollback();
            header('Location: manage_doctors.php?error=1');
        }
    }
    exit;
}

// Handle Toggle Availability
if (isset($_GET['toggle']) && isset($_GET['type'])) {
    $id = intval($_GET['toggle']);
    $type = $_GET['type'] == 'p' ? 'allow_physical' : 'allow_virtual';
    
    // For the column name, we still use string interpolation but since it is hardcoded to 'allow_physical' or 'allow_virtual', it is safe.
    // However, the ID should be bound.
    $stmt = $conn->prepare("UPDATE doctors SET $type = NOT $type WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: manage_doctors.php?updated=1');
}

// Fetch Data
$active_result = $conn->query("SELECT d.*, de.name as department_name FROM doctors d JOIN departments de ON d.department_id = de.id WHERE d.is_deleted = 0 ORDER BY d.name ASC");
$archived_result = $conn->query("SELECT d.*, de.name as department_name FROM doctors d JOIN departments de ON d.department_id = de.id WHERE d.is_deleted = 1 ORDER BY d.name ASC");
$depts = $conn->query("SELECT * FROM departments ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors | Hope Haven Hospital</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        @keyframes zoom-in {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .animate-zoom-in { animation: zoom-in 0.3s ease-out forwards; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6 no-print">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Hospital Doctors</h1>
                <p class="text-slate-500 font-medium">Add and manage doctors for the appointment booking system.</p>
            </div>
            <div class="flex flex-wrap gap-4 items-center w-full md:w-auto">
                <div class="relative flex-1 md:flex-none min-w-[300px]">
                    <input type="text" id="doctorSearch" placeholder="Search by name or department..." 
                           class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button onclick="document.getElementById('addDoctorModal').classList.remove('hidden')" class="bg-blue-600 text-white px-8 py-3.5 rounded-2xl font-black text-sm hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                    Add Doctor
                </button>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="mb-8 p-5 bg-emerald-50 text-emerald-600 rounded-2xl font-bold text-sm border border-emerald-100 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Action completed successfully!
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex gap-4 mb-6">
            <button onclick="switchTab('active')" id="tab-active" class="px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-blue-600 text-white shadow-lg shadow-blue-100">Active (<?php echo $active_result->num_rows; ?>)</button>
            <button onclick="switchTab('archived')" id="tab-archived" class="px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-white text-slate-400 border border-slate-100">Archived (<?php echo $archived_result->num_rows; ?>)</button>
        </div>

        <!-- Active Table -->
        <div id="section-active" class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="doctorsTable">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Doctor Information</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Department</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Physical Visit</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Virtual Care</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($row = $active_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="relative">
                                            <img src="<?php echo $row['image_url']; ?>" class="w-14 h-14 rounded-2xl object-cover ring-4 ring-slate-50 group-hover:ring-blue-100 transition-all shadow-sm">
                                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 text-sm leading-tight"><?php echo htmlspecialchars($row['name']); ?></p>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1 tracking-widest">Medical Officer</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-50 text-blue-600 border border-blue-100">
                                        <?php echo htmlspecialchars($row['department_name']); ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <a href="?toggle=<?php echo $row['id']; ?>&type=p" class="inline-flex px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all <?php echo $row['allow_physical'] ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'; ?>">
                                        <?php echo $row['allow_physical'] ? 'Active' : 'Disabled'; ?>
                                    </a>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <a href="?toggle=<?php echo $row['id']; ?>&type=v" class="inline-flex px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all <?php echo $row['allow_virtual'] ? 'bg-blue-500 text-white shadow-lg shadow-blue-200' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'; ?>">
                                        <?php echo $row['allow_virtual'] ? 'Active' : 'Disabled'; ?>
                                    </a>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="?action=archive&id=<?php echo $row['id']; ?>" onclick="return confirm('Archive this doctor?')" class="p-2.5 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-600 hover:text-white transition-all shadow-sm group/del" title="Archive Doctor">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Archived Table -->
        <div id="section-archived" class="hidden bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Doctor Information</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Department</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($row = $archived_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors group opacity-75">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4 grayscale">
                                        <img src="<?php echo $row['image_url']; ?>" class="w-12 h-12 rounded-xl object-cover">
                                        <p class="font-bold text-slate-500 text-sm"><?php echo htmlspecialchars($row['name']); ?></p>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-slate-400 text-sm"><?php echo htmlspecialchars($row['department_name']); ?></td>
                                <td class="px-8 py-6 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="?action=restore&id=<?php echo $row['id']; ?>" class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Restore Doctor">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('PERMANENTLY DELETE this doctor?')" class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Permanent Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function switchTab(type) {
            const activeSec = document.getElementById('section-active');
            const archivedSec = document.getElementById('section-archived');
            const activeTab = document.getElementById('tab-active');
            const archivedTab = document.getElementById('tab-archived');

            if (type === 'active') {
                activeSec.classList.remove('hidden');
                archivedSec.classList.add('hidden');
                activeTab.className = 'px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-blue-600 text-white shadow-lg shadow-blue-100';
                archivedTab.className = 'px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-white text-slate-400 border border-slate-100';
            } else {
                activeSec.classList.add('hidden');
                archivedSec.classList.remove('hidden');
                archivedTab.className = 'px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-blue-600 text-white shadow-lg shadow-blue-100';
                activeTab.className = 'px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-white text-slate-400 border border-slate-100';
            }
        }
        
        // ... (existing search script)
    </script>
    </main>

    <!-- Add Doctor Modal -->
    <div id="addDoctorModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[32px] w-full max-w-2xl shadow-2xl animate-zoom-in flex flex-col max-h-[90vh]" id="modalContent">
            <!-- Header: Fixed at top -->
            <div class="flex justify-between items-center p-8 border-b border-slate-100">
                <h3 class="text-xl font-black text-slate-900 tracking-tight">Add New Medical Officer</h3>
                <button onclick="document.getElementById('addDoctorModal').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-xl transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Form Content: Scrollable -->
            <form method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                <div class="p-8 overflow-y-auto space-y-8 custom-scrollbar">
                    
                    <!-- Section 1: Personal Information -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <h4 class="text-sm font-black text-slate-700 uppercase tracking-widest">Personal Information</h4>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider ml-1">Full Name</label>
                            <input type="text" name="name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-3 text-slate-900 font-semibold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none" placeholder="e.g. Dr. John Smith">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-2">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider ml-1">Email Address</label>
                                <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-3 text-slate-900 font-semibold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none" placeholder="doctor@hopehaven.ng">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider ml-1">WhatsApp Number</label>
                                <input type="text" name="phone" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-3 text-slate-900 font-semibold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none" placeholder="e.g. 2348000000000">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider ml-1">Login Password</label>
                            <input type="password" name="password" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-3 text-slate-900 font-semibold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none" placeholder="••••••••">
                        </div>
                    </div>

                    <!-- Section 2: Professional Details -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <h4 class="text-sm font-black text-slate-700 uppercase tracking-widest">Professional Details</h4>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider ml-1">Assigned Department</label>
                            <select name="department_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-3 text-slate-900 font-semibold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none cursor-pointer">
                                <option value="">Choose Department</option>
                                <?php 
                                $depts->data_seek(0);
                                while($dept = $depts->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl cursor-pointer border-2 border-transparent hover:border-blue-200 transition-all">
                                <input type="checkbox" name="allow_physical" checked class="w-5 h-5 accent-blue-600">
                                <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Physical Visit</span>
                            </label>
                            <label class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl cursor-pointer border-2 border-transparent hover:border-blue-200 transition-all">
                                <input type="checkbox" name="allow_virtual" class="w-5 h-5 accent-blue-600">
                                <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Virtual Care</span>
                            </label>
                        </div>
                    </div>

                    <!-- Section 3: Profile Media -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <h4 class="text-sm font-black text-slate-700 uppercase tracking-widest">Profile Media</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-2">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider ml-1">Upload Photo</label>
                                <input type="file" name="doctor_image" accept="image/*" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all cursor-pointer">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider ml-1">Or Photo URL</label>
                                <input type="url" name="image_url" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-3 text-slate-900 font-semibold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-xs" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer: Fixed at bottom -->
                <div class="p-8 border-t border-slate-100 bg-slate-50/50 rounded-b-[32px]">
                    <button type="submit" name="add_doctor" class="w-full bg-slate-900 text-white py-4 rounded-xl font-black text-sm tracking-widest uppercase hover:bg-blue-600 transition-all shadow-xl shadow-blue-900/10 flex items-center justify-center gap-3">
                        Register Medical Officer
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const doctorSearch = document.getElementById('doctorSearch');

            // Search Functionality
            if (doctorSearch) {
                doctorSearch.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase();
                    const rows = document.querySelectorAll('#doctorsTable tbody tr');
                    rows.forEach(row => {
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(term) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>