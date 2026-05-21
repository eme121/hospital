<?php
require_once 'includes/db_connect.php';

try {
    $conn->query("CREATE TABLE IF NOT EXISTS telemedicine_case_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        case_id INT NOT NULL,
        doctor_id INT NOT NULL,
        role VARCHAR(50) DEFAULT 'Consultant',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (case_id, doctor_id)
    )");
    echo "Created telemedicine_case_members table.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>