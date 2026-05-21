<?php
require_once 'includes/db_connect.php';

try {
    $conn->query("CREATE TABLE IF NOT EXISTS telemedicine_annotations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        case_id INT NOT NULL,
        doctor_id INT NOT NULL,
        stroke_data LONGTEXT NOT NULL,
        color VARCHAR(20) DEFAULT '#f43f5e',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created telemedicine_annotations table.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>