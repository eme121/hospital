<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_type = $_SESSION['doctor_type'] ?? 'telemedicine';

// Get associated IDs (logic from telemedicine_dashboard.php)
$email_sql = ($doctor_type === 'telemedicine') ? "SELECT email FROM telemedicine_doctors WHERE id = ?" : "SELECT email FROM doctors WHERE id = ?";
$email_stmt = $conn->prepare($email_sql);
$email_stmt->bind_param("i", $doctor_id);
$email_stmt->execute();
$doctor_email = $email_stmt->get_result()->fetch_assoc()['email'] ?? '';

$all_doctor_ids = [$doctor_id];
if ($doctor_email) {
    $ids_res = $conn->query("SELECT id FROM doctors WHERE email = '$doctor_email' UNION SELECT id FROM telemedicine_doctors WHERE email = '$doctor_email'");
    while($row = $ids_res->fetch_assoc()) { $all_doctor_ids[] = (int)$row['id']; }
}
$ids_list = implode(',', array_unique($all_doctor_ids));

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$events = [];

// 1. Physical Appointments
$sql_physical = "SELECT a.id, a.patient_name, a.appointment_date, a.appointment_time, a.status 
                 FROM appointments a 
                 WHERE a.doctor_id IN ($ids_list) AND a.is_archived = 0";
if ($start && $end) {
    $sql_physical .= " AND a.appointment_date BETWEEN '$start' AND '$end'";
}

$res_p = $conn->query($sql_physical);
while($row = $res_p->fetch_assoc()) {
    $color = '#3b82f6'; // Blue for physical
    if ($row['status'] === 'Cancelled') $color = '#ef4444';
    if ($row['status'] === 'Confirmed') $color = '#10b981';

    $events[] = [
        'id' => 'p_' . $row['id'],
        'title' => $row['patient_name'],
        'start' => $row['appointment_date'] . 'T' . $row['appointment_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'type' => 'Physical',
            'status' => $row['status']
        ]
    ];
}

// 2. Virtual Appointments
$sql_virtual = "SELECT ta.id, ta.patient_name, ta.appointment_date, ta.appointment_time, ta.status 
                FROM telemedicine_appointments ta 
                WHERE ta.doctor_id IN ($ids_list) AND ta.is_archived = 0";
if ($start && $end) {
    $sql_virtual .= " AND ta.appointment_date BETWEEN '$start' AND '$end'";
}

$res_v = $conn->query($sql_virtual);
while($row = $res_v->fetch_assoc()) {
    $color = '#6366f1'; // Indigo for virtual
    if ($row['status'] === 'Cancelled') $color = '#ef4444';
    if ($row['status'] === 'Confirmed') $color = '#8b5cf6';

    $events[] = [
        'id' => 'v_' . $row['id'],
        'title' => '[V] ' . $row['patient_name'],
        'start' => $row['appointment_date'] . 'T' . $row['appointment_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'type' => 'Virtual',
            'status' => $row['status']
        ]
    ];
}

echo json_encode($events);
?>