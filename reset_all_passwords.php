<?php
require_once 'includes/db_connect.php';

$new_password = 'password123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$updates = [
    ['table' => 'admins', 'id_col' => 'username', 'id_val' => 'admin'],
    ['table' => 'nurses', 'id_col' => 'email', 'id_val' => 'nurse@hopehaven.com'],
    ['table' => 'pharmacists', 'id_col' => 'email', 'id_val' => 'pharmacy@hopehaven.com'],
    ['table' => 'lab_technicians', 'id_col' => 'email', 'id_val' => 'lab@hopehaven.com'],
    ['table' => 'records_staff', 'id_col' => 'email', 'id_val' => 'records@hospital.com'],
    ['table' => 'accountants', 'id_col' => 'email', 'id_val' => 'finance@hospital.com'],
    ['table' => 'doctors', 'id_col' => 'email', 'id_val' => 'doctor13@gmail.com']
];

echo "<h2>Staff Password Reset Utility</h2>";
echo "<ul>";

foreach ($updates as $update) {
    $table = $update['table'];
    $col = $update['id_col'];
    $val = $update['id_val'];
    
    $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE $col = ?");
    $stmt->bind_param("ss", $hashed_password, $val);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<li>✅ Successfully reset password for <strong>$val</strong> in <code>$table</code></li>";
        } else {
            echo "<li>⚠️ No changes made for <strong>$val</strong> (Account may not exist or password is already the same)</li>";
        }
    } else {
        echo "<li>❌ Error updating $table: " . $conn->error . "</li>";
    }
}

echo "</ul>";
echo "<p><strong>New Password for all:</strong> <code>password123</code></p>";
echo "<p><a href='staff_login.php'>Go to Login Page</a></p>";
?>