<?php
require_once 'includes/db_connect.php';

echo "Starting Folder Type Migration and Cleanup...<br>";

// 1. Create a mapping for current names to target types
// We want to normalize everything to:
// 1. SINGLE FOLDER
// 2. FAMILY FOLDER
// 3. AMENITY FOLDER

$target_folders = [
    'SINGLE FOLDER' => ['name' => 'SINGLE FOLDER', 'desc' => 'Standard records for individual consultations and personal medical history.', 'price' => 2500.00, 'color' => 'blue'],
    'FAMILY FOLDER' => ['name' => 'FAMILY FOLDER', 'desc' => 'Comprehensive health tracking for family units (Up to 5 members).', 'price' => 7500.00, 'color' => 'emerald'],
    'AMENITY FOLDER' => ['name' => 'AMENITY FOLDER', 'desc' => 'VIP access with luxury amenities and priority medical tracking.', 'price' => 15000.00, 'color' => 'amber']
];

// Mapping of old/other names to the target ones
$mapping = [
    'Individual Folder' => 'SINGLE FOLDER',
    'SINGLE FOLDER' => 'SINGLE FOLDER',
    'Family Folder' => 'FAMILY FOLDER',
    'FAMILY FOLDER' => 'FAMILY FOLDER',
    'Premium/Corporate' => 'AMENITY FOLDER',
    'AMENITY FOLDER' => 'AMENITY FOLDER'
];

// Disable foreign key checks temporarily to allow truncate if needed
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Get all current folders
$res = $conn->query("SELECT * FROM folder_types");
$current_folders = [];
while($f = $res->fetch_assoc()) {
    $current_folders[$f['id']] = $f['name'];
}

// 2. Clear folder_types and insert exactly 3
$conn->query("TRUNCATE TABLE folder_types");
$conn->query("INSERT INTO folder_types (id, name, description, price, theme_color) VALUES 
    (1, 'SINGLE FOLDER', 'Standard records for individual consultations and personal medical history.', 2500.00, 'blue'),
    (2, 'FAMILY FOLDER', 'Comprehensive health tracking for family units (Up to 5 members).', 7500.00, 'emerald'),
    (3, 'AMENITY FOLDER', 'VIP access with luxury amenities and priority medical tracking.', 15000.00, 'amber')");

echo "Reset folder_types table to 3 canonical types.<br>";

// 3. Update patient_onboarding records to point to new IDs
foreach ($current_folders as $old_id => $old_name) {
    $target_name = $mapping[$old_name] ?? 'SINGLE FOLDER'; // Default to Single if unknown
    
    $new_id = 1; // Default
    if (strpos(strtoupper($target_name), 'FAMILY') !== false) $new_id = 2;
    if (strpos(strtoupper($target_name), 'AMENITY') !== false || strpos(strtoupper($target_name), 'PREMIUM') !== false) $new_id = 3;

    $stmt = $conn->prepare("UPDATE patient_onboarding SET folder_type_id = ? WHERE folder_type_id = ?");
    $stmt->bind_param("ii", $new_id, $old_id);
    $stmt->execute();
    
    echo "Migrated patients from old folder ID $old_id ($old_name) to new folder ID $new_id.<br>";
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "<strong>Cleanup Complete!</strong> The popup should now show only 3 options.";
?>