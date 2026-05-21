<?php
require_once 'includes/db_connect.php';

$sql = "ALTER TABLE pharmacy_dispensations MODIFY COLUMN status ENUM('Dispensed','Retrieved','Cancelled','Awaiting Payment') DEFAULT 'Awaiting Payment'";

if ($conn->query($sql)) {
    echo "Pharmacy dispensations table updated with 'Awaiting Payment' status.\n";
} else {
    echo "Error updating pharmacy_dispensations table: " . $conn->error . "\n";
}
?>