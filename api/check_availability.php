<?php
require_once '../includes/db_connect.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';
$time = isset($_GET['time']) ? $_GET['time'] : '';
$email = isset($_GET['email']) ? $_GET['email'] : '';

// 1. Check if DOCTOR is available
$sql = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND TIME_FORMAT(appointment_time, '%H:%i') = ? AND status IN ('Confirmed', 'Pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $doctor_id, $date, $time);
$stmt->execute();
$result = $stmt->get_result();

$available = true;
$message = "";

if ($result->num_rows > 0) {
    $available = false;
    $message = "This doctor is already booked for this time slot.";
}

// 2. Check if PATIENT (email) is busy at this time
if ($available && !empty($email)) {
    $p_sql = "SELECT id FROM appointments WHERE email = ? AND appointment_date = ? AND TIME_FORMAT(appointment_time, '%H:%i') = ? AND status IN ('Confirmed', 'Pending')";
    $p_stmt = $conn->prepare($p_sql);
    $p_stmt->bind_param("sss", $email, $date, $time);
    $p_stmt->execute();
    if ($p_stmt->get_result()->num_rows > 0) {
        $available = false;
        $message = "You already have another appointment at this exact time.";
    }
}

header('Content-Type: application/json');
echo json_encode(['available' => $available, 'message' => $message]);
?>