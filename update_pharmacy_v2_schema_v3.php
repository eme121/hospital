<?php
require_once 'includes/db_connect.php';

echo "Updating Pharmacy Schema to support Break-Bulk logic...<br>";

$updates = [
    // 1. Update main_store_inventory
    "ALTER TABLE main_store_inventory ADD COLUMN IF NOT EXISTS strength VARCHAR(50) AFTER drug_name",
    "ALTER TABLE main_store_inventory ADD COLUMN IF NOT EXISTS units_per_pack INT DEFAULT 1 AFTER unit",
    "ALTER TABLE main_store_inventory ADD COLUMN IF NOT EXISTS total_base_units INT DEFAULT 0 AFTER units_per_pack",
    "ALTER TABLE main_store_inventory ADD COLUMN IF NOT EXISTS reorder_level INT DEFAULT 20 AFTER supplier_id",

    // 2. Update pharmacy_stock
    "ALTER TABLE pharmacy_stock ADD COLUMN IF NOT EXISTS strength VARCHAR(50) AFTER drug_name",
    "ALTER TABLE pharmacy_stock ADD COLUMN IF NOT EXISTS base_unit VARCHAR(50) AFTER category",
    "ALTER TABLE pharmacy_stock MODIFY COLUMN quantity INT DEFAULT 0",

    // 3. Create pharmacy_dispensations if it doesn't exist
    "CREATE TABLE IF NOT EXISTS pharmacy_dispensations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        pharmacist_id INT,
        prescription_id INT,
        total_amount DECIMAL(10,2),
        notes TEXT,
        status VARCHAR(50) DEFAULT 'Dispensed',
        dispensed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (pharmacist_id) REFERENCES pharmacists(id)
    )",

    // 4. Create pharmacy_dispensation_items
    "CREATE TABLE IF NOT EXISTS pharmacy_dispensation_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dispensation_id INT NOT NULL,
        drug_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (dispensation_id) REFERENCES pharmacy_dispensations(id) ON DELETE CASCADE,
        FOREIGN KEY (drug_id) REFERENCES pharmacy_stock(id)
    )",

    // 5. Create general dispensations (if not exists)
    "CREATE TABLE IF NOT EXISTS dispensations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        medication_id INT,
        quantity_dispensed INT,
        status VARCHAR(50),
        performed_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($updates as $sql) {
    if ($conn->query($sql)) {
        echo "Success: $sql<br>";
    } else {
        echo "Error on query ($sql): " . $conn->error . "<br>";
    }
}

// Optional: Populate total_base_units for existing records
$conn->query("UPDATE main_store_inventory SET total_base_units = quantity * units_per_pack");

echo "Schema update completed!";
?>