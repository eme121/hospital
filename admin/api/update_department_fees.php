<?php
session_start();
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = $_POST;
if (empty($data)) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
}

if (!isset($data['department_id']) || !isset($data['consultation_fee'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$dept_id = intval($data['department_id']);
$fee = floatval($data['consultation_fee']);

$stmt = $conn->prepare("UPDATE departments SET consultation_fee = ? WHERE id = ?");
$stmt->bind_param("di", $fee, $dept_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>