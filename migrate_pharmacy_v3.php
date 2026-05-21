<?php
require_once 'includes/db_connect.php';

$queries = [
    "ALTER TABLE pharmacy_stock ADD COLUMN admin_unit VARCHAR(50) DEFAULT 'tab' AFTER base_unit",
    "ALTER TABLE pharmacy_stock ADD COLUMN dispense_unit VARCHAR(50) DEFAULT 'pack' AFTER admin_unit",
    "ALTER TABLE pharmacy_stock ADD COLUMN pack_size DECIMAL(10,2) DEFAULT 1.00 AFTER dispense_unit",
    // Update existing records based on form_type
    "UPDATE pharmacy_stock SET admin_unit = 'tab', dispense_unit = 'pack', pack_size = 10 WHERE form_type = 'Tablet'",
    "UPDATE pharmacy_stock SET admin_unit = 'ml', dispense_unit = 'bottle', pack_size = 100 WHERE form_type = 'Syrup'",
    "UPDATE pharmacy_stock SET admin_unit = 'ml', dispense_unit = 'vial', pack_size = 1 WHERE form_type = 'Injection'",
    "UPDATE pharmacy_stock SET admin_unit = 'dose', dispense_unit = 'vial', pack_size = 1 WHERE form_type = 'Vaccine'",
    "UPDATE pharmacy_stock SET admin_unit = 'ml', dispense_unit = 'bottle', pack_size = 500 WHERE form_type = 'IV Fluid'",
    "UPDATE pharmacy_stock SET admin_unit = 'gram', dispense_unit = 'tube', pack_size = 30 WHERE form_type = 'Cream'"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "Successfully executed: " . substr($sql, 0, 50) . "...\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
?>