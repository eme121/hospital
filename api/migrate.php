<?php
require_once '../includes/db_connect.php';

echo "Starting Migration...<br>";

// 1. Update Patients Table
$conn->query("ALTER TABLE patients ADD COLUMN IF NOT EXISTS owed_amount DECIMAL(10,2) DEFAULT 0.00");
$conn->query("ALTER TABLE patients ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) DEFAULT 0.00");
echo "Patients table updated.<br>";

// 2. Update Doctors Table
$conn->query("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS email VARCHAR(100) UNIQUE");
$conn->query("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS password VARCHAR(255)");
$conn->query("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS allow_physical TINYINT(1) DEFAULT 1");
$conn->query("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS allow_virtual TINYINT(1) DEFAULT 0");
echo "Doctors table updated.<br>";

// 3. Create Events Table
$conn->query("CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "Events table checked/created.<br>";

echo "Migration Complete!";
?>
