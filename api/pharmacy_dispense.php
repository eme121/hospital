<?php
session_start();
if (!isset($_SESSION['pharmacist_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$prescription_id = intval($_POST['prescription_id']);
$med_ids = $_POST['medication_id'];
$quantities = $_POST['quantity'];
$pharmacist_id = $_SESSION['pharmacist_id'];

if (empty($med_ids)) {
    echo json_encode(['success' => false, 'message' => 'Please select at least one medication.']);
    exit;
}

$conn->begin_transaction();

try {
    // Check if medication invoice for this prescription is paid
    $pay_check = $conn->prepare("SELECT inv.status FROM invoices inv JOIN invoice_items itm ON inv.id = itm.invoice_id 
                                 WHERE inv.patient_id = (SELECT patient_id FROM telemedicine_prescriptions WHERE id = ?) 
                                 AND itm.item_type = 'Medication' AND inv.status != 'Cancelled'
                                 ORDER BY inv.created_at DESC LIMIT 1");
    $pay_check->bind_param("i", $prescription_id);
    $pay_check->execute();
    $pay_status = $pay_check->get_result()->fetch_assoc()['status'] ?? 'Pending';

    if ($pay_status !== 'Paid') {
        throw new Exception("Dispensation blocked: Medication payment not confirmed by accountant.");
    }

    foreach ($med_ids as $index => $med_id) {
        $qty = intval($quantities[$index]);
        if ($qty <= 0) continue;

        // Check stock
        $check = $conn->prepare("SELECT stock_quantity, name FROM medications WHERE id = ?");
        $check->bind_param("i", $med_id);
        $check->execute();
        $med = $check->get_result()->fetch_assoc();

        if ($med['stock_quantity'] < $qty) {
            throw new Exception("Insufficient stock for " . $med['name']);
        }

        // Deduct stock
        $deduct = $conn->prepare("UPDATE medications SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $deduct->bind_param("ii", $qty, $med_id);
        $deduct->execute();

        // Log inventory
        $log = $conn->prepare("INSERT INTO inventory_logs (medication_id, change_amount, change_type, reason, performed_by_id) VALUES (?, ?, 'dispensation', ?, ?)");
        $reason = "Prescription #" . $prescription_id;
        $change = -$qty;
        $log->bind_param("iisi", $med_id, $change, $reason, $pharmacist_id);
        $log->execute();

        // Create dispensation record
        $disp = $conn->prepare("INSERT INTO dispensations (prescription_id, medication_id, quantity_dispensed) VALUES (?, ?, ?)");
        $disp->bind_param("iii", $prescription_id, $med_id, $qty);
        $disp->execute();

        // Billing Integration: Gather items for invoice
        $invoice_items[] = [
            'description' => $med['name'],
            'type' => 'Medication',
            'quantity' => $qty,
            'price' => $med['price']
        ];
    }

    // Process the invoice
    if (!empty($invoice_items)) {
        require_once 'billing_engine.php';
        $billing = new BillingEngine($conn);
        
        // Get patient_id from prescription
        $pat_stmt = $conn->prepare("SELECT patient_id FROM telemedicine_prescriptions WHERE id = ?");
        $pat_stmt->bind_param("i", $prescription_id);
        $pat_stmt->execute();
        $pat_res = $pat_stmt->get_result()->fetch_assoc();
        
        if ($pat_res && $pat_res['patient_id']) {
            $billing->automateInvoice($pat_res['patient_id'], $invoice_items);
            
            // Notify Patient
            if (function_exists('notify_patient')) {
                $med_names = array_column($invoice_items, 'description');
                $med_list = implode(', ', $med_names);
                $msg = "Pharmacy has dispensed your medication: $med_list. Please check your billing/dashboard.";
                notify_patient($pat_res['patient_id'], 'pharmacy', 'Medication Dispensed', $msg, 'patient_billing.php');
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>