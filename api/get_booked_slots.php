<?php
require_once '../includes/db_connect.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'physical';

header('Content-Type: application/json');

if ($doctor_id <= 0 || empty($date)) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT TIME_FORMAT(appointment_time, '%H:%i') as slot FROM (
            SELECT doctor_id, appointment_date, appointment_time, status FROM appointments
            UNION ALL
            SELECT doctor_id, appointment_date, appointment_time, status FROM telemedicine_appointments
        ) as combined 
        WHERE doctor_id = ? 
        AND appointment_date = ? 
        AND status IN ('Confirmed', 'Pending')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $doctor_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$booked_slots = [];
while ($row = $result->fetch_assoc()) {
    $booked_slots[] = $row['slot'];
}

echo json_encode($booked_slots);
?>