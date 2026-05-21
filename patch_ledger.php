<?php
require_once 'includes/db_connect.php';

try {
    $conn->query("CREATE TABLE IF NOT EXISTS telemedicine_ledger (
        id INT AUTO_INCREMENT PRIMARY KEY,
        case_id INT NOT NULL,
        doctor_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created telemedicine_ledger table.\n";
    
    // Seed initial entries from existing messages if needed, or just start fresh.
    echo "Ledger initialized.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>