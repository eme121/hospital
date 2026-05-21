<?php
session_start();
if (!isset($_SESSION['pharmacist_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $batch_number = $_POST['batch_number'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? NULL;
    $category = $_POST['category'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $price = $_POST['price'] ?? 0;
    $reorder_level = $_POST['reorder_level'] ?? 0;
    $description = $_POST['description'] ?? '';

    if ($id) {
        $stmt = $conn->prepare("UPDATE medications SET name=?, batch_number=?, expiry_date=?, category=?, unit=?, price=?, reorder_level=?, description=? WHERE id=?");
        $stmt->bind_param("ssssssisi", $name, $batch_number, $expiry_date, $category, $unit, $price, $reorder_level, $description, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO medications (name, batch_number, expiry_date, category, unit, price, reorder_level, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssis", $name, $batch_number, $expiry_date, $category, $unit, $price, $reorder_level, $description);
    }

    if ($stmt->execute()) {
        $med_id = $id ?: $conn->insert_id;
        log_audit($id ? "Updated Medication" : "Added Medication", "medications", $med_id, "Name: $name, Batch: $batch_number");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}

if ($action === 'restock') {
    $id = $_POST['id'];
    $amount = intval($_POST['amount']);
    $batch_number = $_POST['batch_number'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $pharmacist_id = $_SESSION['pharmacist_id'];

    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Update stock quantity
        if (!empty($batch_number) || !empty($expiry_date)) {
            $sql = "UPDATE medications SET stock_quantity = stock_quantity + ?";
            if (!empty($batch_number)) $sql .= ", batch_number = '$batch_number'";
            if (!empty($expiry_date)) $sql .= ", expiry_date = '$expiry_date'";
            $sql .= " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $amount, $id);
        } else {
            $stmt = $conn->prepare("UPDATE medications SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmt->bind_param("ii", $amount, $id);
        }
        
        if (!$stmt->execute()) throw new Exception($conn->error);

        $reason = "Manual restock" . (!empty($batch_number) ? " (Batch: $batch_number)" : "");
        $log_stmt = $conn->prepare("INSERT INTO inventory_logs (medication_id, change_amount, change_type, reason, performed_by_id) VALUES (?, ?, 'restock', ?, ?)");
        $log_stmt->bind_param("iisi", $id, $amount, $reason, $pharmacist_id);
        $log_stmt->execute();

        $conn->commit();
        log_audit("Restocked Medication", "medications", $id, "Added $amount units. Reason: $reason");
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>