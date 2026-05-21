<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['accountant_id'])) {
    header('Location: login.php');
    exit;
}

$status_filter = $_GET['status'] ?? 'all';
$dept_filter = $_GET['dept'] ?? 'all';

// Base SQL
$sql = "SELECT DISTINCT i.*, p.full_name, p.file_number 
        FROM invoices i 
        JOIN patients p ON i.patient_id = p.id 
        LEFT JOIN invoice_items ii ON i.id = ii.invoice_id 
        WHERE 1=1 ";

// Apply Status Filter
if ($status_filter !== 'all') {
    $sql .= " AND i.status = '$status_filter'";
}

// Apply Department Filter
if ($dept_filter !== 'all') {
    if ($dept_filter === 'Consultation') {
        $sql .= " AND ii.item_type = 'Consultation'";
    } elseif ($dept_filter === 'Pharmacy') {
        $sql .= " AND ii.item_type = 'Medication'";
    } elseif ($dept_filter === 'Lab') {
        $sql .= " AND ii.item_type = 'Lab'";
    } elseif ($dept_filter === 'Records') {
        $sql .= " AND (ii.item_type = 'Records' OR ii.item_description LIKE '%Folder%' OR ii.item_description LIKE '%Onboarding%')";
    }
}

$sql .= " ORDER BY i.created_at DESC";
$result = $conn->query($sql);

$page_title = "Invoice Registry";
include 'includes/portal_layout_header.php';
?>

<div class="p-10">
    <header class="mb-12">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h2 class="text-4xl font-black text-slate-900 mb-2 tracking-tight">Invoice Registry</h2>
                <p class="text-slate-500 font-medium">Categorized hospital billing and collection.</p>
            </div>
            
            <!-- Status Filter Tabs -->
            <div class="bg-white p-1.5 rounded-2xl border border-slate-100 flex gap-1 shadow-sm">
                <a href="?status=all&dept=<?php echo $dept_filter; ?>" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all <?php echo $status_filter == 'all' ? 'bg-slate-900 text-white shadow-lg' : 'text-slate-400 hover:text-emerald-600'; ?>">All</a>
                <a href="?status=Pending&dept=<?php echo $dept_filter; ?>" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all <?php echo $status_filter == 'Pending' ? 'bg-amber-500 text-white shadow-lg' : 'text-slate-400 hover:text-amber-500'; ?>">Pending</a>
                <a href="?status=Paid&dept=<?php echo $dept_filter; ?>" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all <?php echo $status_filter == 'Paid' ? 'bg-emerald-500 text-white shadow-lg' : 'text-slate-400 hover:text-emerald-600'; ?>">Paid</a>
            </div>
        </div>

        <!-- Department Filter Tabs -->
        <div class="flex flex-wrap gap-3">
            <?php
            $depts = [
                'all' => ['label' => 'All Departments', 'icon' => 'fa-th-large'],
                'Consultation' => ['label' => 'Consultations', 'icon' => 'fa-user-md'],
                'Pharmacy' => ['label' => 'Pharmacy', 'icon' => 'fa-pills'],
                'Lab' => ['label' => 'Lab & Diagnostics', 'icon' => 'fa-vial'],
                'Records' => ['label' => 'Onboarding & Records', 'icon' => 'fa-folder-open']
            ];
            foreach ($depts as $key => $d):
            ?>
            <a href="?status=<?php echo $status_filter; ?>&dept=<?php echo $key; ?>" 
               class="px-6 py-4 rounded-3xl border transition-all flex items-center gap-3 font-bold text-xs
               <?php echo $dept_filter == $key ? 'bg-white border-emerald-500 text-emerald-600 shadow-xl shadow-emerald-100 ring-2 ring-emerald-50' : 'bg-white border-slate-100 text-slate-400 hover:border-emerald-200 hover:text-emerald-600'; ?>">
                <i class="fas <?php echo $d['icon']; ?> text-sm"></i>
                <?php echo $d['label']; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </header>

    <div class="bg-white rounded-[40px] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Invoice</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Patient</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Amount</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50/30 transition-all group">
                        <td class="px-8 py-6">
                            <p class="font-black text-slate-900 text-sm"><?php echo $row['invoice_no']; ?></p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest"><?php echo date('d M Y', strtotime($row['created_at'])); ?></p>
                        </td>
                        <td class="px-8 py-6">
                            <p class="font-bold text-slate-900 text-sm group-hover:text-emerald-600 transition-colors"><?php echo $row['full_name']; ?></p>
                            <p class="text-[10px] text-blue-600 font-black uppercase tracking-widest">#<?php echo $row['file_number']; ?></p>
                        </td>
                        <td class="px-8 py-6">
                            <span class="font-black text-slate-900">₦<?php echo number_format($row['total_amount']); ?></span>
                        </td>
                        <td class="px-8 py-6">
                            <span class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border <?php echo ($row['status']=='Paid') ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-amber-50 text-amber-600 border-amber-100'; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex justify-end gap-2">
                                <?php if ($row['status'] !== 'Paid'): ?>
                                    <button onclick="quickPay(<?php echo $row['id']; ?>, <?php echo $row['total_amount'] - $row['paid_amount']; ?>)" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100/50">Confirm</button>
                                <?php endif; ?>
                                <a href="view_invoice.php?id=<?php echo $row['id']; ?>" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all">Details</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <div class="max-w-xs mx-auto">
                                <div class="w-16 h-16 bg-slate-50 text-slate-200 rounded-3xl flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-folder-open text-2xl"></i>
                                </div>
                                <p class="text-slate-400 font-medium italic">No invoices found for the selected criteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function quickPay(id, amount) {
        if(!confirm('Confirm receipt of ₦' + amount.toLocaleString() + '? This will unlock all linked services.')) return;
        const fd = new FormData();
        fd.append('invoice_id', id);
        fd.append('amount', amount);
        fetch('../api/finance_api.php?action=mark_as_paid', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if(data.success) {
                location.reload();
            } else alert(data.message);
        });
    }
</script>

<?php include 'includes/portal_layout_footer.php'; ?>
