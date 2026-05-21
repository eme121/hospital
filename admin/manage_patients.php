<?php
session_start();
require_once '../includes/db_connect.php';

// Auto-Migration: Ensure is_deleted column exists
$check_d = $conn->query("SHOW COLUMNS FROM patients LIKE 'is_deleted'");
if ($check_d && $check_d->num_rows == 0) {
    $conn->query("ALTER TABLE patients ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
}

// Auto-Migration: Ensure password reset columns exist
$check_token = $conn->query("SHOW COLUMNS FROM patients LIKE 'reset_token'");
if ($check_token && $check_token->num_rows == 0) {
    $conn->query("ALTER TABLE patients ADD COLUMN reset_token VARCHAR(100) DEFAULT NULL, ADD COLUMN token_expiry DATETIME DEFAULT NULL");
}

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Handle Balance Update
if (isset($_POST['update_balance'])) {
    $id = intval($_POST['patient_id']);
    $owed = floatval($_POST['owed_amount']);
    $paid = floatval($_POST['paid_amount']);
    
    // Fetch patient info for email
    $p_stmt = $conn->prepare("SELECT full_name, email FROM patients WHERE id = ?");
    $p_stmt->bind_param("i", $id);
    $p_stmt->execute();
    $p_data = $p_stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("UPDATE patients SET owed_amount = ?, paid_amount = ? WHERE id = ?");
    $stmt->bind_param("ddi", $owed, $paid, $id);
    
    if ($stmt->execute()) {
        if ($p_data && $owed > $paid) {
            require_once '../includes/config.php';
            require_once '../includes/SimpleSMTP.php';
            
            $balance = $owed - $paid;
            $to = $p_data['email'];
            $subject = "Billing Update - Hope Haven Hospital";
            $message = "Dear " . $p_data['full_name'] . ",\n\nThis is to inform you that your hospital billing record has been updated. \n\nOutstanding Balance: ₦" . number_format($balance) . "\n\nPlease log in to your dashboard to view details or submit proof of payment if you have already paid.\n\nBest regards,\nAccounting Department";
            
            $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
            $smtp->send($to, $subject, $message, FROM_EMAIL, FROM_NAME);
        }
        header('Location: manage_patients.php?updated=1');
        exit;
    }
}

// Handle Actions (Archive, Restore, Delete)
if (isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'archive') {
        $stmt = $conn->prepare("UPDATE patients SET is_deleted = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: manage_patients.php?archived=1");
    } elseif ($action === 'restore') {
        $stmt = $conn->prepare("UPDATE patients SET is_deleted = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: manage_patients.php?restored=1");
    } elseif ($action === 'delete') {
        try {
            $conn->begin_transaction();
            
            $stmt1 = $conn->prepare("DELETE FROM appointments WHERE patient_id = ?");
            $stmt1->bind_param("i", $id);
            $stmt1->execute();
            
            $stmt2 = $conn->prepare("DELETE FROM telemedicine_appointments WHERE patient_id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            
            $stmt3 = $conn->prepare("DELETE FROM patients WHERE id = ?");
            $stmt3->bind_param("i", $id);
            $stmt3->execute();
            
            $conn->commit();
            header("Location: manage_patients.php?deleted=1");
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: manage_patients.php?error=1");
        }
    }
    exit;
}

// Fetch Data
$active_result = $conn->query("SELECT p.*, po.status as onboarding_status FROM patients p LEFT JOIN patient_onboarding po ON p.id = po.patient_id WHERE p.is_deleted = 0 ORDER BY p.full_name ASC");
$archived_result = $conn->query("SELECT * FROM patients WHERE is_deleted = 1 ORDER BY full_name ASC");

// Summary Stats
$stats_res = $conn->query("SELECT SUM(owed_amount) as total_owed, SUM(paid_amount) as total_paid FROM patients WHERE is_deleted = 0");
$stats = $stats_res ? $stats_res->fetch_assoc() : ['total_owed' => 0, 'total_paid' => 0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients | Hope Haven Hospital</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; p: 0; }
            .shadow-xl { shadow: none !important; }
            .rounded-[40px] { border-radius: 0 !important; }
        }
        .print-only { display: none; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="grid md:grid-cols-3 gap-6 mb-10 no-print">
            <div class="bg-white p-8 rounded-[32px] border border-slate-100 shadow-sm shadow-slate-200/50">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Total Patients</p>
                <div class="flex items-end gap-2">
                    <h3 class="text-4xl font-black text-slate-900 leading-none"><?php echo $active_result ? $active_result->num_rows : 0; ?></h3>
                    <span class="text-xs font-bold text-slate-400 mb-1">Active</span>
                </div>
            </div>
            <div class="bg-white p-8 rounded-[32px] border border-slate-100 shadow-sm shadow-slate-200/50">
                <p class="text-[10px] font-black text-rose-400 uppercase tracking-[0.2em] mb-3">Total Owed (Revenue)</p>
                <h3 class="text-4xl font-black text-rose-600 leading-none">₦<?php echo number_format($stats['total_owed'] ?? 0); ?></h3>
            </div>
            <div class="bg-white p-8 rounded-[32px] border border-slate-100 shadow-sm shadow-slate-200/50">
                <p class="text-[10px] font-black text-emerald-400 uppercase tracking-[0.2em] mb-3">Total Collected</p>
                <h3 class="text-4xl font-black text-emerald-600 leading-none">₦<?php echo number_format($stats['total_paid'] ?? 0); ?></h3>
            </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6 no-print">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Patient Records</h1>
                <p class="text-slate-500 font-medium">Manage medical files and financial balances.</p>
            </div>
            <div class="flex flex-wrap gap-4 items-center w-full md:w-auto">
                <div class="relative flex-1 md:flex-none min-w-[300px]">
                    <input type="text" id="patientSearch" placeholder="Search by name or file number..." 
                           class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button onclick="exportToCSV()" class="bg-slate-900 text-white px-8 py-3.5 rounded-2xl font-bold text-sm hover:bg-blue-600 transition-all shadow-lg shadow-slate-200">Export CSV</button>
                <button onclick="window.print()" class="bg-white text-slate-600 border border-slate-200 px-6 py-3.5 rounded-2xl font-bold text-sm hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-4 mb-6 no-print">
            <button onclick="switchTab('active')" id="tab-active" class="px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-blue-600 text-white shadow-lg shadow-blue-100">Active (<?php echo $active_result->num_rows; ?>)</button>
            <button onclick="switchTab('archived')" id="tab-archived" class="px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-white text-slate-400 border border-slate-100">Archived (<?php echo $archived_result->num_rows; ?>)</button>
        </div>

        <!-- Active Table -->
        <div id="section-active" class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse" id="patientsTable">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Patient Details</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Onboarding</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-right">Owed (₦)</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-right">Paid (₦)</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Balance</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if($active_result && $active_result->num_rows > 0): ?>
                            <?php while($row = $active_result->fetch_assoc()): 
                                $balance = $row['owed_amount'] - $row['paid_amount'];
                            ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group patient-row">
                                    <td class="px-8 py-6">
                                        <p class="font-bold text-slate-900 text-sm patient-name"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                        <p class="text-[10px] text-blue-600 font-black uppercase tracking-wider mt-1 patient-file">FILE: <?php echo htmlspecialchars($row['file_number']); ?></p>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <?php 
                                            $onStatus = $row['onboarding_status'] ?? 'Not Started';
                                            $onColor = 'bg-slate-50 text-slate-400 border-slate-100';
                                            switch($onStatus) {
                                                case 'Completed': $onColor = 'bg-emerald-50 text-emerald-600 border-emerald-100'; break;
                                                case 'Paid': $onColor = 'bg-blue-50 text-blue-600 border-blue-100'; break;
                                                case 'Payment Pending': $onColor = 'bg-amber-50 text-amber-600 border-amber-100'; break;
                                                case 'In Progress': $onColor = 'bg-indigo-50 text-indigo-600 border-indigo-100'; break;
                                            }
                                        ?>
                                        <span class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border <?php echo $onColor; ?>">
                                            <?php echo $onStatus; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-right font-bold text-slate-700 text-sm">
                                        <?php echo number_format($row['owed_amount']); ?>
                                    </td>
                                    <td class="px-8 py-6 text-right font-bold text-emerald-600 text-sm">
                                        <?php echo number_format($row['paid_amount']); ?>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <?php 
                                            $statusColor = ($balance > 0) ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100';
                                        ?>
                                        <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $statusColor; ?>">
                                            <?php echo ($balance > 0) ? '₦'.number_format($balance).' DUE' : 'CLEARED'; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-center no-print">
                                        <div class="flex justify-center gap-2">
                                            <button onclick='openBalanceModal(<?php echo htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>)' class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Edit Balance">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </button>
                                            <a href="?action=archive&id=<?php echo $row['id']; ?>" onclick="return confirm('Archive patient record?')" class="p-2.5 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-600 hover:text-white transition-all shadow-sm" title="Archive Patient">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold italic">No active patients.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Archived Table -->
        <div id="section-archived" class="hidden bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Patient Details</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if($archived_result && $archived_result->num_rows > 0): ?>
                            <?php while($row = $archived_result->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group opacity-75 grayscale">
                                    <td class="px-8 py-6">
                                        <p class="font-bold text-slate-500 text-sm"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                        <p class="text-[10px] text-slate-400 font-black uppercase mt-1">FILE: <?php echo htmlspecialchars($row['file_number']); ?></p>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <div class="flex justify-center gap-2">
                                            <a href="?action=restore&id=<?php echo $row['id']; ?>" class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Restore Patient">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('PERMANENTLY DELETE this patient?')" class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Permanent Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="px-8 py-20 text-center text-slate-400 font-bold italic">No archived patients.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Balance Modal -->
    <div id="balanceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-6">
        <div class="bg-white max-w-lg w-full rounded-[40px] shadow-2xl overflow-hidden transform transition-all scale-95 opacity-0 duration-300" id="balanceModalContent">
            <div class="p-10">
                <div class="flex justify-between items-center mb-10">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Financial Record</h2>
                    <button onclick="closeBalanceModal()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form method="POST" class="space-y-8">
                    <input type="hidden" name="patient_id" id="modal_patient_id">
                    <div class="p-8 bg-blue-50/50 rounded-[32px] border border-blue-100/50">
                        <p class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] mb-2">Target Patient</p>
                        <h4 id="modal_patient_name" class="text-2xl font-black text-blue-600 leading-tight"></h4>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Total Owed (₦)</label>
                            <input type="number" name="owed_amount" id="modal_owed" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-6 py-4 text-slate-900 font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Total Paid (₦)</label>
                            <input type="number" name="paid_amount" id="modal_paid" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-6 py-4 text-slate-900 font-bold focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all">
                        </div>
                    </div>

                    <button type="submit" name="update_balance" class="w-full py-5 bg-slate-900 text-white rounded-[24px] font-black text-sm tracking-widest uppercase hover:bg-blue-600 transition-all shadow-xl shadow-slate-200 group flex items-center justify-center gap-3">
                        Sync Financial Record
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

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

        // Search Functionality
        document.getElementById('patientSearch').addEventListener('keyup', function() {
            var term = this.value.toLowerCase();
            var rows = document.querySelectorAll('.patient-row');
            rows.forEach(function(row) {
                var name = row.querySelector('.patient-name').textContent.toLowerCase();
                var file = row.querySelector('.patient-file').textContent.toLowerCase();
                row.style.display = (name.includes(term) || file.includes(term)) ? "" : "none";
            });
        });

        function openProfileModal(data) {
            document.getElementById('profile_patient_id').value = data.id;
            document.getElementById('profile_name').value = data.full_name;
            document.getElementById('profile_email').value = data.email;
            document.getElementById('profile_phone').value = data.phone;
            document.getElementById('profile_file').value = data.file_number;
            
            var modal = document.getElementById('profileModal');
            var content = document.getElementById('profileModalContent');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(function() {
                content.classList.remove('scale-95', 'opacity-0');
            }, 10);
        }

        function closeProfileModal() {
            var content = document.getElementById('profileModalContent');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(function() {
                document.getElementById('profileModal').classList.add('hidden');
                document.getElementById('profileModal').classList.remove('flex');
            }, 300);
        }

        function openBalanceModal(data) {
            document.getElementById('modal_patient_id').value = data.id;
            document.getElementById('modal_patient_name').innerText = data.full_name;
            document.getElementById('modal_owed').value = data.owed_amount;
            document.getElementById('modal_paid').value = data.paid_amount;
            
            var modal = document.getElementById('balanceModal');
            var content = document.getElementById('balanceModalContent');
            modal.classList.remove('hidden');
            setTimeout(function() {
                content.classList.remove('scale-95', 'opacity-0');
            }, 10);
        }

        function closeBalanceModal() {
            var content = document.getElementById('balanceModalContent');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(function() {
                document.getElementById('balanceModal').classList.add('hidden');
            }, 300);
        }

        function exportToCSV() {
            var csv = [["Full Name", "File Number", "Owed", "Paid", "Balance"]];
            var rows = document.querySelectorAll(".patient-row");
            rows.forEach(function(row) {
                var name = row.querySelector('.patient-name').textContent;
                var file = row.querySelector('.patient-file').textContent.replace('FILE: ', '');
                var cols = row.querySelectorAll("td");
                csv.push(['"' + name + '"', '"' + file + '"', '"' + cols[1].textContent.trim() + '"', '"' + cols[2].textContent.trim() + '"', '"' + cols[3].textContent.trim() + '"']);
            });
            var csvContent = "data:text/csv;charset=utf-8," + csv.map(e => e.join(",")).join("\n");
            var link = document.createElement("a");
            link.setAttribute("href", encodeURI(csvContent));
            link.setAttribute("download", "patients_report.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>