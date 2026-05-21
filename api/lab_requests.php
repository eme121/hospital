<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';
require_once 'billing_engine.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    if (!isset($_SESSION['doctor_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $patient_id = intval($_POST['patient_id']);
    $case_id = intval($_POST['case_id'] ?? $_POST['visit_id'] ?? 0);
    $test_ids = $_POST['test_ids'] ?? [];
    if (!is_array($test_ids) && isset($_POST['test_id'])) $test_ids = [$_POST['test_id']];
    $manual_test = trim($_POST['manual_test'] ?? '');
    
    if (empty($test_ids) && empty($manual_test)) {
        echo json_encode(['success' => false, 'message' => 'No tests selected.']);
        exit;
    }

    $priority = $_POST['priority'] ?? 'Normal';
    $notes = $_POST['notes'] ?? '';
    $doctor_id = $_SESSION['doctor_id'];

    $conn->begin_transaction();
    try {
        $billing = new BillingEngine($conn);
        $billing_items = [];
        $requested_tests = [];

        // Handle predefined tests
        foreach ($test_ids as $test_id) {
            $test_id = intval($test_id);
            
            // 1. Fetch test details
            $test_stmt = $conn->prepare("SELECT test_name, price FROM lab_tests WHERE id = ?");
            $test_stmt->bind_param("i", $test_id);
            $test_stmt->execute();
            $test = $test_stmt->get_result()->fetch_assoc();

            if (!$test) continue;

            // 2. Insert Lab Request
            $stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, doctor_id, case_id, test_id, priority, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiss", $patient_id, $doctor_id, $case_id, $test_id, $priority, $notes);
            $stmt->execute();
            $request_id = $conn->insert_id;

            $billing_items[] = [
                'description' => "Lab Test: " . $test['test_name'],
                'type' => 'Lab',
                'price' => $test['price']
            ];
            
            $requested_tests[] = $test['test_name'];
            
            // Trigger Real-Time Sync Signal for each request
            SyncManager::signal('lab_requests', 'INSERT', $request_id);
        }

        // Handle manual test input
        if (!empty($manual_test)) {
            $dummy_test_id = 999; // Miscellaneous / Manual
            $stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, doctor_id, case_id, test_id, custom_test_name, priority, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiiss", $patient_id, $doctor_id, $case_id, $dummy_test_id, $manual_test, $priority, $notes);
            $stmt->execute();
            $request_id = $conn->insert_id;

            $billing_items[] = [
                'description' => "Lab Test (Manual): " . $manual_test,
                'type' => 'Lab',
                'price' => 0.00 // Price can be adjusted by laboratory later
            ];

            $requested_tests[] = $manual_test;
            SyncManager::signal('lab_requests', 'INSERT', $request_id);
        }

        if (empty($requested_tests)) {
            throw new Exception("Could not process any of the selected tests.");
        }

        // 3. Billing Integration: Automated Invoice for all Lab Tests
        $billing->automateInvoice($patient_id, $billing_items);

        // 4. SYNC: Automatically post to Telemedicine Chat
        if ($case_id > 0) {
            $chat_msg = "🔬 LAB TESTS REQUESTED: " . implode(", ", $requested_tests) . ($priority === 'Urgent' ? " (URGENT)" : "");
            $chat_stmt = $conn->prepare("INSERT INTO telemedicine_messages (case_id, doctor_id, message) VALUES (?, ?, ?)");
            $chat_stmt->bind_param("iis", $case_id, $doctor_id, $chat_msg);
            $chat_stmt->execute();

            // FEATURE 10: LEDGER
            require_once '../includes/ledger_helper.php';
            log_telemedicine_ledger($conn, $case_id, $doctor_id, 'LAB_REQUESTED', $chat_msg);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => count($requested_tests) . " tests requested."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_status') {
    if (!isset($_SESSION['lab_tech_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE lab_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $request_id);
    
    if ($stmt->execute()) {
        // Trigger Real-Time Sync Signal
        SyncManager::signal('lab_requests', 'UPDATE', $request_id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}

if ($action === 'get_by_case') {
    $case_id = intval($_GET['case_id']);
    $stmt = $conn->prepare("SELECT r.*, t.test_name, res.findings, res.result_file, res.released_at 
                            FROM lab_requests r 
                            JOIN lab_tests t ON r.test_id = t.id 
                            JOIN lab_results res ON r.id = res.request_id 
                            WHERE r.case_id = ? ORDER BY res.released_at DESC");
    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = [];
    while($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    echo json_encode(['success' => true, 'results' => $results]);
}
?>