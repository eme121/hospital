<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id = intval($_POST['appointment_id']);
$type = $_POST['type'];
$rating = intval($_POST['rating']);
$feedback = strip_tags($_POST['feedback']);

$table = ($type === 'Virtual') ? 'telemedicine_appointments' : 'appointments';

$stmt = $conn->prepare("UPDATE $table SET rating = ?, feedback = ? WHERE id = ? AND patient_id = ?");
$stmt->bind_param("isii", $rating, $feedback, $id, $_SESSION['patient_id']);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Thank you for your feedback!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save feedback.']);
}
?>