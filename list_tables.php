<?php
require_once 'includes/db_connect.php';

echo "Database: " . $dbname . "\n";
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>