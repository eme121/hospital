<?php
require_once 'includes/db_connect.php';

echo "Patching Lab Workflow...<br>";

// Add status column to lab_results
$res = $conn->query("SHOW COLUMNS FROM lab_results LIKE 'status'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE lab_results ADD COLUMN status ENUM('Pending Review', 'Released') DEFAULT 'Pending Review' AFTER result_file");
    echo "Added status column to lab_results.<br>";
}

// Add comments column for doctor's remarks during authorization
$res = $conn->query("SHOW COLUMNS FROM lab_results LIKE 'doctor_comments'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE lab_results ADD COLUMN doctor_comments TEXT DEFAULT NULL AFTER status");
    echo "Added doctor_comments column to lab_results.<br>";
}

echo "Lab Patch complete!";
?>