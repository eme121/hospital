<?php
require_once 'includes/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT,
    title VARCHAR(255) NOT NULL,
    record_type ENUM('Lab Result', 'Diagnostic', 'Prescription', 'Other') DEFAULT 'Other',
    file_path VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Table medical_records created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Also adding rating column to appointments for Feature 5 later
$check_rating = $conn->query("SHOW COLUMNS FROM appointments LIKE 'rating'");
if ($check_rating->num_rows == 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN rating INT DEFAULT 0, ADD COLUMN feedback TEXT");
    echo "Added rating and feedback columns to appointments.<br>";
}

$check_rating_tele = $conn->query("SHOW COLUMNS FROM telemedicine_appointments LIKE 'rating'");
if ($check_rating_tele->num_rows == 0) {
    $conn->query("ALTER TABLE telemedicine_appointments ADD COLUMN rating INT DEFAULT 0, ADD COLUMN feedback TEXT");
    echo "Added rating and feedback columns to telemedicine_appointments.<br>";
}

// Adding is_paid to appointments
$check_paid = $conn->query("SHOW COLUMNS FROM appointments LIKE 'is_paid'");
if ($check_paid->num_rows == 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN is_paid TINYINT(1) DEFAULT 0");
    echo "Added is_paid column to appointments.<br>";
}

// Adding is_paid to telemedicine_appointments
$check_paid_tele = $conn->query("SHOW COLUMNS FROM telemedicine_appointments LIKE 'is_paid'");
if ($check_paid_tele->num_rows == 0) {
    $conn->query("ALTER TABLE telemedicine_appointments ADD COLUMN is_paid TINYINT(1) DEFAULT 0");
    echo "Added is_paid column to telemedicine_appointments.<br>";
}

// Adding appointment linking to invoices
$check_inv_link = $conn->query("SHOW COLUMNS FROM invoices LIKE 'appointment_id'");
if ($check_inv_link->num_rows == 0) {
    $conn->query("ALTER TABLE invoices ADD COLUMN appointment_id INT DEFAULT NULL, ADD COLUMN appointment_type ENUM('Physical', 'Virtual') DEFAULT NULL");
    echo "Added appointment linking columns to invoices.<br>";
}
?>