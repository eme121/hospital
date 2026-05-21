<?php
require_once 'includes/db_connect.php';

echo "Checking doctors table for availability columns...\n";

// Check for allow_physical
$check = $conn->query("SHOW COLUMNS FROM doctors LIKE 'allow_physical'");
if ($check && $check->num_rows == 0) {
    echo "Adding allow_physical column...\n";
    if ($conn->query("ALTER TABLE doctors ADD COLUMN allow_physical TINYINT(1) DEFAULT 1")) {
        echo "Successfully added allow_physical.\n";
    } else {
        echo "Error adding allow_physical: " . $conn->error . "\n";
    }
} else {
    echo "allow_physical already exists.\n";
}

// Check for allow_virtual
$check = $conn->query("SHOW COLUMNS FROM doctors LIKE 'allow_virtual'");
if ($check && $check->num_rows == 0) {
    echo "Adding allow_virtual column...\n";
    if ($conn->query("ALTER TABLE doctors ADD COLUMN allow_virtual TINYINT(1) DEFAULT 0")) {
        echo "Successfully added allow_virtual.\n";
    } else {
        echo "Error adding allow_virtual: " . $conn->error . "\n";
    }
} else {
    echo "allow_virtual already exists.\n";
}

echo "Database migration complete.";
?>