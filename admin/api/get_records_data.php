<?php
session_start();
require_once '../../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['records_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tab = $_GET['tab'] ?? 'dashboard';

$sql = "SELECT po.*, p.full_name, p.file_number, p.email, p.phone, ft.name as folder_name 
        FROM patient_onboarding po 
        JOIN patients p ON po.patient_id = p.id 
        LEFT JOIN folder_types ft ON po.folder_type_id = ft.id";

switch ($tab) {
    case 'dashboard':
        $sql .= " WHERE po.status = 'Pending Records'";
        break;
    case 'archive':
        $sql .= " WHERE po.status = 'Sent to Nursing'";
        break;
    case 'all':
    default:
        // No filter for all, but maybe exclude 'Not Started'
        $sql .= " WHERE po.status != 'Not Started'";
        break;
}

$sql .= " ORDER BY po.updated_at DESC";

$res = $conn->query($sql);
$patients = [];

while ($row = $res->fetch_assoc()) {
    $patients[] = $row;
}

echo json_encode(['success' => true, 'patients' => $patients]);
?>