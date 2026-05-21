<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}
require_once 'includes/db_connect.php';

$patient_id = $_SESSION['patient_id'];
$invoices = $conn->query("SELECT * FROM invoices WHERE patient_id = $patient_id ORDER BY created_at DESC");

include 'includes/dashboard_header.php';
?>

<main class="min-h-screen bg-slate-50 py-24">
    <div class="max-w-6xl mx-auto px-4">
        <header class="mb-12 flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Billing & Invoices</h1>
                <p class="text-slate-500 font-medium">Track your medical expenses and download receipts.</p>
            </div>
            <a href="patient_dashboard.php" class="px-8 py-3 bg-white border border-slate-100 rounded-xl font-bold text-xs uppercase text-slate-500 hover:text-slate-900 transition-all shadow-sm">Dashboard</a>
        </header>

        <div class="grid grid-cols-1 gap-6">
            <?php if ($invoices->num_rows == 0): ?>
                <div class="bg-white rounded-[40px] p-20 text-center border border-dashed border-slate-200">
                    <p class="text-slate-400 font-bold italic">No billing records found.</p>
                </div>
            <?php else: ?>
                <?php while($row = $invoices->fetch_assoc()): ?>
                <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm p-8 flex flex-col md:flex-row gap-8 items-center justify-between hover:shadow-xl transition-all">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900"><?php echo $row['invoice_no']; ?></h3>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?php echo date('d M Y', strtotime($row['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="flex-1 px-8">
                        <div class="flex justify-between items-end mb-2">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Payment Status</p>
                            <span class="text-lg font-black text-slate-900">₦<?php echo number_format($row['total_amount'], 2); ?></span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                            <?php $perc = ($row['total_amount'] > 0) ? ($row['paid_amount'] / $row['total_amount'] * 100) : 0; ?>
                            <div class="h-full bg-emerald-500 rounded-full" style="width: <?php echo $perc; ?>%"></div>
                        </div>
                        <div class="flex justify-between mt-2">
                            <p class="text-[10px] font-bold text-emerald-600 uppercase">Paid: ₦<?php echo number_format($row['paid_amount'], 2); ?></p>
                            <p class="text-[10px] font-bold text-rose-600 uppercase">Due: ₦<?php echo number_format($row['total_amount'] - $row['paid_amount'], 2); ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <?php if ($row['status'] != 'Paid'): ?>
                        <button onclick="payOnline(<?php echo $row['id']; ?>)" id="btn-<?php echo $row['id']; ?>" class="px-8 py-3 bg-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200">
                            Pay Online
                        </button>
                        <?php endif; ?>
                        
                        <span class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest
                            <?php echo $row['status'] == 'Paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                        <a href="view_invoice_patient.php?id=<?php echo $row['id']; ?>" class="p-4 bg-slate-900 text-white rounded-2xl hover:bg-blue-600 transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function payOnline(invoiceId) {
    const btn = document.getElementById('btn-' + invoiceId);
    const originalText = btn.innerText;
    
    btn.innerText = 'Initializing...';
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
            Swal.fire({
                icon: 'error',
                title: 'Payment Error',
                text: data.message || 'Could not initialize payment.'
            });
            btn.innerText = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'System Error',
            text: 'An unexpected error occurred.'
        });
        btn.innerText = originalText;
        btn.disabled = false;
    });
}
</script>

<?php include 'includes/footer.php'; ?>
