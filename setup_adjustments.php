<?php
require_once 'includes/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS stock_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    item_type ENUM('main', 'pharmacy') NOT NULL,
    drug_name VARCHAR(150),
    old_quantity INT,
    new_quantity INT,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2),
    reason TEXT NOT NULL,
    performed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "<h1>Success!</h1><p>Adjustment Audit Table Created Successfully.</p><a href='pharmacy/inventory.php'>Return to Inventory</a>";
} else {
    echo "<h1>Error!</h1><p>" . $conn->error . "</p>";
}
?>