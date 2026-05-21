<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/sync_helper.php';

if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['records_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$patient_id = (int)($_POST['patient_id'] ?? 0);

if (!$patient_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid Patient ID']);
    exit;
}

switch ($action) {
    case 'approve':
        $stmt = $conn->prepare("UPDATE patient_onboarding SET status = 'Verified' WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
            echo json_encode(['success' => true, 'message' => 'Patient Verified Successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error verifying patient']);
        }
        break;

    case 'lock':
        $stmt = $conn->prepare("UPDATE patient_onboarding SET is_locked = 1 WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
            echo json_encode(['success' => true, 'message' => 'Patient Record Locked']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error locking record']);
        }
        break;

    case 'unlock':
        $stmt = $conn->prepare("UPDATE patient_onboarding SET is_locked = 0 WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
            echo json_encode(['success' => true, 'message' => 'Patient Record Unlocked']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error unlocking record']);
        }
        break;

    case 'send_to_nursing':
        $stmt = $conn->prepare("UPDATE patient_onboarding SET status = 'Sent to Nursing' WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        if ($stmt->execute()) {
            SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
            echo json_encode(['success' => true, 'message' => 'Patient sent to Nursing Queue']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error sending to Nursing']);
        }
        break;

    case 'edit_details':
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        $stmt = $conn->prepare("UPDATE patients SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $patient_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Patient Details Updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating details']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}
?>