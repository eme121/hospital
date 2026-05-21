<?php
require_once 'includes/db_connect.php';

echo "<h2>Starting Advanced Billing & Insurance Schema Update...</h2>";

$queries = [
    // 1. Insurance Providers
    "CREATE TABLE IF NOT EXISTS insurance_providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        phone VARCHAR(50),
        email VARCHAR(100),
        address TEXT,
        discount_rate DECIMAL(5,2) DEFAULT 0.00, -- Percentage they cover or pre-negotiated discount
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 2. Patient Insurance Link
    "CREATE TABLE IF NOT EXISTS patient_insurance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        provider_id INT NOT NULL,
        policy_number VARCHAR(100) NOT NULL,
        plan_type VARCHAR(100),
        expiry_date DATE,
        status ENUM('Active', 'Expired', 'Suspended') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (provider_id) REFERENCES insurance_providers(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 3. Update Invoices to support Insurance
    "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS insurance_id INT DEFAULT NULL",
    "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS insurance_covered_amount DECIMAL(10,2) DEFAULT 0.00",
    "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS superbill_status ENUM('None', 'Generated', 'Submitted', 'Paid', 'Rejected') DEFAULT 'None'",
    
    // 4. Insurance Claims Tracking
    "CREATE TABLE IF NOT EXISTS insurance_claims (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        insurance_provider_id INT NOT NULL,
        claim_number VARCHAR(100) UNIQUE,
        amount_claimed DECIMAL(10,2) NOT NULL,
        amount_approved DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('Pending', 'Submitted', 'Approved', 'Rejected') DEFAULT 'Pending',
        notes TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id),
        FOREIGN KEY (insurance_provider_id) REFERENCES insurance_providers(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>SUCCESS: " . substr($sql, 0, 100) . "...</p>";
    } else {
        echo "<p style='color:red;'>ERROR: " . $conn->error . " | SQL: " . $sql . "</p>";
    }
}

// Seed some sample HMOs
$seeds = [
    "INSERT IGNORE INTO insurance_providers (name, contact_person, discount_rate) VALUES 
    ('Reliance HMO', 'John Doe', 10.00),
    ('AXA Mansard', 'Jane Smith', 15.00),
    ('Hygeia HMO', 'Admin', 5.00),
    ('NHIS (National)', 'Government', 50.00)"
];

foreach ($seeds as $sql) {
    $conn->query($sql);
}

echo "<h3>Advanced Billing Schema Update Complete.</h3>";
?>