<?php
require_once 'includes/db_connect.php';
$tables = ['vital_signs', 'patients', 'lab_results'];
foreach ($tables as $table) {
    echo "<h3>Structure of $table:</h3>";
    $res = $conn->query("DESCRIBE $table");
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")<br>";
    }
}
?>