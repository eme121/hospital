<?php
require_once 'includes/db_connect.php';
$res = $conn->query("DESCRIBE patients");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>