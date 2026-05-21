<?php
require_once 'includes/db_connect.php';

echo "Starting migration for appointments enhancements...\n";

$tables = ['appointments', 'telemedicine_appointments'];

foreach ($tables as $table) {
    // 1. Add is_archived column if not exists
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE 'is_archived'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE $table ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
        echo "Added 'is_archived' column to '$table'.\n";
    }

    // 2. Add 'Completed' and 'No-show' to status enum if possible, 
    // or just ensure we can use them if it's a VARCHAR (the original SQL said ENUM)
    // Actually, checking current status
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE 'status'");
    $row = $res->fetch_assoc();
    $type = $row['Type']; // e.g. enum('Pending','Confirmed','Cancelled')
    
    if (strpos($type, 'Completed') === false || strpos($type, 'No-show') === false) {
        // Update ENUM to include new statuses
        $new_enum = "ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed', 'No-show')";
        $conn->query("ALTER TABLE $table MODIFY COLUMN status $new_enum DEFAULT 'Pending'");
        echo "Updated 'status' enum for '$table'.\n";
    }
}

echo "Migration complete.\n";
?>