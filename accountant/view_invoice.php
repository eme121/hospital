<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['accountant_id'])) {
    header('Location: login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$invoice_res = $conn->query("SELECT i.*, p.full_name, p.file_number, p.phone, p.email, pfd.field_value as address 
                             FROM invoices i 
                             JOIN patients p ON i.patient_id = p.id 
                             LEFT JOIN patient_form_data pfd ON (p.id = pfd.patient_id AND pfd.section_name = 'Contact/NOK' AND pfd.field_name = 'address')
                             WHERE i.id = $id");

if (!$invoice_res) {
    die("Query Error: " . $conn->error);
}

$invoice = $invoice_res->fetch_assoc();

if (!$invoice) {
    header('Location: manage_invoices.php');
    exit;
}

$items_res = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $id");
$items = [];
while($row = $items_res->fetch_assoc()) $items[] = $row;

$is_patient_view = isset($_GET['view']) && $_GET['view'] === 'patient';

$page_title = "Invoice " . $invoice['invoice_no'];
include 'includes/portal_layout_header.php';
?>

<div class="p-10">
    <header class="flex justify-between items-center mb-12 no-print">
        <a href="manage_invoices.php" class="text-slate-400 hover:text-slate-900 font-bold flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Registry
        </a>
        <div class="flex gap-4">
            <?php if ($is_patient_view): ?>
                <a href="?id=<?php echo $id; ?>" class="px-6 py-4 bg-white border border-slate-200 rounded-2xl font-black text-xs uppercase tracking-widest text-blue-600 hover:bg-blue-50 transition-all flex items-center gap-2">
                    <i class="fas fa-eye"></i> Internal View
                </a>
            <?php else: ?>
                <a href="?id=<?php echo $id; ?>&view=patient" class="px-6 py-4 bg-white border border-slate-200 rounded-2xl font-black text-xs uppercase tracking-widest text-emerald-600 hover:bg-emerald-50 transition-all flex items-center gap-2">
                    <i class="fas fa-user-tag"></i> Patient View
                </a>
            <?php endif; ?>

            <button onclick="window.print()" class="px-6 py-4 bg-white border border-slate-200 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-400 hover:text-emerald-600 transition-all flex items-center gap-2">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <?php if ($invoice['status'] !== 'Paid'): ?>
                <button onclick="confirmPayment(<?php echo $id; ?>, <?php echo $invoice['total_amount'] - $invoice['paid_amount']; ?>)" class="px-8 py-4 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-100 transition-all hover:bg-emerald-700">
                    Confirm Payment
                </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="bg-white rounded-[40px] border border-slate-100 shadow-xl overflow-hidden print-padding">
        <div class="p-12 border-b border-slate-50 bg-slate-50/30">
            <div class="flex justify-between items-start mb-12">
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-emerald-600 rounded-xl flex items-center justify-center text-white text-xl font-black">H</div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tighter">HOPE<span class="text-emerald-600">HAVEN</span></h1>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Invoice From</p>
                    <p class="text-sm font-black text-slate-900">Hope Haven Hospital Finance Dept</p>
                    <p class="text-xs font-medium text-slate-500">info@hopehaven.com | +234 800 123 4567</p>
                </div>
                <div class="text-right">
                    <h2 class="text-3xl font-black text-slate-900 mb-2"><?php echo $is_patient_view ? 'OFFICIAL RECEIPT' : 'INVOICE'; ?></h2>
                    <p class="text-sm font-black text-emerald-600 uppercase tracking-widest"><?php echo $invoice['invoice_no']; ?></p>
                    <div class="mt-6">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Date Issued</p>
                        <p class="text-sm font-black text-slate-900"><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-12">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Bill To</p>
                    <p class="text-lg font-black text-slate-900"><?php echo $invoice['full_name']; ?></p>
                    <p class="text-xs font-black text-blue-600 uppercase tracking-widest mb-2">#<?php echo $invoice['file_number']; ?></p>
                    <p class="text-sm font-medium text-slate-500 leading-relaxed"><?php echo nl2br(htmlspecialchars($invoice['address'])); ?></p>
                    <p class="text-sm font-bold text-slate-700 mt-2"><?php echo $invoice['phone']; ?></p>
                </div>
                <div class="bg-slate-900 rounded-[32px] p-8 text-white relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Amount Due</p>
                        <h3 class="text-4xl font-black mb-6">₦<?php echo number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?></h3>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Status</p>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-[10px] font-black uppercase tracking-widest"><?php echo $invoice['status']; ?></span>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Due Date</p>
                                <p class="text-sm font-bold"><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <i class="fas fa-wallet absolute -right-4 -bottom-4 text-8xl text-white/5 transform -rotate-12"></i>
                </div>
            </div>
        </div>

        <div class="p-12">
            <table class="w-full text-left mb-12">
                <thead>
                    <tr class="border-b-2 border-slate-100">
                        <th class="py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                        <th class="py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Category</th>
                        <th class="py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Qty</th>
                        <?php if (!$is_patient_view): ?>
                            <th class="py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Unit Price</th>
                            <th class="py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Subtotal</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($items as $item): ?>
                        <tr>
                            <td class="py-6 pr-8">
                                <p class="text-sm font-black text-slate-900"><?php echo $item['item_description']; ?></p>
                            </td>
                            <td class="py-6">
                                <span class="px-2 py-1 bg-slate-100 rounded text-[9px] font-black uppercase text-slate-500"><?php echo $item['item_type']; ?></span>
                            </td>
                            <td class="py-6 text-center font-bold text-slate-700 text-sm"><?php echo $item['quantity']; ?></td>
                            <?php if (!$is_patient_view): ?>
                                <td class="py-6 text-right font-bold text-slate-700 text-sm">₦<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="py-6 text-right font-black text-slate-900 text-sm">₦<?php echo number_format($item['subtotal'], 2); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="flex justify-end border-t-2 border-slate-100 pt-8">
                <div class="w-72 space-y-4">
                    <div class="flex justify-between items-center text-sm">
                        <p class="font-bold text-slate-400 uppercase tracking-widest">Total Amount</p>
                        <p class="font-black text-slate-900">₦<?php echo number_format($invoice['total_amount'], 2); ?></p>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <p class="font-bold text-slate-400 uppercase tracking-widest">Amount Paid</p>
                        <p class="font-black text-emerald-600">₦<?php echo number_format($invoice['paid_amount'], 2); ?></p>
                    </div>
                    <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                        <p class="font-black text-slate-900 uppercase tracking-widest">Balance Due</p>
                        <p class="text-xl font-black text-slate-900">₦<?php echo number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-12 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center no-print">
            <p class="text-xs font-medium text-slate-400">Please contact finance@hopehaven.com for any billing inquiries.</p>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Verified Hospital Document</p>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmPayment(id, amount) {
        if(!confirm('Confirm immediate payment receipt of ₦' + amount.toLocaleString() + '?')) return;
        const fd = new FormData();
        fd.append('invoice_id', id);
        fd.append('amount', amount);
        fetch('../api/finance_api.php?action=mark_as_paid', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if(data.success) location.reload();
            else alert(data.message);
        });
    }
</script>

<?php include 'includes/portal_layout_footer.php'; ?>
