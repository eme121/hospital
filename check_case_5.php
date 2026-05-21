<?php
$conn = new mysqli('localhost', 'root', '', 'hospital_db');
$res = $conn->query('SELECT id, patient_name_or_id, status, created_by FROM telemedicine_cases WHERE id = 5');
print_r($res->fetch_assoc());
$conn->close();
?>