<?php
require_once 'includes/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(100),
    department_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
)";

if ($conn->query($sql)) {
    echo "Table services created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}
?>