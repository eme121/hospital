<?php
require_once 'includes/db_connect.php';

echo "<h2>Telemedicine System Diagnostic & Repair</h2>";

// 1. Fix telemedicine_messages table
$cols_to_add = [
    'message_type' => "VARCHAR(20) DEFAULT 'text'",
    'file_path' => "VARCHAR(255) DEFAULT NULL",
    'file_type' => "VARCHAR(50) DEFAULT NULL",
    'is_voice' => "INT(1) DEFAULT 0",
    'duration' => "INT(11) DEFAULT 0",
    'is_deleted' => "TINYINT(1) DEFAULT 0"
];

foreach ($cols_to_add as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM telemedicine_messages LIKE '$col'");
    if ($check->num_rows == 0) {
        if ($conn->query("ALTER TABLE telemedicine_messages ADD COLUMN $col $definition")) {
            echo "✅ Added missing column: <b>$col</b><br>";
        } else {
            echo "❌ Failed to add column $col: " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ️ Column $col already exists.<br>";
    }
}

// 2. Check if telemedicine_presence has case_id
$check_p = $conn->query("SHOW COLUMNS FROM telemedicine_presence LIKE 'case_id'");
if ($check_p->num_rows == 0) {
    // Drop and recreate presence to handle primary key issues if needed, or just add column
    $conn->query("ALTER TABLE telemedicine_presence ADD COLUMN case_id INT(11) DEFAULT NULL");
    echo "✅ Added missing column case_id to presence table.<br>";
}

// 3. Ensure Case #10 exists for testing
$check_case = $conn->query("SELECT id FROM telemedicine_cases WHERE id = 10");
if ($check_case->num_rows == 0) {
    // Get a valid doctor ID to assign
    $doc = $conn->query("SELECT id FROM telemedicine_doctors LIMIT 1")->fetch_assoc();
    $doc_id = $doc ? $doc['id'] : 1;
    $conn->query("INSERT INTO telemedicine_cases (id, patient_name_or_id, symptoms, status, created_by) VALUES (10, 'Test Patient 10', 'Persistent connection issues', 'Open', $doc_id)");
    echo "✅ Created missing Case #10 for testing.<br>";
} else {
    echo "ℹ️ Case #10 already exists.<br>";
}

// 4. Clear presence table to reset any 'stuck' states
$conn->query("DELETE FROM telemedicine_presence");
echo "✅ Presence table reset.<br>";

echo "<br><b>Repair Complete!</b> Please <a href='telemedicine_dashboard.php'>return to Dashboard</a> and try again.";
?>