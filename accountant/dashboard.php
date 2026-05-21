<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_helper.php';

// Enforce RBAC
Auth::guard('billing', 'view');

if (!isset($_SESSION['accountant_id']) && Auth::getRole() !== 'admin') {
    header('Location: login.php');
    exit;
}

// Financial Stats
$total_revenue = $conn->query("SELECT SUM(paid_amount) FROM invoices")->fetch_row()[0] ?? 0;
$pending_revenue = $conn->query("SELECT SUM(total_amount - paid_amount) FROM invoices WHERE status != 'Paid'")->fetch_row()[0] ?? 0;
$pending_proofs = $conn->query("SELECT COUNT(*) FROM patient_onboarding WHERE payment_status = 'Pending'")->fetch_row()[0] ?? 0;

$pending_invoices = $conn->query("SELECT i.*, p.full_name FROM invoices i JOIN patients p ON i.patient_id = p.id WHERE i.status != 'Paid' ORDER BY i.created_at DESC LIMIT 10");

$page_title = "Finance Dashboard";
include 'includes/portal_layout_header.php';
?>

<div class="p-10">
    <header class="flex justify-between items-end mb-12">
        <div>
            <h2 class="text-4xl font-black text-slate-900 mb-2 tracking-tight">Financial Overview</h2>
            <p class="text-slate-500 font-medium text-lg">Hospital revenue and billing management.</p>
        </div>
        <div class="flex gap-4">
            <button onclick="location.reload()" class="p-4 bg-white border border-slate-200 rounded-2xl text-slate-400 hover:text-emerald-600 transition-all">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </header>

    <div class="grid md:grid-cols-3 gap-8 mb-12">
        <div class="bg-white p-8 rounded-[40px] border border-slate-100 shadow-sm">
            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-wallet text-2xl"></i>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Total Revenue (Paid)</p>
            <h3 class="text-3xl font-black text-slate-900">₦<?php echo number_format($total_revenue); ?></h3>
        </div>
        <div class="bg-white p-8 rounded-[40px] border border-slate-100 shadow-sm">
            <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-clock text-2xl"></i>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Outstanding Balance</p>
            <h3 class="text-3xl font-black text-slate-900">₦<?php echo number_format($pending_revenue); ?></h3>
        </div>
        <div class="bg-white p-8 rounded-[40px] border border-slate-100 shadow-sm">
            <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-file-signature text-2xl"></i>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Pending Verifications</p>
            <h3 class="text-3xl font-black text-slate-900"><?php echo $pending_proofs; ?></h3>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        <div class="bg-white rounded-[40px] border border-slate-100 p-10">
            <div class="flex justify-between items-center mb-8">
                <h4 class="text-lg font-black text-slate-900 uppercase tracking-tight">Pending Payments</h4>
                <a href="manage_invoices.php?status=Pending" class="text-[10px] font-black text-emerald-600 uppercase tracking-widest hover:underline">View All</a>
            </div>
            <div class="space-y-4">
                <?php if($pending_invoices->num_rows == 0): ?>
                    <p class="text-slate-400 italic font-medium">No pending payments found.</p>
                <?php else: while($inv = $pending_invoices->fetch_assoc()): 
                    $inv_id = $inv['id'];
                    $type_res = $conn->query("SELECT item_type FROM invoice_items WHERE invoice_id = $inv_id LIMIT 1");
                    $item_type = $type_res->fetch_assoc()['item_type'] ?? 'Other';
                    $icon = 'fas fa-file-invoice-dollar';
                    if($item_type == 'Lab') $icon = 'fas fa-vial';
                    if($item_type == 'Consultation') $icon = 'fas fa-user-md';
                    if($item_type == 'Medication') $icon = 'fas fa-pills';
                ?>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-400 shadow-sm">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-900"><?php echo $inv['full_name']; ?></p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">₦<?php echo number_format($inv['total_amount']); ?> • <?php echo $inv['invoice_no']; ?></p>
                            </div>
                        </div>
                        <button onclick="quickPay(<?php echo $inv['id']; ?>, <?php echo $inv['total_amount'] - $inv['paid_amount']; ?>)" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all opacity-0 group-hover:opacity-100 shadow-lg shadow-emerald-100">Confirm</button>
                    </div>
                <?php endwhile; endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-[40px] border border-slate-100 p-10">
            <h4 class="text-lg font-black text-slate-900 mb-8 uppercase tracking-tight">Recent Financial Activity</h4>
            <div class="space-y-6" id="notif-feed">
                <p class="text-slate-400 italic font-medium">Listening for live updates...</p>
            </div>
        </div>
    </div>
</div>

<script>
    function quickPay(id, amount) {
        if(!confirm('Confirm receipt of ₦' + amount.toLocaleString() + '?')) return;
        const fd = new FormData();
        fd.append('invoice_id', id);
        fd.append('amount', amount);
        fetch('../api/finance_api.php?action=mark_as_paid', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if(data.success) location.reload();
            else alert(data.message);
        });
    }

    // Feed specific to Dashboard
    function fetchDashboardFeed() {
        fetch('../api/notifications.php?action=get&role=admin')
            .then(r => r.json())
            .then(data => {
                if(data.success && data.notifications.length > 0) {
                    document.getElementById('notif-feed').innerHTML = data.notifications.map(n => `
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-[10px] font-black text-emerald-600 uppercase mb-1">${n.title}</p>
                            <p class="text-xs font-bold text-slate-700">${n.message}</p>
                            <p class="text-[9px] text-slate-400 mt-2">${new Date(n.created_at).toLocaleString()}</p>
                        </div>
                    `).join('');
                }
            });
    }
    setInterval(fetchDashboardFeed, 30000);
    fetchDashboardFeed();

    // Real-Time Sync Subscription
    let reloadTimeout = null;
    function throttledReload() {
        if (reloadTimeout) return;
        reloadTimeout = setTimeout(() => {
            location.reload();
        }, 2000);
    }

    if (window.HospitalSync) {
        window.HospitalSync.subscribe('billing', (signal) => {
            console.log('📡 [Finance] Billing Signal Received');
            throttledReload(); 
        });
        window.HospitalSync.subscribe('notifications', (signal) => {
            console.log('📡 [Finance] Notification Signal Received');
            fetchDashboardFeed();
        });
        window.HospitalSync.subscribe('patient_queue', (signal) => {
            console.log('📡 [Finance] Patient Queue Updated (Onboarding)');
            // Refresh counts/stats
            throttledReload();
        });
    }
</script>

<?php include 'includes/portal_layout_footer.php'; ?>
