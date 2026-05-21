<?php
session_start();
require_once '../../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action']) || !isset($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $data['action'];
$items = $data['items']; // Array of objects {id: 1, type: 'Physical'}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit;
}

$success_count = 0;
$error_message = '';

foreach ($items as $item) {
    $id = intval($item['id']);
    $type = $item['type'];
    
    // Explicitly map allowed types to tables
    $table = ($type === 'Virtual') ? 'telemedicine_appointments' : (($type === 'Physical') ? 'appointments' : null);
    
    if (!$table) continue;

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);
    } elseif ($action === 'archive') {
        $stmt = $conn->prepare("UPDATE $table SET is_archived = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
    } elseif ($action === 'restore') {
        $stmt = $conn->prepare("UPDATE $table SET is_archived = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
    } elseif ($action === 'status_update' && isset($data['status'])) {
        $status = $data['status'];
        $stmt = $conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);

        // Billing Integration: Create invoice if status is 'Completed'
        if ($status === 'Completed') {
            require_once '../../api/billing_engine.php';
            $billing = new BillingEngine($conn);

            // Fetch appointment details to get patient_id and department
            $details = $conn->query("SELECT a.patient_id, d.name as dept_name, d.consultation_fee 
                                    FROM $table a 
                                    JOIN departments d ON a.department_id = d.id 
                                    WHERE a.id = $id")->fetch_assoc();

            if ($details && $details['patient_id']) {
                $billing->automateInvoice($details['patient_id'], [
                    [
                        'description' => "Consultation Fee: " . $details['dept_name'],
                        'type' => 'Consultation',
                        'price' => $details['consultation_fee']
                    ]
                ], $id, $type);
            }
        }
    } else {
        continue;
    }

    if ($stmt->execute()) {
        $success_count++;
        // Trigger Real-Time Sync Signal
        require_once '../../includes/sync_helper.php';
        SyncManager::signal('patient_queue', 'UPDATE', $id);
    } else {
        $error_message = $stmt->error;
    }
}

echo json_encode([
    'success' => $success_count > 0,
    'count' => $success_count,
    'message' => $error_message ?: "Action completed successfully"
]);
?>