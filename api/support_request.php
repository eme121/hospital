<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
        exit;
    }

    // Update the request
    $stmt = $conn->prepare("UPDATE financial_aid_requests SET current_amount = current_amount + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $request_id);

    if ($stmt->execute()) {
        // Log this into payment_history for accounting/reports
        // We'll use a dummy patient_id or the recipient's id if we want to track it against them
        $log_stmt = $conn->prepare("INSERT INTO payment_history (patient_id, amount, method, description, status) VALUES (
            (SELECT patient_id FROM financial_aid_requests WHERE id = ?), 
            ?, 'gateway', ?, 'confirmed')");
        $desc = "Financial Aid Contribution via Support Page";
        $log_stmt->bind_param("ids", $request_id, $amount, $desc);
        $log_stmt->execute();

        // Check if goal reached
        $check = $conn->prepare("SELECT amount, current_amount FROM financial_aid_requests WHERE id = ?");
        $check->bind_param("i", $request_id);
        $check->execute();
        $res = $check->get_result()->fetch_assoc();

        if ($res && $res['current_amount'] >= $res['amount']) {
            $conn->query("UPDATE financial_aid_requests SET status = 'completed' WHERE id = $request_id");
        }

        echo json_encode(['status' => 'success', 'message' => 'Support recorded']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>