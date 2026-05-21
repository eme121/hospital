<?php
session_start();
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Try to get data from $_POST first, then php://input (JSON)
$data = $_POST;
if (empty($data)) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
}

if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'No data received. Data: ' . file_get_contents('php://input')]);
    exit;
}

$conn->begin_transaction();
try {
    $stmt_sys = $conn->prepare("UPDATE system_settings SET value = ? WHERE `key` = ?");
    $stmt_folder_price = $conn->prepare("UPDATE folder_types SET price = ? WHERE id = ?");
    $stmt_folder_desc = $conn->prepare("UPDATE folder_types SET description = ? WHERE id = ?");

    foreach ($data as $key => $value) {
        if (strpos($key, 'folder_price_') === 0) {
            $fid = (int)str_replace('folder_price_', '', $key);
            $stmt_folder_price->bind_param("di", $value, $fid);
            $stmt_folder_price->execute();
        } elseif (strpos($key, 'folder_desc_') === 0) {
            $fid = (int)str_replace('folder_desc_', '', $key);
            $stmt_folder_desc->bind_param("si", $value, $fid);
            $stmt_folder_desc->execute();
        } else {
            $stmt_sys->bind_param("ss", $value, $key);
            $stmt_sys->execute();
        }
    }
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>