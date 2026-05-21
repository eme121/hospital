<?php
require_once '../includes/db_connect.php';

echo "Upgrading Prescriptions Schema for Premium Reminders...<br>";

// Add schedule columns
$queries = [
    "ALTER TABLE telemedicine_prescriptions ADD COLUMN IF NOT EXISTS dosage_times VARCHAR(255) DEFAULT '08:00, 14:00, 20:00' COMMENT 'Comma separated 24h times'",
    "ALTER TABLE telemedicine_prescriptions ADD COLUMN IF NOT EXISTS start_date DATE DEFAULT NULL",
    "ALTER TABLE telemedicine_prescriptions ADD COLUMN IF NOT EXISTS end_date DATE DEFAULT NULL",
    "ALTER TABLE telemedicine_prescriptions ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1",
    "ALTER TABLE telemedicine_prescriptions ADD COLUMN IF NOT EXISTS last_reminded_at TIMESTAMP NULL DEFAULT NULL"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "Executed: " . substr($sql, 0, 50) . "... SUCCESS<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

// Set default start/end dates for existing prescriptions to keep them active
$conn->query("UPDATE telemedicine_prescriptions SET start_date = DATE(created_at), end_date = DATE_ADD(created_at, INTERVAL 7 DAY) WHERE start_date IS NULL");

echo "Schema Upgrade Complete!";
?>