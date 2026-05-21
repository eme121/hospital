<?php
require_once 'includes/db_connect.php';

try {
    // 1. Add voice message support
    $conn->query("ALTER TABLE telemedicine_messages ADD COLUMN is_voice BOOLEAN DEFAULT 0 AFTER file_type");
    $conn->query("ALTER TABLE telemedicine_messages ADD COLUMN duration INT DEFAULT 0 AFTER is_voice");
    echo "Updated telemedicine_messages table.\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    // 2. Presence and typing indicators
    $conn->query("CREATE TABLE IF NOT EXISTS telemedicine_presence (
        doctor_id INT PRIMARY KEY,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_typing_in_case INT DEFAULT 0,
        FOREIGN KEY (doctor_id) REFERENCES telemedicine_doctors(id)
    )");
    echo "Created telemedicine_presence table.\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    // 3. Prescriptions
    $conn->query("CREATE TABLE IF NOT EXISTS telemedicine_prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        case_id INT,
        doctor_id INT,
        patient_id INT,
        medications TEXT,
        dosage TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (case_id) REFERENCES telemedicine_cases(id),
        FOREIGN KEY (doctor_id) REFERENCES telemedicine_doctors(id),
        FOREIGN KEY (patient_id) REFERENCES patients(id)
    )");
    echo "Created telemedicine_prescriptions table.\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    // 4. Patient-Doctor Private Chat
    $conn->query("CREATE TABLE IF NOT EXISTS telemedicine_patient_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        case_id INT,
        sender_id INT,
        sender_type ENUM('doctor', 'patient'),
        message TEXT,
        file_path VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (case_id) REFERENCES telemedicine_cases(id)
    )");
    echo "Created telemedicine_patient_messages table.\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

echo "Migration Complete.";
?>