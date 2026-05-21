<?php
require_once 'includes/db_connect.php';

echo "<pre>";

// Add is_paid to appointments
$check = $conn->query("SHOW COLUMNS FROM appointments LIKE 'is_paid'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE appointments ADD COLUMN is_paid TINYINT(1) DEFAULT 0")) {
        echo "Successfully added 'is_paid' to 'appointments' table.\n";
    } else {
        echo "Error adding 'is_paid' to 'appointments': " . $conn->error . "\n";
    }
} else {
    echo "Column 'is_paid' already exists in 'appointments'.\n";
}

// Add is_paid to telemedicine_appointments
$check = $conn->query("SHOW COLUMNS FROM telemedicine_appointments LIKE 'is_paid'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE telemedicine_appointments ADD COLUMN is_paid TINYINT(1) DEFAULT 0")) {
        echo "Successfully added 'is_paid' to 'telemedicine_appointments' table.\n";
    } else {
        echo "Error adding 'is_paid' to 'telemedicine_appointments': " . $conn->error . "\n";
    }
} else {
    echo "Column 'is_paid' already exists in 'telemedicine_appointments'.\n";
}

// Add appointment linking to invoices
$check = $conn->query("SHOW COLUMNS FROM invoices LIKE 'appointment_id'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE invoices ADD COLUMN appointment_id INT DEFAULT NULL, ADD COLUMN appointment_type ENUM('Physical', 'Virtual') DEFAULT NULL")) {
        echo "Successfully added appointment linking columns to 'invoices' table.\n";
    } else {
        echo "Error adding columns to 'invoices': " . $conn->error . "\n";
    }
} else {
    echo "Appointment linking columns already exist in 'invoices'.\n";
}

// Add doctor_id to patient_queue_status
$check = $conn->query("SHOW COLUMNS FROM patient_queue_status LIKE 'doctor_id'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE patient_queue_status ADD COLUMN doctor_id INT DEFAULT NULL AFTER patient_id")) {
        echo "Successfully added 'doctor_id' to 'patient_queue_status' table.\n";
    } else {
        echo "Error adding 'doctor_id' to 'patient_queue_status': " . $conn->error . "\n";
    }
} else {
    echo "Column 'doctor_id' already exists in 'patient_queue_status'.\n";
}

echo "</pre>";
?>