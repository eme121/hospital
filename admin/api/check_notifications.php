<?php
session_start();
require_once '../../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$action = $_GET['action'] ?? 'fetch';

if ($action === 'mark_read') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $conn->query("UPDATE admin_notifications SET status = 'read' WHERE id = $id");
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'mark_all_read') {
    $conn->query("UPDATE admin_notifications SET status = 'read'");
    echo json_encode(['success' => true]);
    exit;
}

// Fetch unread notifications for polling
$last_id = intval($_GET['last_id'] ?? 0);
$new_notifications = [];
$res = $conn->query("SELECT * FROM admin_notifications WHERE status = 'unread' AND id > $last_id ORDER BY id DESC");
while ($row = $res->fetch_assoc()) {
    $new_notifications[] = $row;
}

// Also get the total unread count
$count_res = $conn->query("SELECT COUNT(*) as total FROM admin_notifications WHERE status = 'unread'");
$unread_count = $count_res->fetch_assoc()['total'];

// Fetch recent 10 notifications for the history dropdown
$history = [];
$res_h = $conn->query("SELECT * FROM admin_notifications ORDER BY id DESC LIMIT 10");
while ($row = $res_h->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode([
    'new' => $new_notifications,
    'unread_count' => $unread_count,
    'history' => $history,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
