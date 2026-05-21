<?php
require_once 'includes/db_connect.php';

echo "Patching lab_samples table...<br>";
$res = $conn->query("SHOW COLUMNS FROM lab_samples LIKE 'sample_volume'");
if ($res->num_rows == 0) {
    if ($conn->query("ALTER TABLE lab_samples ADD COLUMN sample_volume VARCHAR(50) DEFAULT NULL AFTER sample_type")) {
        echo "Added sample_volume column to lab_samples.<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
} else {
    echo "sample_volume column already exists.<br>";
}

echo "Lab Patch complete!";
?>