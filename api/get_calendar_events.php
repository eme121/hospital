<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_helper.php';

header('Content-Type: application/json');

// Only staff or admin
if (!Auth::getRole()) {
    die(json_encode(['error' => 'Unauthorized']));
}

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$events = [];

// 1. Physical Appointments
$sql_physical = "SELECT a.id, a.patient_name, a.appointment_date, a.appointment_time, a.status, d.name as doctor_name 
                 FROM appointments a 
                 LEFT JOIN doctors d ON a.doctor_id = d.id 
                 WHERE a.is_archived = 0";
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
        'title' => $row['patient_name'] . ' (Dr. ' . ($row['doctor_name'] ?? 'TBD') . ')',
        'start' => $row['appointment_date'] . 'T' . $row['appointment_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'type' => 'Physical',
            'status' => $row['status'],
            'doctor' => $row['doctor_name']
        ]
    ];
}

// 2. Virtual Appointments
$sql_virtual = "SELECT ta.id, ta.patient_name, ta.appointment_date, ta.appointment_time, ta.status, td.name as doctor_name 
                FROM telemedicine_appointments ta 
                LEFT JOIN telemedicine_doctors td ON ta.doctor_id = td.id 
                WHERE ta.is_archived = 0";
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
            'status' => $row['status'],
            'doctor' => $row['doctor_name']
        ]
    ];
}

echo json_encode($events);
?>