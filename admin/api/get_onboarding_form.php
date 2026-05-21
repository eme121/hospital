<?php
session_start();
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['records_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$patient_id = (int)($_GET['patient_id'] ?? 0);

if (!$patient_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid Patient ID']);
    exit;
}

$sql = "SELECT section_name, field_name, field_value FROM patient_form_data WHERE patient_id = ? ORDER BY section_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$form_data = [];
while ($row = $result->fetch_assoc()) {
    $section = $row['section_name'];
    $field = $row['field_name'];
    $value = $row['field_value'];
    
    if (!isset($form_data[$section])) {
        $form_data[$section] = [];
    }
    $form_data[$section][$field] = $value;
}

echo json_encode(['success' => true, 'form_data' => $form_data]);
?>