<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}
require_once 'includes/db_connect.php';

$id = intval($_GET['id']);
$patient_id = $_SESSION['patient_id'];

$invoice_res = $conn->query("SELECT i.*, p.full_name, p.file_number, p.email, p.phone 
                            FROM invoices i 
                            JOIN patients p ON i.patient_id = p.id 
                            WHERE i.id = $id AND i.patient_id = $patient_id");
$invoice = $invoice_res->fetch_assoc();

if (!$invoice) die("Unauthorized access or invoice not found.");

$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $id");

include 'includes/dashboard_header.php';
?>

<div class="min-h-screen bg-slate-50 py-20 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Action Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 no-print gap-4">
            <a href="patient_billing.php" class="text-slate-400 font-bold hover:text-slate-900 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Billing
            </a>
            <div class="flex gap-4">
                <?php if ($invoice['status'] !== 'Paid'): ?>
                    <button onclick="payNow(<?php echo $id; ?>)" id="payNowBtn" class="px-8 py-3 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-100 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Pay Online Now
                    </button>
                <?php endif; ?>
                <button onclick="window.print()" class="px-8 py-3 bg-slate-900 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-200">
                    Print Receipt
                </button>
            </div>
        </div>

        <!-- Invoice Card (Identical to Admin but for consistency) -->
        <div class="bg-white rounded-[40px] shadow-2xl border border-slate-100 overflow-hidden" id="printable-invoice">
            <div class="p-16 border-b-4 border-slate-900 bg-slate-50 flex justify-between items-start">
                <div>
                    <span class="text-3xl font-black text-slate-900 tracking-tighter mb-2 block">HOPE HAVEN HOSPITAL</span>
                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-[0.3em]">Hospital Billing Dept</p>
                    <div class="mt-8 text-sm font-medium text-slate-500">
                        <p>123 Medical Drive, Health Plaza</p>
                        <p>billing@hopehaven.com</p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-5xl font-black text-slate-900 mb-2">RECEIPT</h2>
                    <p class="text-xl font-black text-blue-600"><?php echo $invoice['invoice_no']; ?></p>
                    <div class="mt-8 text-sm font-bold text-slate-900 uppercase tracking-widest">
                        <p class="text-slate-400">Date</p>
                        <p><?php echo date('d M Y', strtotime($invoice['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="px-16 py-12 grid grid-cols-2 gap-12 bg-white">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Patient Information</p>
                    <h3 class="text-2xl font-black text-slate-900 mb-2"><?php echo htmlspecialchars($invoice['full_name']); ?></h3>
                    <p class="text-sm font-bold text-blue-600 mb-4"><?php echo $invoice['file_number']; ?></p>
                </div>
                <div class="text-right flex flex-col justify-end">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Payment Status</p>
                    <div class="inline-block ml-auto px-6 py-2 rounded-full text-xs font-black uppercase tracking-widest
                        <?php echo $invoice['status'] == 'Paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'; ?>">
                        <?php echo $invoice['status']; ?>
                    </div>
                </div>
            </div>

            <div class="px-16 py-8">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b-2 border-slate-100">
                            <th class="py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                            <th class="py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Qty</th>
                            <th class="py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td class="py-6 font-bold text-slate-900"><?php echo htmlspecialchars($item['item_description']); ?></td>
                            <td class="py-6 text-center font-bold text-slate-600"><?php echo $item['quantity']; ?></td>
                            <td class="py-6 text-right font-black text-slate-900">₦<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-16 py-12 bg-slate-50/50 flex justify-end">
                <div class="w-80 space-y-4">
                    <div class="flex justify-between text-sm font-bold text-slate-500">
                        <span>Total Amount</span>
                        <span>₦<?php echo number_format($invoice['total_amount'], 2); ?></span>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-emerald-600">
                        <span>Amount Paid</span>
                        <span>- ₦<?php echo number_format($invoice['paid_amount'], 2); ?></span>
                    </div>
                    <div class="pt-4 border-t border-slate-200 flex justify-between items-center">
                        <span class="text-lg font-black text-slate-900 uppercase tracking-tight">Balance Due</span>
                        <span class="text-3xl font-black text-rose-600">₦<?php echo number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="p-16 border-t border-slate-100 text-center">
                <p class="text-sm font-bold text-slate-900 mb-2 uppercase tracking-widest">Thank you for choosing Hope Haven</p>
            </div>
        </div>
    </div>
</div>

<script>
function payNow(invoiceId) {
    const btn = document.getElementById('payNowBtn');
    const originalContent = btn.innerHTML;
    
    btn.innerHTML = `
        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Initializing...
    `;
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
            alert(data.message || 'Could not initialize payment.');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An unexpected error occurred.');
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
}
</script>

<style>
@media print {
    body { background: white; }
    .no-print { display: none !important; }
    main { padding: 0 !important; }
    #printable-invoice { box-shadow: none !important; border: none !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
