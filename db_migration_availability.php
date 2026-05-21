<?php
require_once 'includes/db_connect.php';

// Drop old table if exists to upgrade to rule-based system
$conn->query("DROP TABLE IF EXISTS doctor_availability");

$sql = "
CREATE TABLE IF NOT EXISTS doctor_availability_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    title VARCHAR(100) DEFAULT 'General Availability',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_of_week VARCHAR(50) NOT NULL, -- Store as '1,2,3' (0=Sun, 6=Sat)
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);";

if ($conn->query($sql)) {
    echo "<div style='font-family:sans-serif; padding:40px; text-align:center;'>
            <h1 style='color:#10b981;'>Migration Successful!</h1>
            <p>The Advanced Availability Rules table has been created.</p>
            <a href='admin/manage_availability.php' style='padding:12px 24px; background:#2563eb; color:white; border-radius:12px; text-decoration:none; font-weight:bold;'>Go to Admin Scheduler</a>
          </div>";
} else {
    echo "Migration Error: " . $conn->error;
}
?>