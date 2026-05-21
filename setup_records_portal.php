<?php
require_once 'includes/db_connect.php';

echo "Setting up Records Portal...<br>";

// 1. Create records_staff table
$conn->query("CREATE TABLE IF NOT EXISTS records_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Seed default records staff
$pass = password_hash('records123', PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO records_staff (name, email, password) VALUES ('Records Officer', 'records@hospital.com', '$pass')");

// 3. Update patient_onboarding ENUM to include all required states
$conn->query("ALTER TABLE patient_onboarding MODIFY COLUMN status ENUM('Not Started', 'Payment Pending', 'Paid', 'Pending Records', 'Verified', 'Sent to Nursing', 'Completed') DEFAULT 'Not Started'");

echo "Records Portal Setup Complete! Use records@hospital.com / records123";
?>