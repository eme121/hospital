<?php
require_once 'includes/db_connect.php';

echo "Initializing Sync Hub Architecture...<br>";

// 1. Create Sync Registry Table
$sql = "CREATE TABLE IF NOT EXISTS sync_registry (
    module_name VARCHAR(50) PRIMARY KEY,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    sync_token VARCHAR(100)
)";

if ($conn->query($sql)) {
    echo "✓ Sync Registry Table Created.<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// 2. Seed Module Tokens
$modules = ['lab_requests', 'patient_queue', 'notifications', 'billing', 'onboarding', 'prescriptions', 'clinical_visits', 'telemedicine_chat', 'telemedicine_cases'];

foreach($modules as $m) {
    $token = md5(uniqid(rand(), true));
    $conn->query("INSERT IGNORE INTO sync_registry (module_name, sync_token) VALUES ('$m', '$token')");
}

echo "✓ System Modules Registered.<br>";

// 3. Create/Update Sync Signal Dispatcher
$sql_trigger = "CREATE TABLE IF NOT EXISTS sync_signals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(50),
    signal_type VARCHAR(20),
    data_id INT,
    sender_id INT DEFAULT NULL,
    sender_name VARCHAR(100) DEFAULT NULL,
    payload LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql_trigger);

// Check if columns exist for existing tables
$res = $conn->query("SHOW COLUMNS FROM sync_signals LIKE 'sender_id'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE sync_signals ADD COLUMN sender_id INT DEFAULT NULL AFTER data_id");
    $conn->query("ALTER TABLE sync_signals ADD COLUMN sender_name VARCHAR(100) DEFAULT NULL AFTER sender_id");
    $conn->query("ALTER TABLE sync_signals ADD COLUMN payload LONGTEXT DEFAULT NULL AFTER sender_name");
}

// 4. Update Presence Table
$conn->query("CREATE TABLE IF NOT EXISTS telemedicine_presence (
    doctor_id INT PRIMARY KEY,
    current_case_id INT DEFAULT NULL,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$res2 = $conn->query("SHOW COLUMNS FROM telemedicine_presence LIKE 'current_case_id'");
if ($res2->num_rows == 0) {
    $conn->query("ALTER TABLE telemedicine_presence ADD COLUMN current_case_id INT DEFAULT NULL AFTER doctor_id");
}

echo "✓ Sync Signal Dispatcher Ready.<br>";
echo "<b>Architecture Ready.</b>";
?>