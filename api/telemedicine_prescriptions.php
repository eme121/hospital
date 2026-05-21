<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id']) && !isset($_SESSION['patient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'create' && isset($_SESSION['doctor_id'])) {
    $case_id = intval($_POST['case_id']);
    $doctor_id = $_SESSION['doctor_id'];
    $patient_id = intval($_POST['patient_id']);
    $medications = $_POST['medications'];
    $dosage = $_POST['dosage'];
    $notes = $_POST['notes'];
    
    // Premium Schedule Fields
    $dosage_times = $_POST['dosage_times'] ?? '08:00, 14:00, 20:00';
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+7 days'));

    $stmt = $conn->prepare("INSERT INTO telemedicine_prescriptions (case_id, doctor_id, patient_id, medications, dosage, notes, dosage_times, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissssss", $case_id, $doctor_id, $patient_id, $medications, $dosage, $notes, $dosage_times, $start_date, $end_date);

    if ($stmt->execute()) {
        $prescription_id = $conn->insert_id;
        // Notify Patient
        if (function_exists('notify_patient')) {
            $msg = "A new prescription for $medications has been added to your record.";
            notify_patient($patient_id, 'prescription', 'New Prescription', $msg, 'telemedicine_dashboard_patient.php');
        }

        // SYNC: Automatically post to Telemedicine Chat
        if ($case_id > 0) {
            $chat_msg = "💊 PRESCRIPTION ADDED: $medications ($dosage)";
            $chat_stmt = $conn->prepare("INSERT INTO telemedicine_messages (case_id, doctor_id, message, message_type) VALUES (?, ?, ?, 'clinical_action')");
            $chat_stmt->bind_param("iis", $case_id, $doctor_id, $chat_msg);
            $chat_stmt->execute();
            SyncManager::signal('telemedicine_chat', 'INSERT', $case_id);

            // FEATURE 10: LEDGER
            require_once '../includes/ledger_helper.php';
            log_telemedicine_ledger($conn, $case_id, $doctor_id, 'PRESCRIPTION_ADDED', $chat_msg);
        }

        SyncManager::signal('prescriptions', 'INSERT', $prescription_id);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

if ($action === 'get') {
    $case_id = intval($_GET['case_id']);
    $stmt = $conn->prepare("SELECT p.*, d.name as doctor_name FROM telemedicine_prescriptions p JOIN telemedicine_doctors d ON p.doctor_id = d.id WHERE p.case_id = ? ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $prescriptions = [];
    while ($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
    }
    echo json_encode(['success' => true, 'prescriptions' => $prescriptions]);
}
?>