<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';
require_once 'billing_engine.php';

header('Content-Type: application/json');

if (!isset($_SESSION['accountant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only accountants can perform adjustments.']);
    exit;
}

$action = $_GET['action'] ?? '';
$accountant_id = $_SESSION['accountant_id'];

function logFinanceAction($conn, $action, $entity_type, $entity_id, $details) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_type, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?, 'admin', ?, ?, ?, ?, ?, ?)");
    $user_type = 'admin'; // Accountants are treated as admin-level for auditing
    $stmt->bind_param("isssisss", $_SESSION['accountant_id'], $action, $entity_type, $entity_id, $details, $ip, $ua);
    $stmt->execute();
}

if ($action === 'adjust_item') {
    $item_id = intval($_POST['item_id']);
    $new_price = floatval($_POST['unit_price']);
    $new_qty = intval($_POST['quantity']);
    
    // Get old values for logging
    $old_res = $conn->query("SELECT itm.*, inv.invoice_no FROM invoice_items itm JOIN invoices inv ON itm.invoice_id = inv.id WHERE itm.id = $item_id");
    $old = $old_res->fetch_assoc();
    
    if (!$old) {
        echo json_encode(['success' => false, 'message' => 'Item not found.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE invoice_items SET unit_price = ?, quantity = ? WHERE id = ?");
        $stmt->bind_param("dii", $new_price, $new_qty, $item_id);
        $stmt->execute();
        
        // Refresh invoice total
        $billing = new BillingEngine($conn);
        $billing->updateInvoiceTotal($old['invoice_id']);
        
        $details = "Adjusted item '{$old['item_description']}' on INV {$old['invoice_no']}. Old Price: {$old['unit_price']}, New Price: $new_price. Old Qty: {$old['quantity']}, New Qty: $new_qty.";
        logFinanceAction($conn, 'Adjusted Invoice Item', 'invoice_items', $item_id, $details);
        
        $conn->commit();

        SyncManager::signal('billing', 'UPDATE', $old['invoice_id']);

        echo json_encode(['success' => true, 'message' => 'Item adjusted successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

if ($action === 'mark_as_paid') {
    $invoice_id = intval($_POST['invoice_id']);
    $amount = floatval($_POST['amount']); // Usually full amount
    
    $inv_res = $conn->query("SELECT * FROM invoices WHERE id = $invoice_id");
    $inv = $inv_res->fetch_assoc();
    
    if (!$inv) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
        exit;
    }

    $billing = new BillingEngine($conn);
    if ($billing->applyPayment($invoice_id, $amount)) {
        logFinanceAction($conn, 'Manual Payment Applied', 'invoices', $invoice_id, "Accountant manually applied payment of ₦" . number_format($amount) . " to INV {$inv['invoice_no']}. Status updated to Paid.");
        
        SyncManager::signal('billing', 'UPDATE', $invoice_id);

        echo json_encode(['success' => true, 'message' => 'Payment applied successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to apply payment.']);
    }
}

if ($action === 'delete_item') {
    $item_id = intval($_POST['item_id']);
    
    $old_res = $conn->query("SELECT itm.*, inv.invoice_no FROM invoice_items itm JOIN invoices inv ON itm.invoice_id = inv.id WHERE itm.id = $item_id");
    $old = $old_res->fetch_assoc();
    
    if (!$old) {
        echo json_encode(['success' => false, 'message' => 'Item not found.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM invoice_items WHERE id = $item_id");
        
        $billing = new BillingEngine($conn);
        $billing->updateInvoiceTotal($old['invoice_id']);
        
        logFinanceAction($conn, 'Deleted Invoice Item', 'invoice_items', $item_id, "Deleted '{$old['item_description']}' from INV {$old['invoice_no']}. Value was ₦" . number_format($old['subtotal']));
        
        $conn->commit();

        SyncManager::signal('billing', 'DELETE', $old['invoice_id']);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>