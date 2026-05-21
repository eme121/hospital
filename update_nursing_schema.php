<?php
require_once 'includes/db_connect.php';

$queries = [
    // 1. Patient Visits Table
    "CREATE TABLE IF NOT EXISTS `patient_visits` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `patient_id` INT NOT NULL,
        `nurse_id` INT NOT NULL,
        `visit_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `status` ENUM('Active', 'Completed', 'Referred') DEFAULT 'Active',
        `presenting_complaints` TEXT,
        `medical_history` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 2. Vital Signs Update
    "ALTER TABLE `vital_signs` 
        ADD COLUMN `visit_id` INT DEFAULT NULL,
        ADD COLUMN `nurse_id` INT DEFAULT NULL,
        ADD COLUMN `fasting_blood_sugar` DECIMAL(5,2) DEFAULT NULL COMMENT 'mg/dL',
        ADD COLUMN `pulse` INT DEFAULT NULL,
        MODIFY COLUMN `temperature` DECIMAL(4,1) DEFAULT NULL COMMENT 'Celsius'",

    // 3. Clerking Templates Table
    "CREATE TABLE IF NOT EXISTS `clerking_templates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `fields_json` LONGTEXT NOT NULL COMMENT 'JSON structure of form fields',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 4. Clerking Records Table
    "CREATE TABLE IF NOT EXISTS `clerking_records` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `visit_id` INT NOT NULL,
        `template_id` INT NOT NULL,
        `nurse_id` INT NOT NULL,
        `data_json` LONGTEXT NOT NULL COMMENT 'Submitted form data in JSON',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 5. Nursing Referrals Table
    "CREATE TABLE IF NOT EXISTS `nursing_referrals` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `visit_id` INT NOT NULL,
        `doctor_id` INT DEFAULT NULL,
        `nurse_id` INT NOT NULL,
        `priority` ENUM('Normal', 'Urgent', 'Emergency') DEFAULT 'Normal',
        `notes` TEXT,
        `status` ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 6. Patient Table Updates
    "ALTER TABLE `patients` 
        ADD COLUMN `gender` ENUM('Male', 'Female', 'Other') DEFAULT NULL,
        ADD COLUMN `age` INT DEFAULT NULL"
];

foreach ($queries as $query) {
    try {
        if (!$conn->query($query)) {
            echo "Error executing query: " . $conn->error . "\n";
        } else {
            echo "Query executed successfully.\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

// Seed default templates
$default_templates = [
    [
        'name' => 'General Clerking',
        'fields' => [
            ['label' => 'History of Present Illness', 'name' => 'hpi', 'type' => 'textarea'],
            ['label' => 'Past Medical History', 'name' => 'pmh', 'type' => 'textarea'],
            ['label' => 'Drug History', 'name' => 'drug_history', 'type' => 'textarea'],
            ['label' => 'Family/Social History', 'name' => 'family_history', 'type' => 'textarea'],
            ['label' => 'Examination Findings', 'name' => 'examination', 'type' => 'textarea'],
            ['label' => 'Provisional Diagnosis', 'name' => 'provisional_diagnosis', 'type' => 'text'],
            ['label' => 'Plan', 'name' => 'plan', 'type' => 'textarea']
        ]
    ],
    [
        'name' => 'Diabetic Care Flow',
        'fields' => [
            ['label' => 'Duration of Diabetes', 'name' => 'duration', 'type' => 'text'],
            ['label' => 'Current Medications', 'name' => 'meds', 'type' => 'textarea'],
            ['label' => 'Last HbA1c', 'name' => 'hba1c', 'type' => 'text'],
            ['label' => 'Hypoglycemic Episodes', 'name' => 'hypo', 'type' => 'textarea'],
            ['label' => 'Foot Examination', 'name' => 'foot_exam', 'type' => 'textarea']
        ]
    ],
    [
        'name' => 'BPH Assessment (Male)',
        'fields' => [
            ['label' => 'IPSS Score', 'name' => 'ipss', 'type' => 'number'],
            ['label' => 'Nocturia Frequency', 'name' => 'nocturia', 'type' => 'text'],
            ['label' => 'Quality of Life Score', 'name' => 'qol', 'type' => 'number'],
            ['label' => 'DRE Findings', 'name' => 'dre', 'type' => 'textarea'],
            ['label' => 'PSA Level', 'name' => 'psa', 'type' => 'text']
        ]
    ],
    [
        'name' => 'Hypertension Review',
        'fields' => [
            ['label' => 'Duration of Hypertension', 'name' => 'duration', 'type' => 'text'],
            ['label' => 'Adherence to Meds', 'name' => 'adherence', 'type' => 'text'],
            ['label' => 'Target Organ Damage (Signs)', 'name' => 'target_organ', 'type' => 'textarea'],
            ['label' => 'Salt Intake/Lifestyle', 'name' => 'lifestyle', 'type' => 'textarea']
        ]
    ],
    [
        'name' => 'Metabolic Syndrome Screening',
        'fields' => [
            ['label' => 'Waist Circumference (cm)', 'name' => 'waist', 'type' => 'number'],
            ['label' => 'Triglycerides', 'name' => 'triglycerides', 'type' => 'text'],
            ['label' => 'HDL Cholesterol', 'name' => 'hdl', 'type' => 'text'],
            ['label' => 'Fasting Glucose', 'name' => 'glucose', 'type' => 'text']
        ]
    ],
    [
        'name' => 'Viral Hepatitis Panel',
        'fields' => [
            ['label' => 'HBsAg Status', 'name' => 'hbsag', 'type' => 'text'],
            ['label' => 'HBeAg Status', 'name' => 'hbeag', 'type' => 'text'],
            ['label' => 'Anti-HCV Status', 'name' => 'anti_hcv', 'type' => 'text'],
            ['label' => 'ALT/AST Levels', 'name' => 'liver_enzymes', 'type' => 'text']
        ]
    ],
    [
        'name' => 'Referral Sheet',
        'fields' => [
            ['label' => 'Reason for Referral', 'name' => 'reason', 'type' => 'textarea'],
            ['label' => 'Urgency', 'name' => 'urgency', 'type' => 'text'],
            ['label' => 'Specific Specialist Needed', 'name' => 'specialist', 'type' => 'text'],
            ['label' => 'Brief Summary', 'name' => 'summary', 'type' => 'textarea']
        ]
    ]
];

foreach ($default_templates as $tpl) {
    $fields_json = json_encode($tpl['fields']);
    $stmt = $conn->prepare("INSERT IGNORE INTO clerking_templates (name, fields_json) VALUES (?, ?)");
    $stmt->bind_param("ss", $tpl['name'], $fields_json);
    $stmt->execute();
}

echo "Nursing schema update completed.";
?>
