<?php
$conn = new mysqli('localhost', 'root', '', 'hospital_db');
if ($conn->connect_error) die("Connection failed");

$new_password = 'password123';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

// 1. Reset specific staff accounts
$staff_resets = [
    ['admins', 'username', 'admin'],
    ['lab_technicians', 'email', 'lab@hopehaven.com'],
    ['pharmacists', 'email', 'pharmacy@hopehaven.com'],
    ['nurses', 'email', 'nurse@hopehaven.com']
];

echo "Resetting Main Staff passwords to: $new_password\n";
foreach ($staff_resets as $r) {
    $table = $r[0];
    $col = $r[1];
    $val = $r[2];
    
    $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE $col = ?");
    $stmt->bind_param("ss", $hashed, $val);
    $stmt->execute();
    echo "Processed $val ($table): " . ($conn->affected_rows > 0 ? "SUCCESS" : "NO CHANGE") . "\n";
}

// 2. Reset ALL Doctor accounts for testing
echo "\nResetting ALL Doctor passwords to: $new_password\n";
$stmt = $conn->prepare("UPDATE telemedicine_doctors SET password = ?");
$stmt->bind_param("s", $hashed);
$stmt->execute();
echo "Doctors updated: " . $conn->affected_rows . "\n";

$conn->close();
?>