<?php
require_once 'includes/db_connect.php';

$sql = "ALTER TABLE telemedicine_case_members ADD COLUMN status ENUM('pending', 'accepted', 'declined') DEFAULT 'accepted'";
if ($conn->query($sql)) {
    echo "Successfully added status column to telemedicine_case_members.\n";
    // Update existing consultants to 'accepted' if they were already there, 
    // but the default 'accepted' already covers existing rows.
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

// Also ensure the lead physician is accepted
$conn->query("UPDATE telemedicine_case_members SET status = 'accepted' WHERE role = 'Lead Physician'");
?>