<?php
require_once 'includes/db_connect.php';

echo "Updating Folder Types...<br>";

// 1. Clear existing
$conn->query("TRUNCATE TABLE folder_types");

// 2. Insert Required Names and Prices
$conn->query("INSERT INTO folder_types (name, description, price, theme_color) VALUES 
    ('SINGLE FOLDER', 'Standard records for individual consultations and personal medical history.', 2500.00, 'blue'), 
    ('FAMILY FOLDER', 'Comprehensive health tracking for family units (Up to 5 members).', 7500.00, 'emerald'), 
    ('AMENITY FOLDER', 'VIP access with luxury amenities and priority medical tracking.', 15000.00, 'amber')");

echo "Folder Types Synchronized!";
?>