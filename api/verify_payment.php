<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

if (!isset($_GET['reference'])) {
    header("Location: ../patient_dashboard.php?status=error&message=No reference found");
    exit;
}

$reference = $_GET['reference'];

// Verify Paystack Transaction
$url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
    "Cache-Control: no-cache",
]);
$result = curl_exec($ch);
$response = json_decode($result, true);

if ($response && $response['status'] && $response['data']['status'] === 'success') {
    $invoice_id = $response['data']['metadata']['invoice_id'];
    $amount_paid = $response['data']['amount'] / 100;

    // 1. Update Payment History
    $stmt = $conn->prepare("UPDATE payment_history SET status = 'confirmed' WHERE reference = ?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();

    // 2. Update Invoice & Linked Appointments via BillingEngine
    require_once 'billing_engine.php';
    $billing = new BillingEngine($conn);
    $billing->applyPayment($invoice_id, $amount_paid);

    // 3. Update Onboarding status if applicable
    $patient_id = $response['data']['metadata']['patient_id'];
    $conn->query("UPDATE patient_onboarding SET payment_status = 'Confirmed', status = 'Paid' WHERE patient_id = $patient_id AND (status = 'Payment Pending' OR status = 'Awaiting Confirmation')");

    log_audit("Verified Gateway Payment", "invoices", $invoice_id, "Amount: ₦" . number_format($amount_paid) . ", Ref: $reference");

    // Redirect logic: If onboarding payment, go back to onboarding to finish the form
    $redirect_url = "../patient_dashboard.php";
    if (isset($response['data']['metadata']['payment_type']) && $response['data']['metadata']['payment_type'] === 'onboarding') {
        $redirect_url = "../onboarding.php";
    }
    
    header("Location: $redirect_url?status=success&message=Payment verified successfully");
} else {
    header("Location: ../patient_dashboard.php?status=error&message=Payment verification failed");
}
?>