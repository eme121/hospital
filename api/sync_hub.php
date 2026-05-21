<?php
/**
 * Real-Time Sync Hub (Senior Architect Implementation)
 * Provides current sync tokens for intelligent delta-polling.
 */
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

// Get current state of all modules
$res = $conn->query("SELECT module_name, sync_token FROM sync_registry");
$registry = [];
while($row = $res->fetch_assoc()) {
    $registry[$row['module_name']] = $row['sync_token'];
}

// Maintenance: Cleanup signals older than 24 hours (Run occasionally to save resources)
if (mt_rand(1, 100) === 1) {
    $conn->query("DELETE FROM sync_signals WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
}

// Presence Reporting
$presence_case_id = isset($_GET['presence_case_id']) ? (int)$_GET['presence_case_id'] : null;
$doctor_id = $_SESSION['doctor_id'] ?? null;
if ($doctor_id) {
    // Upsert presence
    $stmt = $conn->prepare("INSERT INTO telemedicine_presence (doctor_id, current_case_id, last_seen) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE current_case_id = VALUES(current_case_id), last_seen = NOW()");
    $stmt->bind_param("ii", $doctor_id, $presence_case_id);
    $stmt->execute();
}

// Fetch active doctors for the current case
$presence_data = [];
if ($presence_case_id) {
    $res = $conn->query("SELECT p.doctor_id, d.name as doctor_name FROM telemedicine_presence p JOIN telemedicine_doctors d ON p.doctor_id = d.id WHERE p.current_case_id = $presence_case_id AND p.last_seen > DATE_SUB(NOW(), INTERVAL 15 SECOND)");
    while($row = $res->fetch_assoc()) {
        $presence_data[] = $row;
    }
}

// Optional: Get specific signals since last check
$is_baseline = !isset($_GET['last_signal_id']);
$last_signal_id = (int)($_GET['last_signal_id'] ?? 0);
$new_signals = [];
$newest_id = $last_signal_id;

if ($is_baseline) {
    // Baseline request: Just get the current newest ID to start polling from here
    $res_max = $conn->query("SELECT MAX(id) as max_id FROM sync_signals");
    if ($row_max = $res_max->fetch_assoc()) {
        $newest_id = (int)($row_max['max_id'] ?? 0);
    }
} else {
    // Polling request: Get new signals since last check
    $signals = $conn->query("SELECT id, module_name, signal_type, data_id, sender_id, sender_name, payload, created_at FROM sync_signals WHERE id > $last_signal_id ORDER BY id ASC LIMIT 20");
    while($s = $signals->fetch_assoc()) {
        $new_signals[] = $s;
        $newest_id = max($newest_id, $s['id']);
    }
}

echo json_encode([
    'success' => true,
    'registry' => $registry,
    'signals' => $new_signals,
    'newest_signal_id' => $newest_id,
    'presence' => $presence_data,
    'server_time' => time()
]);
?>