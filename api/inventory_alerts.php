<?php
session_start();
if (!isset($_SESSION['pharmacist_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_alerts') {
    $alerts = [];
    
    // 1. Check Expiry (Next 90 Days) - Combined Pharmacy & Main Store
    $sql_exp = "SELECT drug_name, expiry_date, 'Pharmacy' as location 
                FROM pharmacy_stock 
                WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date != '0000-00-00'
                UNION
                SELECT drug_name, expiry_date, 'Main Store' as location 
                FROM main_store_inventory 
                WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date != '0000-00-00'
                ORDER BY expiry_date ASC";
    
    $res_exp = $conn->query($sql_exp);
    while($row = $res_exp->fetch_assoc()) {
        $days = floor((strtotime($row['expiry_date']) - time()) / 86400);
        $alerts[] = [
            'type' => $days <= 0 ? 'expired' : 'expiring_soon',
            'item' => $row['drug_name'],
            'location' => $row['location'],
            'date' => date('d M Y', strtotime($row['expiry_date'])),
            'days_left' => $days,
            'severity' => $days <= 30 ? 'critical' : 'warning'
        ];
    }

    // 2. Check Low Stock - Combined Pharmacy & Main Store
    $sql_low = "SELECT drug_name, quantity, reorder_level, 'Pharmacy' as location 
                FROM pharmacy_stock 
                WHERE quantity <= reorder_level
                UNION
                SELECT drug_name, quantity, reorder_level, 'Main Store' as location 
                FROM main_store_inventory 
                WHERE quantity <= reorder_level
                ORDER BY (quantity/reorder_level) ASC";
                
    $res_low = $conn->query($sql_low);
    while($row = $res_low->fetch_assoc()) {
        $alerts[] = [
            'type' => 'low_stock',
            'item' => $row['drug_name'],
            'location' => $row['location'],
            'current_qty' => $row['quantity'],
            'threshold' => $row['reorder_level'],
            'severity' => $row['quantity'] <= ($row['reorder_level'] * 0.5) ? 'critical' : 'warning'
        ];
    }

    echo json_encode(['success' => true, 'alerts' => $alerts]);
    exit;
}
?>