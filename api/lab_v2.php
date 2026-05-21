<?php
session_start();
if (!isset($_SESSION['lab_tech_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';
require_once '../includes/clinical_helper.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$tech_id = $_SESSION['lab_tech_id'] ?? 0;

if ($action === 'get_tests') {
    $category = $_GET['category'] ?? '';
    $sql = "SELECT * FROM lab_tests";
    if ($category) $sql .= " WHERE category = '" . $conn->real_escape_string($category) . "'";
    $sql .= " ORDER BY category, test_name";
    echo json_encode(['success' => true, 'tests' => $conn->query($sql)->fetch_all(MYSQLI_ASSOC)]);
}

if ($action === 'save_test') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['test_name'];
    $cat = $_POST['category'];
    $price = $_POST['price'];
    $unit = $_POST['unit'];
    $is_num = $_POST['is_numeric'] ? 1 : 0;
    $min = $_POST['reference_min'] ?: null;
    $max = $_POST['reference_max'] ?: null;
    $desc = $_POST['description'] ?? '';

    if ($id) {
        $stmt = $conn->prepare("UPDATE lab_tests SET test_name=?, category=?, price=?, unit=?, is_numeric=?, reference_min=?, reference_max=?, description=? WHERE id=?");
        $stmt->bind_param("ssisiddsi", $name, $cat, $price, $unit, $is_num, $min, $max, $desc, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO lab_tests (test_name, category, price, unit, is_numeric, reference_min, reference_max, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisidds", $name, $cat, $price, $unit, $is_num, $min, $max, $desc);
    }

    if ($stmt->execute()) {
        SyncManager::signal('lab_requests'); // Refresh test lists
        echo json_encode(['success' => true]);
    } else echo json_encode(['success' => false, 'message' => $conn->error]);
}

if ($action === 'get_requests') {
    $status = $_GET['status'] ?? 'Pending';
    $result = ClinicalHelper::getLabRequests($conn, $status);
    echo json_encode(['success' => true, 'requests' => $result->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'collect_sample') {
    $request_id = $_POST['request_id'];
    $type = $_POST['sample_type'];
    $volume = $_POST['sample_volume'] ?? '';
    $notes = $_POST['notes'] ?? '';

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO lab_samples (request_id, sample_type, sample_volume, collected_by, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $request_id, $type, $volume, $tech_id, $notes);
        $stmt->execute();

        $conn->query("UPDATE lab_requests SET status = 'Sample Collected' WHERE id = $request_id");
        
        // Signal Real-time Update
        SyncManager::signal('lab_requests', 'UPDATE', $request_id);
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

if ($action === 'save_result') {
    $request_id = $_POST['request_id'];
    $findings = $_POST['findings'];
    $numeric_value = $_POST['numeric_value'] ?: null;
    $is_abnormal = isset($_POST['is_abnormal']) ? 1 : 0;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO lab_results (request_id, lab_tech_id, findings, numeric_value, is_abnormal) VALUES (?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE findings=?, numeric_value=?, is_abnormal=?, status='Completed'");
        $stmt->bind_param("iissssis", $request_id, $tech_id, $findings, $numeric_value, $is_abnormal, $findings, $numeric_value, $is_abnormal);
        $stmt->execute();

        $conn->query("UPDATE lab_requests SET status = 'Completed' WHERE id = $request_id");

        // Sync Signals
        SyncManager::signal('lab_requests', 'UPDATE', $request_id);
        SyncManager::signal('clinical_visits', 'UPDATE');

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

if ($action === 'get_patient_results') {
    $patient_id = $_GET['patient_id'];
    $sql = "SELECT r.*, res.findings, res.numeric_value, res.is_abnormal, t.test_name, t.unit, t.reference_min, t.reference_max
            FROM lab_requests r 
            JOIN lab_tests t ON r.test_id = t.id 
            LEFT JOIN lab_results res ON r.id = res.request_id 
            WHERE r.patient_id = ? 
            ORDER BY r.requested_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'results' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}
?>