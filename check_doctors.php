<?php
require_once 'includes/db_connect.php';
$res = $conn->query("SELECT id, name, allow_physical, allow_virtual FROM doctors");
echo "Total Doctors: " . $res->num_rows . "\n";
while($row = $res->fetch_assoc()) {
    echo "- {$row['name']} (ID: {$row['id']}): Physical: {$row['allow_physical']}, Virtual: {$row['allow_virtual']}\n";
}
?>