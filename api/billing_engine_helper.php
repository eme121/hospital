<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_fee') {
    $type = $_GET['type'] ?? 'physical';
    $dept_id = intval($_GET['dept_id'] ?? 0);

    if ($type === 'virtual') {
        $res = $conn->query("SELECT value FROM system_settings WHERE `key` = 'virtual_consultation_fee'");
        $fee = $res->fetch_assoc()['value'] ?? 5000;
        echo json_encode(['success' => true, 'fee' => floatval($fee)]);
    } else {
        $stmt = $conn->prepare("SELECT consultation_fee FROM departments WHERE id = ?");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'fee' => floatval($res['consultation_fee'] ?? 0)]);
    }
    exit;
}
?>