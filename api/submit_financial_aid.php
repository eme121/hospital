<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_SESSION['patient_id'];
    $name = $_POST['name'];
    $amount = $_POST['amount'];
    $reason = $_POST['reason'];
    $image_name = NULL;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $target_dir = "../assets/financial_aid/";
        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $image_name = uniqid() . "." . $ext;
        $target_file = $target_dir . $image_name;
        
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO financial_aid_requests (patient_id, name, amount, reason, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $patient_id, $name, $amount, $reason, $image_name);

    if ($stmt->execute()) {
        if (function_exists('notify_admin')) {
            notify_admin('support', 'New Aid Request', "$name requested ₦" . number_format($amount), 'manage_aid.php');
        }
        echo json_encode(['status' => 'success', 'message' => 'Request submitted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>