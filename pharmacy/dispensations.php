<?php
session_start();
if (!isset($_SESSION['pharmacist_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';
include '../includes/portal_head.php';

$dispensations = $conn->query("SELECT d.*, p.full_name as patient_name, p.file_number, u.name as pharmacist_name, i.status as payment_status 
                               FROM pharmacy_dispensations d 
                               JOIN patients p ON d.patient_id = p.id 
                               LEFT JOIN pharmacists u ON d.pharmacist_id = u.id 
                               LEFT JOIN invoices i ON d.invoice_id = i.id
                               ORDER BY d.dispensed_at DESC");
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 lg:px-12 shrink-0 shadow-sm z-10">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight uppercase">Dispensation <span class="text-emerald-600">History</span></h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Historical fulfillment records</p>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="location.reload()" class="w-10 h-10 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all shadow-sm">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 lg:p-12 custom-scrollbar">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white rounded-[40px] border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">Reference</th>
                                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">Patient</th>
                                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">Value</th>
                                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">Status</th>
                                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">Billing</th>
                                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if($dispensations->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="6" class="px-8 py-20 text-center">
                                            <p class="text-slate-400 font-bold italic tracking-wide">No dispensation records found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while($row = $dispensations->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/80 transition-all group">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-3">
                                                <div class="w-2 h-2 rounded-full <?php echo $row['status'] == 'Dispensed' ? 'bg-emerald-500' : 'bg-amber-500'; ?>"></div>
                                                <div>
                                                    <p class="font-black text-slate-900 tracking-tight">#D-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></p>
                                                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5"><?php echo date('d M, Y', strtotime($row['dispensed_at'])); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <p class="font-black text-slate-900"><?php echo htmlspecialchars($row['patient_name']); ?></p>
                                            <p class="text-[10px] text-slate-400 font-bold tracking-widest uppercase mt-0.5"><?php echo $row['file_number']; ?></p>
                                        </td>
                                        <td class="px-8 py-6">
                                            <p class="font-black text-slate-900">₦<?php echo number_format($row['total_amount'], 2); ?></p>
                                        </td>
                                        <td class="px-8 py-6">
                                            <span class="px-4 py-1.5 <?php 
                                                if($row['status'] == 'Dispensed') echo 'bg-emerald-50 text-emerald-600 border-emerald-100';
                                                elseif($row['status'] == 'Awaiting Payment') echo 'bg-amber-50 text-amber-600 border-amber-100';
                                                else echo 'bg-rose-50 text-rose-600 border-rose-100'; 
                                            ?> text-[9px] font-black uppercase rounded-lg border">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-2">
                                                <i data-lucide="<?php echo $row['payment_status'] == 'Paid' ? 'shield-check' : 'shield-alert'; ?>" class="w-3.5 h-3.5 <?php echo $row['payment_status'] == 'Paid' ? 'text-emerald-500' : 'text-slate-300'; ?>"></i>
                                                <span class="<?php echo $row['payment_status'] == 'Paid' ? 'text-emerald-600' : 'text-slate-400'; ?> text-[10px] font-black uppercase tracking-widest">
                                                    <?php echo $row['payment_status'] ?? 'Pending'; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button onclick="viewDispensation(<?php echo $row['id']; ?>)" class="p-2.5 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-900 hover:text-white transition-all shadow-sm" title="Quick View">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>
                                                <?php if($row['status'] == 'Dispensed'): ?>
                                                <button onclick="reverseDispensation(<?php echo $row['id']; ?>)" class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Reverse Transaction">
                                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                                </button>
                                                <?php endif; ?>
                                                <a href="dispense.php?id=<?php echo $row['prescription_id']; ?>&patient_id=<?php echo $row['patient_id']; ?>" class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Detailed Workspace">
                                                    <i data-lucide="layout-grid" class="w-4 h-4"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('viewModal')"></div>
    <div class="relative bg-white rounded-[40px] w-full max-w-xl shadow-2xl overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
        <div class="p-10 border-b border-slate-50 bg-slate-50/50 flex justify-between items-start shrink-0">
            <div>
                <h3 class="text-3xl font-black text-slate-900 uppercase tracking-tight">Order Details</h3>
                <p id="view_disp_id" class="text-emerald-600 font-black text-xs uppercase tracking-[0.2em] mt-2"></p>
            </div>
            <button onclick="closeModal('viewModal')" class="p-3 bg-white text-slate-400 hover:text-rose-500 rounded-2xl transition-all shadow-sm">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-10 overflow-y-auto custom-scrollbar flex-1">
            <div id="view_content" class="space-y-8">
                <!-- Loaded via AJAX -->
            </div>
        </div>
        <div class="p-8 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
            <button onclick="closeModal('viewModal')" class="px-8 py-3 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition-all">Close</button>
        </div>
    </div>
</div>

<script>
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function viewDispensation(id) {
    document.getElementById('view_disp_id').textContent = '#REF-' + String(id).padStart(5, '0');
    document.getElementById('view_content').innerHTML = `
        <div class="flex flex-col items-center py-10">
            <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest">Retrieving Secure Data...</p>
        </div>
    `;
    document.getElementById('viewModal').classList.remove('hidden');
    
    fetch(`../api/pharmacy_v2.php?action=get_dispensation_details&id=${id}`)
    .then(r => r.json()).then(data => {
        if(data.success) {
            let itemsHtml = `
                <div class="space-y-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">Medication Inventory</p>
                    <div class="bg-slate-50 rounded-[2.5rem] p-6 border border-slate-100 space-y-4">
            `;
            data.items.forEach(item => {
                itemsHtml += `
                    <div class="flex justify-between items-center pb-4 border-b border-slate-200 last:border-0 last:pb-0">
                        <div>
                            <p class="font-black text-slate-900">${item.drug_name}</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">${item.form_type} &bull; ${item.quantity} units</p>
                        </div>
                        <p class="font-black text-slate-900 tracking-tight">₦${parseFloat(item.subtotal).toLocaleString(undefined, {minimumFractionDigits:2})}</p>
                    </div>
                `;
            });
            itemsHtml += `</div></div>`;
            
            let infoHtml = `
                <div class="grid grid-cols-2 gap-8">
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Pharmacist</p>
                        <p class="text-sm font-black text-slate-900">${data.disp.pharmacist_name}</p>
                    </div>
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Patient</p>
                        <p class="text-sm font-black text-slate-900">${data.disp.patient_name}</p>
                    </div>
                </div>
                ${data.disp.notes ? `
                <div class="space-y-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Order Notes</p>
                    <div class="p-6 bg-amber-50 rounded-3xl border border-amber-100 text-sm font-bold text-amber-900 leading-relaxed italic">
                        "${data.disp.notes}"
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('view_content').innerHTML = itemsHtml + infoHtml;
            lucide.createIcons();
        }
    });
}

function reverseDispensation(id) {
    const reason = prompt("Enter reversal authorization reason:");
    if(!reason) return;
    
    if(!confirm("Authorize Stock Reversal? This action will return items to inventory and cancel associated billing.")) return;
    
    const formData = new FormData();
    formData.append('dispensation_id', id);
    formData.append('reason', reason);
    
    fetch('../api/pharmacy_v2.php?action=reverse_dispensation', { method: 'POST', body: formData })
    .then(r => r.json()).then(data => {
        if(data.success) {
            alert('Authorization Success: Transaction Reversed.');
            location.reload();
        } else {
            alert('Authorization Failed: ' + data.message);
        }
    });
}
</script>

<?php include '../includes/portal_footer.php'; ?>
</body>
</html>