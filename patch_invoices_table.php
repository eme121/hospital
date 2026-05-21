<?php
require_once 'includes/db_connect.php';

$sql = "ALTER TABLE invoices ADD COLUMN type VARCHAR(50) DEFAULT 'General' AFTER appointment_type";

if ($conn->query($sql)) {
    echo "Invoices table patched successfully with 'type' column.\n";
} else {
    echo "Error patching invoices table: " . $conn->error . "\n";
    // Check if it already exists
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "The 'type' column already exists.\n";
    }
}
?>