<?php
require_once 'includes/db_connect.php';

echo "Updating Pharmacy Schema to support Liquid Formulations and Vaccines...<br>";

$updates = [
    // Add columns for concentration and container volume to main_store_inventory
    "ALTER TABLE main_store_inventory ADD COLUMN IF NOT EXISTS concentration_mass DECIMAL(10,2) DEFAULT NULL AFTER strength",
    "ALTER TABLE main_store_inventory ADD COLUMN IF NOT EXISTS concentration_volume DECIMAL(10,2) DEFAULT NULL AFTER concentration_mass",
    "ALTER TABLE main_store_inventory ADD COLUMN IF NOT EXISTS container_volume DECIMAL(10,2) DEFAULT NULL AFTER concentration_volume",
    "ALTER TABLE main_store_inventory ADD COLUMN IF NOT EXISTS mass_unit VARCHAR(20) DEFAULT 'mg' AFTER container_volume",
    "ALTER TABLE main_store_inventory ADD COLUMN IF NOT EXISTS volume_unit VARCHAR(20) DEFAULT 'ml' AFTER mass_unit",

    // Add columns for concentration and container volume to pharmacy_stock
    "ALTER TABLE pharmacy_stock ADD COLUMN IF NOT EXISTS concentration_mass DECIMAL(10,2) DEFAULT NULL AFTER strength",
    "ALTER TABLE pharmacy_stock ADD COLUMN IF NOT EXISTS concentration_volume DECIMAL(10,2) DEFAULT NULL AFTER concentration_mass",
    "ALTER TABLE pharmacy_stock ADD COLUMN IF NOT EXISTS container_volume DECIMAL(10,2) DEFAULT NULL AFTER concentration_volume",
    "ALTER TABLE pharmacy_stock ADD COLUMN IF NOT EXISTS mass_unit VARCHAR(20) DEFAULT 'mg' AFTER container_volume",
    "ALTER TABLE pharmacy_stock ADD COLUMN IF NOT EXISTS volume_unit VARCHAR(20) DEFAULT 'ml' AFTER mass_unit",
    
    // Ensure quantity is DECIMAL for liquids (ml)
    "ALTER TABLE pharmacy_stock MODIFY COLUMN quantity DECIMAL(10,2) DEFAULT 0.00",
    "ALTER TABLE pharmacy_dispensation_items MODIFY COLUMN quantity DECIMAL(10,2) NOT NULL"
];

foreach ($updates as $sql) {
    if ($conn->query($sql)) {
        echo "Success: $sql<br>";
    } else {
        echo "Error on query ($sql): " . $conn->error . "<br>";
    }
}

echo "Schema update completed!";
?>
