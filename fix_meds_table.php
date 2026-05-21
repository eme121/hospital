<?php
require_once 'includes/db_connect.php';

$cols = [
    'category_id' => "INT",
    'unit_id' => "INT",
    'supplier_id' => "INT",
    'cost_price' => "DECIMAL(10,2) DEFAULT 0.00",
    'selling_price' => "DECIMAL(10,2) DEFAULT 0.00",
    'reorder_level' => "INT DEFAULT 10",
    'packaging_type' => "ENUM('Bottle', 'Ampule', 'Card', 'Vaccine', 'Sachet', 'Vial') DEFAULT 'Bottle'",
    'batch_number' => "VARCHAR(50)",
    'expiry_date' => "DATE",
    'description' => "TEXT"
];

foreach ($cols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM medications LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE medications ADD COLUMN `$col` $def");
        echo "Added column: $col\n";
    }
}

// Also ensure pharmacy_dispensations has the right structure
$conn->query("CREATE TABLE IF NOT EXISTS `pharmacy_dispensation_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `dispensation_id` INT NOT NULL,
    `drug_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL
)");

echo "Medications schema validation complete.\n";
?>