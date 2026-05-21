<?php
$conn = new mysqli('localhost', 'root', '', 'hospital_db');
if ($conn->connect_error) die('Connection failed');
$res = $conn->query('DESCRIBE financial_aid_requests');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
$conn->close();
?>