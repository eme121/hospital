<?php
require_once '../includes/db_connect.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$type = isset($_GET['type']) ? $_GET['type'] : 'physical';

header('Content-Type: application/json');

if ($doctor_id <= 0) {
    echo json_encode([]);
    exit;
}

$total_slots_per_day = 7; // 09:00, 10:00, 11:00, 12:00, 14:00, 15:00, 16:00

// Fetch dates that have ALL slots booked across both types
$sql = "SELECT appointment_date FROM (
            SELECT doctor_id, appointment_date, appointment_time, status FROM appointments
            UNION ALL
            SELECT doctor_id, appointment_date, appointment_time, status FROM telemedicine_appointments
        ) as combined 
        WHERE doctor_id = ? 
        AND MONTH(appointment_date) = ? 
        AND YEAR(appointment_date) = ? 
        AND status IN ('Confirmed', 'Pending')
        GROUP BY appointment_date
        HAVING COUNT(*) >= $total_slots_per_day";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $doctor_id, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

$fully_booked_dates = [];
while ($row = $result->fetch_assoc()) {
    $fully_booked_dates[] = $row['appointment_date'];
}

echo json_encode($fully_booked_dates);
?>