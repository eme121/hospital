<?php
require_once '../includes/db_connect.php';

$sql = "SELECT id, name FROM departments ORDER BY name ASC";
$result = $conn->query($sql);

$departments = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($departments);
?>