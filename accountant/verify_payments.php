<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['accountant_id'])) {
    header('Location: login.php');
    exit;
}

$message = "";

// Handle Verification
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $status = ($action == 'confirm') ? 'confirmed' : 'rejected';

    if ($status == 'confirmed') {
        // Fetch payment details
        $pay_stmt = $conn->prepare("SELECT patient_id, amount, invoice_id FROM payment_history WHERE id = ?");
        $pay_stmt->bind_param("i", $id);
        $pay_stmt->execute();
        $pay = $pay_stmt->get_result()->fetch_assoc();

        if ($pay) {
            // Update patient's paid_amount (Legacy support)
            $update_stmt = $conn->prepare("UPDATE patients SET paid_amount = paid_amount + ? WHERE id = ?");
            $update_stmt->bind_param("di", $pay['amount'], $pay['patient_id']);
            $update_stmt->execute();

            // Billing Integration: Apply payment to invoices
            require_once '../api/billing_engine.php';
            $billing = new BillingEngine($conn);
            
            if ($pay['invoice_id']) {
                $billing->applyPayment($pay['invoice_id'], $pay['amount']);
            } else {
                $remaining = $pay['amount'];
                $inv_stmt = $conn->prepare("SELECT id, total_amount, paid_amount FROM invoices WHERE patient_id = ? AND status != 'Paid' ORDER BY created_at ASC");
                $inv_stmt->bind_param("i", $pay['patient_id']);
                $inv_stmt->execute();
                $invoices = $inv_stmt->get_result();
                
                while ($inv = $invoices->fetch_assoc()) {
                    if ($remaining <= 0) break;
                    $due = $inv['total_amount'] - $inv['paid_amount'];
                    $to_apply = min($remaining, $due);
                    $billing->applyPayment($inv['id'], $to_apply);
                    $remaining -= $to_apply;
                }
            }
            $conn->query("UPDATE patient_onboarding SET payment_status = 'Confirmed', status = 'Paid' WHERE patient_id = {$pay['patient_id']} AND (status = 'Payment Pending' OR status = 'Awaiting Confirmation')");
            
            require_once '../includes/sync_helper.php';
            SyncManager::signal('billing', 'UPDATE', $pay['patient_id']);
        }
    }

    $stmt = $conn->prepare("UPDATE payment_history SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $message = "Transaction marked as " . strtoupper($status);
}

// Fetch payments
$filter = isset($_GET['show_all']) ? "" : "WHERE ph.status = 'pending'";
$query = "SELECT ph.*, p.full_name, p.file_number 
          FROM payment_history ph 
          JOIN patients p ON ph.patient_id = p.id 
          $filter 
          ORDER BY ph.created_at DESC";
$result = $conn->query($query);

$page_title = "Verify Payments";
include 'includes/portal_layout_header.php';
?>

<div class="p-10">
    <header class="flex justify-between items-end mb-12">
        <div>
            <h2 class="text-4xl font-black text-slate-900 mb-2 tracking-tight">Verify Payments</h2>
            <p class="text-slate-500 font-medium">Review and confirm patient financial transactions.</p>
        </div>
        <div class="flex gap-4">
            <a href="?show_all=1" class="px-6 py-4 bg-white border border-slate-200 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-400 hover:text-emerald-600 transition-all">View History</a>
        </div>
    </header>

    <?php if($message): ?>
        <div class="mb-8 p-6 bg-emerald-50 text-emerald-600 rounded-[32px] font-bold border border-emerald-100 flex items-center gap-4 animate-in">
            <i class="fas fa-check-circle text-xl"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-[40px] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Patient</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Method & Proof</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50/30 transition-all">
                        <td class="px-8 py-6">
                            <p class="font-black text-slate-900 text-sm"><?php echo $row['full_name']; ?></p>
                            <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest">#<?php echo $row['file_number']; ?></p>
                        </td>
                        <td class="px-8 py-6 font-black text-slate-900">₦<?php echo number_format($row['amount']); ?></td>
                        <td class="px-8 py-6">
                            <p class="text-xs font-bold text-slate-500 mb-2"><?php echo ucfirst($row['method']); ?></p>
                            <?php if($row['method'] == 'paystack' && $row['status'] == 'pending'): ?>
                                <button onclick="autoVerify('<?php echo $row['reference']; ?>', <?php echo $row['id']; ?>)" class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-[8px] font-black uppercase tracking-widest border border-blue-100 hover:bg-blue-600 hover:text-white transition-all">Auto-Verify</button>
                            <?php endif; ?>
                            <?php if($row['proof_image']): ?>
                                <a href="../assets/payment_proofs/<?php echo $row['proof_image']; ?>" target="_blank" class="text-[10px] font-black text-emerald-600 uppercase tracking-widest hover:underline flex items-center gap-2">
                                    <i class="fas fa-image"></i> View Receipt
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="px-8 py-6">
                            <span class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border <?php echo ($row['status']=='pending') ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex justify-center gap-2">
                                <?php if($row['status'] == 'pending'): ?>
                                    <a href="?action=confirm&id=<?php echo $row['id']; ?>" class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="?action=reject&id=<?php echo $row['id']; ?>" class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function autoVerify(reference, paymentId) {
    if (!reference) {
        Swal.fire('Error', 'No reference found for this transaction.', 'error');
        return;
    }

    Swal.fire({
        title: 'Verifying...',
        text: 'Checking Paystack status for ' + reference,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`../api/verify_paystack.php?reference=${reference}&payment_id=${paymentId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Payment has been automatically verified and confirmed.',
                    icon: 'success',
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Failed', data.message || 'Transaction could not be verified.', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Network error or invalid response.', 'error');
        });
}
</script>

<?php include 'includes/portal_layout_footer.php'; ?>
