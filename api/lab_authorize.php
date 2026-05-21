<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/notifications_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$result_id = intval($_POST['result_id'] ?? 0);
$comments = $_POST['comments'] ?? '';

if ($result_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Result ID.']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Fetch details for notification
    $stmt = $conn->prepare("SELECT r.id, p.id as patient_id, p.full_name as patient_name, p.email, p.phone, t.test_name 
                            FROM lab_results r 
                            JOIN patients p ON r.patient_id = p.id 
                            JOIN lab_requests lr ON r.request_id = lr.id 
                            JOIN lab_tests t ON lr.test_id = t.id 
                            WHERE r.id = ?");
    $stmt->bind_param("i", $result_id);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();

    if (!$info) {
        throw new Exception("Result not found.");
    }

    // 2. Update Status to Released
    $update = $conn->prepare("UPDATE lab_results SET status = 'Released', doctor_comments = ? WHERE id = ?");
    $update->bind_param("si", $comments, $result_id);
    $update->execute();

    // 3. Notify Patient
    NotificationService::setConnection($conn);
    $patient_msg = "Dear {$info['patient_name']}, your lab results for {$info['test_name']} have been reviewed and released by your doctor.";
    if (!empty($comments)) {
        $patient_msg .= "\nDoctor's Remarks: $comments";
    }
    $patient_msg .= "\nYou can view them now in your dashboard.";

    NotificationService::send('patient', $info['patient_id'], 'lab', 'Lab Results Released', $patient_msg, 'patient_labs.php', [
        'email' => $info['email'],
        'phone' => $info['phone']
    ]);

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>