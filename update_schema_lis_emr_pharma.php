<?php
require_once 'includes/db_connect.php';

echo "<h2>Starting Schema Update for LIS, EMR, and Advanced Pharmacy...</h2>";

$queries = [
    // 1. Patient EMR Enhancements
    "ALTER TABLE patients ADD COLUMN IF NOT EXISTS allergies TEXT DEFAULT NULL",
    "ALTER TABLE patients ADD COLUMN IF NOT EXISTS risk_factors TEXT DEFAULT NULL",
    
    // 2. Lab Results Enhancements (Store values at release time)
    "ALTER TABLE lab_results ADD COLUMN IF NOT EXISTS reference_range TEXT DEFAULT NULL",
    "ALTER TABLE lab_results ADD COLUMN IF NOT EXISTS unit VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE lab_results ADD COLUMN IF NOT EXISTS lab_notes TEXT DEFAULT NULL",

    // 3. Pharmacy Suppliers
    "CREATE TABLE IF NOT EXISTS pharmacy_suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        phone VARCHAR(50),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 4. Purchase Orders (Pharmacy)
    "CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT,
        order_no VARCHAR(50) UNIQUE NOT NULL,
        total_amount DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('Pending', 'Ordered', 'Received', 'Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES pharmacy_suppliers(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 5. Purchase Order Items
    "CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        medication_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
        FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 6. Drug Interactions
    "CREATE TABLE IF NOT EXISTS drug_interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        drug_a_name VARCHAR(255) NOT NULL,
        drug_b_name VARCHAR(255) NOT NULL,
        severity ENUM('Minor', 'Moderate', 'Major') NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    // 7. Vitals History Tracking (Ensure we have enough historical data points for charts)
    "ALTER TABLE vital_signs ADD INDEX IF NOT EXISTS idx_patient_date (patient_id, recorded_at)"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>SUCCESS: " . substr($sql, 0, 100) . "...</p>";
    } else {
        echo "<p style='color:red;'>ERROR: " . $conn->error . " | SQL: " . $sql . "</p>";
    }
}

// Seed some initial drug interactions for safety
$seeds = [
    "INSERT IGNORE INTO drug_interactions (drug_a_name, drug_b_name, severity, description) VALUES 
    ('Aspirin', 'Warfarin', 'Major', 'Increased risk of severe bleeding.'),
    ('Amoxicillin', 'Methotrexate', 'Moderate', 'Amoxicillin may decrease the excretion of methotrexate, increasing toxicity.'),
    ('Ibuprofen', 'Lisinopril', 'Moderate', 'NSAIDs can reduce the antihypertensive effect of ACE inhibitors and increase kidney risk.'),
    ('Metformin', 'Contrast Dye', 'Major', 'Risk of lactic acidosis if Metformin is not stopped before iodine-based contrast scans.')"
];

foreach ($seeds as $sql) {
    $conn->query($sql);
}

echo "<h3>Schema Update Complete.</h3>";
?>