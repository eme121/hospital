<?php
require_once 'includes/db_connect.php';

echo "Updating database for Patient Onboarding System...<br>";

$queries = [
    // 1. Folder Types & Pricing
    "CREATE TABLE IF NOT EXISTS folder_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        theme_color VARCHAR(20) DEFAULT 'blue',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 2. Patient Onboarding Status
    "CREATE TABLE IF NOT EXISTS patient_onboarding (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        folder_type_id INT,
        status ENUM('Not Started', 'Payment Pending', 'Awaiting Confirmation', 'Paid', 'Pending Records', 'Verified', 'Sent to Nursing', 'In Intake', 'In Progress', 'Completed') DEFAULT 'Not Started',
        current_step INT DEFAULT 1,
        payment_status ENUM('Pending', 'Confirmed', 'Failed') DEFAULT 'Pending',
        is_locked TINYINT(1) DEFAULT 0,
        form_progress INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (folder_type_id) REFERENCES folder_types(id)
    )",

    // 3. Onboarding Payments
    "CREATE TABLE IF NOT EXISTS onboarding_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        onboarding_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        reference VARCHAR(100),
        method VARCHAR(50),
        proof_file VARCHAR(255),
        status ENUM('Pending', 'Confirmed', 'Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (onboarding_id) REFERENCES patient_onboarding(id)
    )",

    // 4. Patient Form Data (Flexible structure for "new patient form.docx")
    "CREATE TABLE IF NOT EXISTS patient_form_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        section_name VARCHAR(100),
        field_name VARCHAR(100),
        field_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY patient_field (patient_id, section_name, field_name),
        FOREIGN KEY (patient_id) REFERENCES patients(id)
    )"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "Success: Table/Schema updated.<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

// Seed default folder types if empty
$check = $conn->query("SELECT id FROM folder_types LIMIT 1");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO folder_types (name, description, price, theme_color) VALUES 
        ('SINGLE FOLDER', 'Standard records for individual consultations and personal medical history.', 2500.00, 'blue'),
        ('FAMILY FOLDER', 'Comprehensive health tracking for family units (Up to 5 members).', 7500.00, 'emerald'),
        ('AMENITY FOLDER', 'VIP access with luxury amenities and priority medical tracking.', 15000.00, 'amber')");
    echo "Seeded default folder types.<br>";
}

echo "Onboarding System Schema Update Complete!";
?>