<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_SESSION['patient_id'];
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'];
    $description = $_POST['description'];
    $proof_name = NULL;

    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
        $target_dir = "../assets/payment_proofs/";
        $ext = pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
        $proof_name = "PROOF_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES["proof"]["tmp_name"], $target_dir . $proof_name);
    }

    $stmt = $conn->prepare("INSERT INTO payment_history (patient_id, amount, method, description, proof_image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $patient_id, $amount, $method, $description, $proof_name);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Evidence submitted. Awaiting verification.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
}
?>