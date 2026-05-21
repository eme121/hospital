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
$doctor_name = $_SESSION['doctor_name'] ?? 'Doctor';

// Fix table name mismatch: it should be telemedicine_messages based on DB schema
$table = "telemedicine_messages";

if ($action === 'send') {
    $case_id = intval($_POST['case_id']);
    $message = $conn->real_escape_string($_POST['message'] ?? '');
    
    $file_path = null;
    $file_type = null;
    $message_type = 'text';
    $is_voice = 0;
    $duration = 0;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = "../assets/telemedicine_uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . "." . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = "assets/telemedicine_uploads/" . $file_name;
            
            // Detect file type
            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $file_type = 'image';
            } elseif (in_array($file_ext, ['webm', 'mp3', 'wav', 'ogg', 'm4a'])) {
                $file_type = 'audio';
                $is_voice = ($message === '[Voice Message]') ? 1 : 0;
            } else {
                $file_type = 'document';
            }
            $message_type = 'file';
        }
    }

    if (empty($message) && empty($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Empty message']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO $table (case_id, doctor_id, message, message_type, file_path, file_type, is_voice, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssii", $case_id, $doctor_id, $message, $message_type, $file_path, $file_type, $is_voice, $duration);
    
    if ($stmt->execute()) {
        SyncManager::signal('telemedicine_chat', 'NEW_MESSAGE', $case_id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} elseif ($action === 'get') {
    $case_id = intval($_GET['case_id']);
    $last_id = intval($_GET['last_id'] ?? 0);
    
    // Join with doctors table to get name
    $stmt = $conn->prepare("SELECT m.*, d.name FROM $table m JOIN telemedicine_doctors d ON m.doctor_id = d.id WHERE m.case_id = ? AND m.id > ? AND m.is_deleted = 0 ORDER BY m.created_at ASC");
    $stmt->bind_param("ii", $case_id, $last_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    echo json_encode(['success' => true, 'messages' => $messages]);
} elseif ($action === 'send_signal') {
    $case_id = intval($_GET['case_id']);
    $type = $_GET['type'] ?? '';
    $payload = $_POST['payload'] ?? null;
    
    try {
        SyncManager::signal('telemedicine_chat', $type, $case_id, $payload);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        error_log("Signaling failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'presence') {
    $case_id = intval($_GET['case_id']);
    $conn->query("INSERT INTO telemedicine_presence (doctor_id, current_case_id, last_seen) VALUES ($doctor_id, $case_id, NOW()) ON DUPLICATE KEY UPDATE current_case_id = $case_id, last_seen = NOW()");
    
    $stmt = $conn->prepare("SELECT p.*, d.name FROM telemedicine_presence p JOIN telemedicine_doctors d ON p.doctor_id = d.id WHERE p.current_case_id = ? AND p.last_seen > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $presence = [];
    while ($row = $result->fetch_assoc()) {
        $presence[] = $row;
    }
    echo json_encode(['success' => true, 'presence' => $presence]);
}
?>