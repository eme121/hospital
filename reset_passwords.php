<?php
/**
 * USE THIS SCRIPT ONLY TO RECOVER LOST ACCOUNTS.
 * DELETE THIS FILE AFTER USE FOR SECURITY.
 */
$conn = new mysqli('localhost', 'root', '', 'hospital_db');
if ($conn->connect_error) die("Connection failed");

$target_user = 'admin'; // Change this to the email/username you want to reset
$new_password = 'password123'; // Change this to your desired new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Table configuration
$configs = [
    'admins' => 'username',
    'telemedicine_doctors' => 'email',
    'lab_technicians' => 'email',
    'pharmacists' => 'email',
    'nurses' => 'email'
];

$success = false;
foreach ($configs as $table => $column) {
    $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE $column = ?");
    $stmt->bind_param("ss", $hashed_password, $target_user);
    $stmt->execute();
    if ($conn->affected_rows > 0) {
        echo "Successfully updated password for '$target_user' in table '$table'.\n";
        $success = true;
        break;
    }
}

if (!$success) {
    echo "Could not find user '$target_user' in any table. Please check the identifier.\n";
}

$conn->close();
?>