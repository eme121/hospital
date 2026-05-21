<?php
require_once 'includes/db_connect.php';

echo "Patching telemedicine_prescriptions table...<br>";

// 1. Add visit_id if it doesn't exist
$check_visit = $conn->query("SHOW COLUMNS FROM telemedicine_prescriptions LIKE 'visit_id'");
if ($check_visit->num_rows == 0) {
    echo "Adding 'visit_id' column...<br>";
    if ($conn->query("ALTER TABLE telemedicine_prescriptions ADD COLUMN visit_id INT(11) AFTER doctor_id")) {
        echo "visit_id added.<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

// 2. Add medications_json if it doesn't exist
$check_json = $conn->query("SHOW COLUMNS FROM telemedicine_prescriptions LIKE 'medications_json'");
if ($check_json->num_rows == 0) {
    echo "Adding 'medications_json' column...<br>";
    if ($conn->query("ALTER TABLE telemedicine_prescriptions ADD COLUMN medications_json TEXT AFTER visit_id")) {
        echo "medications_json added.<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

echo "Patch complete.";
?>
