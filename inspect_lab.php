<?php
require_once 'includes/db_connect.php';

echo "Inspecting lab_samples table...<br>";
$res = $conn->query("DESCRIBE lab_samples");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "Column: " . $row['Field'] . " - Type: " . $row['Type'] . "<br>";
    }
} else {
    echo "Error: " . $conn->error;
}
?>