<?php
require_once 'includes/db_connect.php';
$res = $conn->query("DESCRIBE telemedicine_doctors");
if (!$res) {
    echo "DESCRIBE failed: " . $conn->error . "\n";
    exit;
}
echo "Structure of telemedicine_doctors:\n";
while($row = $res->fetch_assoc()) {
    echo "- {$row['Field']} ({$row['Type']})\n";
}

$res2 = $conn->query("SELECT COUNT(*) as count FROM telemedicine_doctors");
if ($res2) {
    echo "Count: " . $res2->fetch_assoc()['count'] . "\n";
} else {
    echo "COUNT failed: " . $conn->error . "\n";
}
?>