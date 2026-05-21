<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_SESSION['patient_id'];
    $invoice_id = intval($_POST['invoice_id']);

    // Get Invoice Details
    $stmt = $conn->prepare("SELECT total_amount - paid_amount as balance, invoice_no FROM invoices WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $invoice_id, $patient_id);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();

    if (!$invoice || $invoice['balance'] <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid invoice or already paid']);
        exit;
    }

    // Get Patient Email
    $p_stmt = $conn->prepare("SELECT email FROM patients WHERE id = ?");
    $p_stmt->bind_param("i", $patient_id);
    $p_stmt->execute();
    $patient = $p_stmt->get_result()->fetch_assoc();

    $amount = $invoice['balance'] * 100; // Paystack expects kobo
    $email = $patient['email'];
    $reference = "HHH-" . uniqid() . "-" . $invoice_id;

    // Initialize Paystack
    $url = "https://api.paystack.co/transaction/initialize";
    $fields = [
        'email' => $email,
        'amount' => $amount,
        'reference' => $reference,
        'callback_url' => BASE_URL . "/api/verify_payment.php",
        'metadata' => [
            'invoice_id' => $invoice_id,
            'patient_id' => $patient_id
        ]
    ];

    $fields_string = http_build_query($fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
        "Cache-Control: no-cache",
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local environments without CA certs

    $result = curl_exec($ch);
    $curl_error = curl_error($ch);
    $response = json_decode($result, true);

    if ($result === false) {
        echo json_encode(['status' => 'error', 'message' => 'CURL Error: ' . $curl_error]);
        exit;
    }

    if ($response && $response['status']) {
        // Log the pending transaction
        $desc = "Online Payment for Invoice #" . $invoice['invoice_no'];
        $stmt = $conn->prepare("INSERT INTO payment_history (patient_id, invoice_id, amount, reference, method, description, status) VALUES (?, ?, ?, ?, 'gateway', ?, 'pending')");
        $bal = $invoice['balance'];
        $stmt->bind_param("iidss", $patient_id, $invoice_id, $bal, $reference, $desc);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'authorization_url' => $response['data']['authorization_url']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Paystack initialization failed: ' . ($response['message'] ?? 'Unknown error')]);
    }
}
?>