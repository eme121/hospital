<?php
require_once 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM patient_onboarding LIMIT 10");
echo "<pre>";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>