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
$id = intval($_POST['id'] ?? 0);
$type = $_POST['type'] ?? 'Physical';
$status = $_POST['status'] ?? ''; // Accepted, Declined, Dismissed, or Restore

if ($id <= 0 || !in_array($status, ['Accepted', 'Declined', 'Dismissed', 'Restore'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

NotificationService::setConnection($conn);

$table = ($type === 'Virtual') ? 'telemedicine_appointments' : 'appointments';

// Handle Dismissal/Restore separately to preserve original status
if ($status === 'Dismissed' || $status === 'Restore') {
    $val = ($status === 'Dismissed') ? 1 : 0;
    $update_stmt = $conn->prepare("UPDATE $table SET is_archived = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $val, $id);
    if ($update_stmt->execute()) {
        $msg = ($status === 'Dismissed') ? 'Appointment hidden.' : 'Appointment restored.';
        echo json_encode(['success' => true, 'message' => $msg]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Operation failed.']);
    }
    exit;
}

// 1. Fetch appointment details before update
$stmt = $conn->prepare("SELECT patient_id, patient_name, email, phone, appointment_date, appointment_time FROM $table WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();

if (!$appt) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
    exit;
}

// 2. Update status
$update_stmt = $conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
$update_stmt->bind_param("si", $status, $id);

if ($update_stmt->execute()) {
    // Trigger Real-Time Sync Signal
    require_once '../includes/sync_helper.php';
    SyncManager::signal('patient_queue', 'UPDATE', $id);

    $date = $appt['appointment_date'];
    $time = $appt['appointment_time'];
    $doctor_name = $_SESSION['doctor_name'];

    // 3. Notify Patient
    $patient_title = "Appointment $status";
    $patient_msg = "Your $type appointment for $date at $time with Dr. $doctor_name has been $status.";
    NotificationService::send('patient', $appt['patient_id'], 'appointment_update', $patient_title, $patient_msg, 'patient_dashboard.php', [
        'email' => $appt['email'],
        'phone' => $appt['phone']
    ]);

    // 4. Notify Admin
    $admin_msg = "Doctor $doctor_name has $status the $type appointment for {$appt['patient_name']} ($date at $time).";
    $admin_whatsapp = $conn->query("SELECT value FROM system_settings WHERE `key` = 'admin_whatsapp'")->fetch_assoc()['value'] ?? '';
    NotificationService::send('admin', null, 'appointment_update', "Appointment $status", $admin_msg, 'dashboard.php', [
        'email' => SMTP_USER,
        'phone' => $admin_whatsapp
    ]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update appointment.']);
}
?>