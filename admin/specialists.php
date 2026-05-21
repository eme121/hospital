<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Current tab
$current_tab = $_GET['tab'] ?? 'pending';

// Approval Logic
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $status = ($action == 'approve') ? 'Approved' : 'Rejected';

    $stmt = $conn->prepare("UPDATE telemedicine_doctors SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute() && $status == 'Approved') {
        syncSpecialistToMain($conn, $id);
    }
    header("Location: specialists.php?tab=$current_tab&notif=status_updated");
    exit;
}

function syncSpecialistToMain($conn, $spec_id) {
    $spec_stmt = $conn->prepare("SELECT name, email, password, department_id, profile_pix FROM telemedicine_doctors WHERE id = ?");
    $spec_stmt->bind_param("i", $spec_id);
    $spec_stmt->execute();
    $spec = $spec_stmt->get_result()->fetch_assoc();

    if ($spec) {
        $check_stmt = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
        $check_stmt->bind_param("s", $spec['email']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows == 0) {
            $ins_stmt = $conn->prepare("INSERT INTO doctors (name, email, password, department_id, image_url, allow_physical, allow_virtual) VALUES (?, ?, ?, ?, ?, 0, 1)");
            $ins_stmt->bind_param("sssis", $spec['name'], $spec['email'], $spec['password'], $spec['department_id'], $spec['profile_pix']);
            $ins_stmt->execute();
        }
    }
}

// Fetch Specialists based on tab
$status_filter = ucfirst($current_tab);
$stmt = $conn->prepare("SELECT d.*, de.name as department_name FROM telemedicine_doctors d LEFT JOIN departments de ON d.department_id = de.id WHERE d.status = ? ORDER BY d.created_at DESC");
$stmt->bind_param("s", $status_filter);
$stmt->execute();
$specialists = $stmt->get_result();

// Count for badges
$count_pending = $conn->query("SELECT COUNT(*) as count FROM telemedicine_doctors WHERE status = 'Pending'")->fetch_assoc()['count'];
$count_approved = $conn->query("SELECT COUNT(*) as count FROM telemedicine_doctors WHERE status = 'Approved'")->fetch_assoc()['count'];
$count_rejected = $conn->query("SELECT COUNT(*) as count FROM telemedicine_doctors WHERE status = 'Rejected'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specialists | Hope Haven Hospital</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tab-active { color: #2563eb; border-bottom: 2px solid #2563eb; }
        .hover-scale { transition: transform 0.2s; }
        .hover-scale:hover { transform: scale(1.02); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="mb-10 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Doctor Onboarding</h1>
                <p class="text-slate-500 font-medium italic">Manage and review medical specialist registrations.</p>
            </div>
        </div>

        <!-- Sub-Tabs Navigation -->
        <div class="flex gap-10 mb-8 border-b border-slate-200 no-print">
            <a href="?tab=pending" class="pb-4 text-xs font-black uppercase tracking-widest flex items-center gap-2 <?php echo ($current_tab == 'pending') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-400 hover:text-slate-600'; ?>">
                Pending
                <span class="bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full text-[9px]"><?php echo $count_pending; ?></span>
            </a>
            <a href="?tab=approved" class="pb-4 text-xs font-black uppercase tracking-widest flex items-center gap-2 <?php echo ($current_tab == 'approved') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-400 hover:text-slate-600'; ?>">
                Approved
                <span class="bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full text-[9px]"><?php echo $count_approved; ?></span>
            </a>
            <a href="?tab=rejected" class="pb-4 text-xs font-black uppercase tracking-widest flex items-center gap-2 <?php echo ($current_tab == 'rejected') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-400 hover:text-slate-600'; ?>">
                Rejected
                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-[9px]"><?php echo $count_rejected; ?></span>
            </a>
        </div>

        <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Specialist</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Specialty</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Registration Date</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Status</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if($specialists->num_rows > 0): ?>
                            <?php while($row = $specialists->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <img src="../<?php echo $row['profile_pix']; ?>" class="w-12 h-12 rounded-xl object-cover ring-2 ring-slate-100 group-hover:ring-blue-200 transition-all">
                                            <div>
                                                <p class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($row['name']); ?></p>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5"><?php echo htmlspecialchars($row['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-blue-100">
                                            <?php echo htmlspecialchars($row['department_name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs font-bold text-slate-600"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></p>
                                        <p class="text-[9px] text-slate-400 font-medium uppercase mt-0.5"><?php echo date('h:i A', strtotime($row['created_at'])); ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <?php 
                                            $color = "bg-amber-100 text-amber-700 border-amber-200";
                                            if($row['status'] == 'Approved') $color = "bg-emerald-100 text-emerald-700 border-emerald-200";
                                            if($row['status'] == 'Rejected') $color = "bg-rose-100 text-rose-700 border-rose-200";
                                        ?>
                                        <span class="px-4 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border <?php echo $color; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex justify-center gap-3">
                                            <button onclick="showDetails(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="View Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <a href="?tab=pending&action=approve&id=<?php echo $row['id']; ?>" class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Approve Specialist">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                </a>
                                                <a href="?tab=pending&action=reject&id=<?php echo $row['id']; ?>" class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Reject Specialist">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-24 text-center">
                                    <p class="text-slate-400 font-bold italic tracking-wide">No specialist registrations found in this tab.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] w-full max-w-lg p-10 shadow-2xl transform transition-all scale-95 opacity-0 duration-300" id="modalContent">
            <div class="flex justify-between items-center mb-8">
                <h3 class="text-xl font-black text-slate-900 tracking-tight uppercase">Specialist Details</h3>
                <button onclick="hideModal()" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <div id="modalBody" class="space-y-6">
                <!-- Content injected by JS -->
            </div>
        </div>
    </div>

    <script>
        function showDetails(data) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');
            const body = document.getElementById('modalBody');
            
            body.innerHTML = `
                <div class="flex flex-col items-center text-center mb-6">
                    <img src="../${data.profile_pix}" class="w-32 h-32 rounded-3xl object-cover shadow-2xl mb-4 ring-8 ring-slate-50">
                    <h2 class="text-2xl font-black text-slate-900">${data.name}</h2>
                    <span class="px-4 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-black uppercase tracking-widest mt-2 border border-blue-100">${data.department_name}</span>
                </div>
                <div class="grid grid-cols-2 gap-6 bg-slate-50 p-6 rounded-[32px]">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Email Address</p>
                        <p class="text-sm font-bold text-slate-700">${data.email}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Joined Date</p>
                        <p class="text-sm font-bold text-slate-700">${new Date(data.created_at).toLocaleDateString()}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Onboarding Status</p>
                        <span class="text-sm font-bold text-blue-600 uppercase tracking-widest">${data.status}</span>
                    </div>
                </div>
                ${data.status === 'Pending' ? `
                <div class="flex gap-4 mt-8">
                    <a href="?tab=pending&action=approve&id=${data.id}" class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest text-center hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-200">Approve Specialist</a>
                    <a href="?tab=pending&action=reject&id=${data.id}" class="flex-1 py-4 bg-rose-50 text-rose-600 rounded-2xl font-black text-xs uppercase tracking-widest text-center hover:bg-rose-100 transition-all">Reject</a>
                </div>
                ` : ''}
            `;
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function hideModal() {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }
    </script>
</body>
</html>