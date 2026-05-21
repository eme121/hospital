<?php
require_once 'includes/db_connect.php';

try {
    $conn->query("ALTER TABLE telemedicine_cases ADD COLUMN IF NOT EXISTS specialist_signature LONGTEXT DEFAULT NULL");
    echo "Ensured specialist_signature column exists in telemedicine_cases table.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>