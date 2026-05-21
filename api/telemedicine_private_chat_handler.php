<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';

header('Content-Type: application/json');

$is_doctor = isset($_SESSION['doctor_id']);
$is_patient = isset($_SESSION['patient_id']);

if (!$is_doctor && !$is_patient) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$user_id = $is_doctor ? $_SESSION['doctor_id'] : $_SESSION['patient_id'];
$user_type = $is_doctor ? 'doctor' : 'patient';
$action = $_GET['action'] ?? '';

// Auto-Migration: Ensure columns exist
$conn->query("ALTER TABLE telemedicine_patient_messages ADD COLUMN IF NOT EXISTS file_type VARCHAR(50) DEFAULT NULL AFTER file_path");
$conn->query("ALTER TABLE telemedicine_patient_messages ADD COLUMN IF NOT EXISTS is_voice TINYINT(1) DEFAULT 0 AFTER file_type");
$conn->query("ALTER TABLE telemedicine_patient_messages ADD COLUMN IF NOT EXISTS duration INT DEFAULT 0 AFTER is_voice");
$conn->query("ALTER TABLE telemedicine_patient_messages ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0 AFTER duration");
$conn->query("ALTER TABLE telemedicine_patient_messages ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at");

if ($action === 'delete') {
    $msg_id = intval($_POST['msg_id'] ?? 0);
    $case_id = intval($_POST['case_id'] ?? 0);
    // Security: Only allow sender to delete
    $stmt = $conn->prepare("UPDATE telemedicine_patient_messages SET is_deleted = 1 WHERE id = ? AND sender_id = ? AND sender_type = ?");
    $stmt->bind_param("iis", $msg_id, $user_id, $user_type);
    if ($stmt->execute()) {
        SyncManager::signal('telemedicine_chat', 'DELETE', $case_id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete message.']);
    }
    exit;
}

if ($action === 'replace') {
    $msg_id = intval($_POST['msg_id'] ?? 0);
    $case_id = intval($_POST['case_id'] ?? 0);
    $is_voice = isset($_POST['is_voice']) ? 1 : 0;
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = "../assets/telemedicine_uploads/";
        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        
        // Validation
        $allowed_audio = ['webm', 'mp3', 'wav', 'ogg', 'm4a'];
        if ($is_voice && !in_array($file_ext, $allowed_audio)) {
            echo json_encode(['success' => false, 'message' => 'Invalid audio format.']);
            exit;
        }

        $file_name = uniqid() . "." . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = "assets/telemedicine_uploads/" . $file_name;
            $file_type = $is_voice ? 'audio' : 'document';
            
            // Update message and set updated_at, reset is_deleted if it was deleted
            $stmt = $conn->prepare("UPDATE telemedicine_patient_messages SET file_path = ?, file_type = ?, is_deleted = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND sender_id = ? AND sender_type = ?");
            $stmt->bind_param("ssiis", $file_path, $file_type, $msg_id, $user_id, $user_type);
            
            if ($stmt->execute()) {
                SyncManager::signal('telemedicine_chat', 'UPDATE', $case_id);
                echo json_encode(['success' => true, 'file_path' => $file_path]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database update failed.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'File upload failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No file provided.']);
    }
    exit;
}

if ($action === 'send') {
    $case_id = intval($_POST['case_id']);
    $message = $_POST['message'] ?? '';
    $is_voice = isset($_POST['is_voice']) ? 1 : 0;
    $duration = intval($_POST['duration'] ?? 0);
    $file_path = null;
    $file_type = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = "../assets/telemedicine_uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . "." . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = "assets/telemedicine_uploads/" . $file_name;
            $file_type = $is_voice ? 'audio' : (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'document');
        }
    }

    if (empty($message) && empty($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Empty message.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO telemedicine_patient_messages (case_id, sender_id, sender_type, message, file_path, file_type, is_voice, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssii", $case_id, $user_id, $user_type, $message, $file_path, $file_type, $is_voice, $duration);
    
    if ($stmt->execute()) {
        SyncManager::signal('telemedicine_chat', 'INSERT', $case_id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

if ($action === 'send_signal') {
    $case_id = intval($_GET['case_id']);
    $type = $_GET['type'] ?? ''; // e.g., 'call_start', 'call_end'
    $payload = $_POST['payload'] ?? ($_GET['payload'] ?? null);
    
    // Broadcast signal via SyncEngine
    SyncManager::signal('telemedicine_chat', strtoupper($type), $case_id, $payload);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get') {
    $case_id = intval($_GET['case_id']);
    $last_id = intval($_GET['last_id'] ?? 0);

    $stmt = $conn->prepare("SELECT * FROM telemedicine_patient_messages WHERE case_id = ? AND id > ? ORDER BY created_at ASC");
    $stmt->bind_param("ii", $case_id, $last_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    echo json_encode(['success' => true, 'messages' => $messages]);
}
?>