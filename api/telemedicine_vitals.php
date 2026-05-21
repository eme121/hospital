<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$case_id = intval($_POST['case_id'] ?? 0);
$patient_id = intval($_POST['patient_id'] ?? 0);

if ($case_id <= 0 || $patient_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Case or Patient ID.']);
    exit;
}

$sys = $_POST['blood_pressure_sys'] ?? null;
$dia = $_POST['blood_pressure_dia'] ?? null;
$temp = $_POST['temperature'] ?? null;
$hr = $_POST['heart_rate'] ?? null;

// Insert into vital_signs (leaving visit_id as 0 or NULL for tele-cases)
$stmt = $conn->prepare("INSERT INTO vital_signs (patient_id, nurse_id, blood_pressure_sys, blood_pressure_dia, temperature, heart_rate, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
$notes = "Logged via Telemedicine Case #$case_id";
$nurse_id = 0; // Logged by doctor
$stmt->bind_param("iiiidds", $patient_id, $nurse_id, $sys, $dia, $temp, $hr, $notes);

if ($stmt->execute()) {
    // Post a system message to the chat
    $chat_msg = "📊 VITALS LOGGED: BP: $sys/$dia, Temp: $temp°C, HR: $hr BPM";
    $chat_stmt = $conn->prepare("INSERT INTO telemedicine_messages (case_id, doctor_id, message, message_type) VALUES (?, ?, ?, 'clinical_action')");
    $chat_stmt->bind_param("iis", $case_id, $doctor_id, $chat_msg);
    $chat_stmt->execute();
    
    SyncManager::signal('telemedicine_chat', 'INSERT', $case_id);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>