<?php
require_once 'includes/db_connect.php';

echo "<h2>Nurse Account Recovery Tool</h2>";

// 1. Ensure Table exists
$sql_table = "CREATE TABLE IF NOT EXISTS nurses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_table)) {
    echo "<p style='color:green;'>✓ Nurses table is ready.</p>";
} else {
    die("<p style='color:red;'>✗ Error creating table: " . $conn->error . "</p>");
}

// 2. Clear existing default if exists to avoid conflicts
$conn->query("DELETE FROM nurses WHERE email = 'nurse@hopehaven.com'");

// 3. Insert fresh account
$name = "Nurse Joy";
$email = "nurse@hopehaven.com";
$password = "password";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO nurses (name, email, password, department_id) VALUES (?, ?, ?, 1)");
$stmt->bind_param("sss", $name, $email, $hashed_password);

if ($stmt->execute()) {
    echo "<div style='background:#f0fdf4; padding:20px; border-radius:10px; border:1px solid #bbf7d0;'>";
    echo "<h3 style='color:#166534;'>✓ SUCCESS! Nurse Account Reset</h3>";
    echo "<p><b>Login URL:</b> <a href='nurse/login.php'>nurse/login.php</a></p>";
    echo "<p><b>Email:</b> <code>nurse@hopehaven.com</code></p>";
    echo "<p><b>Password:</b> <code>password</code></p>";
    echo "</div>";
} else {
    echo "<p style='color:red;'>✗ Insertion failed: " . $conn->error . "</p>";
}

echo "<p style='margin-top:20px; font-size:0.8em; color:#666;'>Note: Delete this file (fix_nurse.php) after use for security.</p>";
?>