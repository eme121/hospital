<?php
require_once 'includes/db_connect.php';

echo "Running Migration to fix Doctor Finalization / Pharmacy Queue...<br>";

// 1. Add 'diagnosis' column to patient_visits if it doesn't exist
$check_diag = $conn->query("SHOW COLUMNS FROM patient_visits LIKE 'diagnosis'");
if ($check_diag->num_rows == 0) {
    echo "Adding 'diagnosis' column to patient_visits...<br>";
    if ($conn->query("ALTER TABLE patient_visits ADD COLUMN diagnosis TEXT AFTER medical_history")) {
        echo "Diagnosis column added successfully.<br>";
    } else {
        echo "Error adding diagnosis column: " . $conn->error . "<br>";
    }
} else {
    echo "Diagnosis column already exists.<br>";
}

// 2. Add 'Finalized' to status enum in patient_visits
echo "Updating patient_visits status enum...<br>";
if ($conn->query("ALTER TABLE patient_visits MODIFY COLUMN status ENUM('Active', 'Completed', 'Referred', 'Finalized') DEFAULT 'Active'")) {
    echo "Status enum updated successfully.<br>";
} else {
    echo "Error updating status enum: " . $conn->error . "<br>";
}

// 3. Ensure telemedicine_prescriptions exists (Just in case)
$check_table = $conn->query("SHOW TABLES LIKE 'telemedicine_prescriptions'");
if ($check_table->num_rows == 0) {
    echo "Creating telemedicine_prescriptions table...<br>";
    $sql = "CREATE TABLE `telemedicine_prescriptions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `patient_id` int(11) NOT NULL,
      `doctor_id` int(11) NOT NULL,
      `visit_id` int(11) NOT NULL,
      `medications_json` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    if ($conn->query($sql)) {
        echo "Table created successfully.<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

echo "Migration Complete! The Discharge button should now work correctly.";
?>