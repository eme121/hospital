<?php
require_once 'includes/db_connect.php';

echo "Updating tables for Payment-First workflow...<br>";

// 1. Add 'is_paid' column to appointments
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS is_paid TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE telemedicine_appointments ADD COLUMN IF NOT EXISTS is_paid TINYINT(1) DEFAULT 0");
echo "Added is_paid columns.<br>";

// 2. Add 'payment_ref' column to link with Paystack/etc
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS payment_ref VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE telemedicine_appointments ADD COLUMN IF NOT EXISTS payment_ref VARCHAR(100) DEFAULT NULL");
echo "Added payment_ref columns.<br>";

echo "Database updated for payments!";
?>