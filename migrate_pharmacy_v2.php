<?php
require_once 'includes/db_connect.php';

echo "Starting Pharmacy System Migration...<br>";

$queries = [
    // 1. Suppliers Table
    "CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        contact_person VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 2. Main Store Inventory (The Source of Truth)
    "CREATE TABLE IF NOT EXISTS main_store_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        drug_name VARCHAR(150) NOT NULL,
        category VARCHAR(100),
        packaging_type VARCHAR(50), -- tablet, bottle, etc.
        unit VARCHAR(20), -- e.g., mg
        batch_number VARCHAR(50),
        expiry_date DATE,
        quantity INT DEFAULT 0,
        unit_cost_price DECIMAL(10,2),
        total_cost_price DECIMAL(10,2),
        supplier_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
    )",

    // 3. Pharmacy Stock (Dispensing Unit)
    "CREATE TABLE IF NOT EXISTS pharmacy_stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        drug_name VARCHAR(150) UNIQUE NOT NULL,
        category VARCHAR(100),
        unit VARCHAR(20),
        quantity INT DEFAULT 0,
        selling_price DECIMAL(10,2) DEFAULT 0.00,
        expiry_date DATE,
        reorder_level INT DEFAULT 10,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // 4. Stock Movements (Transfer Log)
    "CREATE TABLE IF NOT EXISTS stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        main_store_item_id INT,
        drug_name VARCHAR(150),
        quantity INT NOT NULL,
        from_location VARCHAR(50) DEFAULT 'MAIN STORE',
        to_location VARCHAR(50) DEFAULT 'PHARMACY',
        performed_by INT, -- user_id
        movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (main_store_item_id) REFERENCES main_store_inventory(id) ON DELETE SET NULL
    )",

    // 5. Purchases (Record of all incoming stock to Main Store)
    "CREATE TABLE IF NOT EXISTS drug_purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        main_store_item_id INT,
        supplier_id INT,
        quantity_received INT,
        total_cost DECIMAL(10,2),
        purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        batch_number VARCHAR(50),
        FOREIGN KEY (main_store_item_id) REFERENCES main_store_inventory(id) ON DELETE SET NULL,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
    )"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "Success: Table/Column updated.<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

// Seed some initial suppliers if none exist
$check = $conn->query("SELECT COUNT(*) FROM suppliers");
if ($check && $check->fetch_row()[0] == 0) {
    $conn->query("INSERT INTO suppliers (name, contact_person, phone) VALUES 
        ('Emzor Pharmaceuticals', 'Mr. Emeka', '08012345678'),
        ('GlaxoSmithKline', 'Sarah James', '07098765432'),
        ('Fidson Healthcare', 'John Doe', '09011223344')");
    echo "Seeded default suppliers.<br>";
}

echo "Pharmacy Migration Complete!";
?>