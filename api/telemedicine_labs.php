<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';
require_once 'billing_engine.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? '';
$doctor_id = $_SESSION['doctor_id'];

if ($action === 'create') {
    $case_id = intval($_POST['case_id']);
    $patient_id = intval($_POST['patient_id']);
    $test_ids = $_POST['test_ids'] ?? []; // Array of test IDs
    $manual_test = $_POST['manual_test'] ?? '';
    $priority = $_POST['priority'] ?? 'Normal';
    $notes = $_POST['notes'] ?? '';

    if (empty($test_ids) && empty($manual_test)) {
        echo json_encode(['success' => false, 'message' => 'No tests selected.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $billing_items = [];
        $billing = new BillingEngine($conn);
        $tests_ordered = [];

        // Handle specific test IDs
        if (!empty($test_ids)) {
            foreach ($test_ids as $tid) {
                $tid = intval($tid);
                $res = $conn->query("SELECT test_name, price FROM lab_tests WHERE id = $tid");
                if ($test = $res->fetch_assoc()) {
                    $stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, doctor_id, case_id, test_id, priority, notes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiiiss", $patient_id, $doctor_id, $case_id, $tid, $priority, $notes);
                    $stmt->execute();
                    
                    $tests_ordered[] = $test['test_name'];
                    $billing_items[] = [
                        'description' => "Lab Test: " . $test['test_name'],
                        'type' => 'Lab',
                        'price' => $test['price']
                    ];
                }
            }
        }

        // Handle manual test entry
        if (!empty($manual_test)) {
            $stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, doctor_id, case_id, test_id, custom_test_name, priority, notes) VALUES (?, ?, ?, 0, ?, ?, ?)");
            $stmt->bind_param("iiiisss", $patient_id, $doctor_id, $case_id, $manual_test, $priority, $notes);
            $stmt->execute();
            $tests_ordered[] = $manual_test;
            
            $billing_items[] = [
                'description' => "Lab Test: " . $manual_test,
                'type' => 'Lab',
                'price' => 5000 // Default price for custom tests
            ];
        }

        // Automated Invoice
        if (!empty($billing_items)) {
            $billing->automateInvoice($patient_id, $billing_items);
        }

        // SYNC: Post to Telemedicine Chat
        $tests_str = implode(', ', $tests_ordered);
        $chat_msg = "🧪 LAB ORDER PLACED: $tests_str";
        $chat_stmt = $conn->prepare("INSERT INTO telemedicine_messages (case_id, doctor_id, message, message_type) VALUES (?, ?, ?, 'clinical_action')");
        $chat_stmt->bind_param("iis", $case_id, $doctor_id, $chat_msg);
        $chat_stmt->execute();

        $conn->commit();
        
        SyncManager::signal('telemedicine_chat', 'INSERT', $case_id);
        SyncManager::signal('lab_requests', 'INSERT');

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>