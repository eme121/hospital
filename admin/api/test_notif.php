<?php
require_once __DIR__ . '/../../includes/db_connect.php';
$res = $conn->query('SELECT * FROM admin_notifications ORDER BY id DESC LIMIT 5');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
