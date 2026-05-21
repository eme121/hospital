<?php
require_once 'includes/db_connect.php';

echo "Starting appointment system patch...<br>";

// 1. Update appointment statuses
$conn->query("ALTER TABLE appointments MODIFY COLUMN status ENUM('Pending','Confirmed','Cancelled','Completed','No-show','Accepted','Declined') DEFAULT 'Pending'");
$conn->query("ALTER TABLE telemedicine_appointments MODIFY COLUMN status ENUM('Pending','Confirmed','Cancelled','Completed','No-show','Accepted','Declined') DEFAULT 'Pending'");
echo "Updated appointment statuses.<br>";

// 2. Add phone column to doctors if not exists (for WhatsApp)
$res = $conn->query("SHOW COLUMNS FROM doctors LIKE 'phone'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE doctors ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
    echo "Added phone column to doctors table.<br>";
}

$res = $conn->query("SHOW COLUMNS FROM telemedicine_doctors LIKE 'phone'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE telemedicine_doctors ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
    echo "Added phone column to telemedicine_doctors table.<br>";
}

// 3. Create Notifications tables
$conn->query("CREATE TABLE IF NOT EXISTS doctor_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT,
    type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    action_url VARCHAR(255),
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS patient_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    action_url VARCHAR(255),
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "Notification tables created.<br>";

// 4. Create WhatsApp settings table
$conn->query("CREATE TABLE IF NOT EXISTS system_settings (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("INSERT IGNORE INTO system_settings (`key`, `value`) VALUES 
    ('whatsapp_api_url', 'https://api.twilio.com/2010-04-01/Accounts/YOUR_SID/Messages.json'),
    ('whatsapp_token', 'YOUR_TOKEN'),
    ('whatsapp_from', 'whatsapp:+14155238886'),
    ('admin_whatsapp', '+2348000000000'),
    ('enable_whatsapp', '0'),
    ('virtual_consultation_fee', '5000')
");
echo "System settings table prepared.<br>";

// 5. Add duration column to message tables
$conn->query("ALTER TABLE telemedicine_patient_messages ADD COLUMN IF NOT EXISTS duration INT DEFAULT 0");
$conn->query("ALTER TABLE telemedicine_doctor_messages ADD COLUMN IF NOT EXISTS duration INT DEFAULT 0");
echo "Message tables updated with duration column.<br>";

echo "Patch complete!";
?>