<?php
require_once 'includes/db_connect.php';

echo "Updating status ENUM for patient_onboarding...<br>";

$sql = "ALTER TABLE patient_onboarding MODIFY COLUMN status ENUM('Not Started', 'Payment Pending', 'Awaiting Confirmation', 'Paid', 'Pending Records', 'Verified', 'Sent to Nursing', 'Completed') DEFAULT 'Not Started'";

if ($conn->query($sql)) {
    echo "Success: Status column updated.<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

echo "Done!";
?>