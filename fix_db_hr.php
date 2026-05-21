<?php
require_once 'includes/db_connect.php';

// Add password column to telemedicine_doctors if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM telemedicine_doctors LIKE 'password'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE telemedicine_doctors ADD COLUMN password VARCHAR(255) AFTER email");
    echo "Added password column to telemedicine_doctors\n";
} else {
    echo "Password column already exists in telemedicine_doctors\n";
}

// Add department_id to nurses if it doesn't exist (it should based on manage_hr.php)
$result = $conn->query("SHOW COLUMNS FROM nurses LIKE 'department_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE nurses ADD COLUMN department_id INT AFTER password");
    echo "Added department_id column to nurses\n";
}

?>