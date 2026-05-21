<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$doctor_id = $_SESSION['doctor_id'];

if ($action === 'bypass_payment') {
    $patient_id = intval($_POST['patient_id']);
    
    // Check if the patient exists in queue
    $check = $conn->query("SELECT current_stage FROM patient_queue_status WHERE patient_id = $patient_id");
    
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        
        // Force stage to Doctor and Mark as Authorized Bypass
        $stmt = $conn->prepare("UPDATE patient_queue_status 
                               SET current_stage = 'Doctor', 
                                   status = 'Authorized Bypass', 
                                   doctor_id = ?,
                                   notes = CONCAT(COALESCE(notes,''), ' [Payment Bypassed by Doctor]') 
                               WHERE patient_id = ?");
        $stmt->bind_param("ii", $doctor_id, $patient_id);
        
        if ($stmt->execute()) {
            SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
            echo json_encode(['success' => true, 'message' => 'Clinical access authorized for Dr. ' . $_SESSION['doctor_name']]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        } else {
        // If not in queue at all, create an entry for them
        $stmt = $conn->prepare("INSERT INTO patient_queue_status (patient_id, current_stage, status, notes, doctor_id) VALUES (?, 'Doctor', 'Authorized Bypass', 'Emergency Bypass by Doctor', ?)");
        $stmt->bind_param("ii", $patient_id, $doctor_id);
        if ($stmt->execute()) {
            SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
            echo json_encode(['success' => true, 'message' => 'New clinical session initialized via Bypass.']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        }
        }
?>