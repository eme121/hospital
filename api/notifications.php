<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$role = $_GET['role'] ?? '';
$action = $_GET['action'] ?? '';

if ($action === 'get') {
    if ($role === 'patient') {
        $patient_id = $_SESSION['patient_id'];
        $stmt = $conn->prepare("SELECT * FROM patient_notifications WHERE patient_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->bind_param("i", $patient_id);
    } elseif ($role === 'doctor') {
        $doctor_id = $_SESSION['doctor_id'];
        $stmt = $conn->prepare("SELECT * FROM doctor_notifications WHERE doctor_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->bind_param("i", $doctor_id);
    } elseif ($role === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10");
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid role.']);
        exit;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    $unread_count = 0;
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
        if ($row['status'] === 'unread') $unread_count++;
    }
    echo json_encode(['success' => true, 'notifications' => $notifications, 'unread_count' => $unread_count]);
}

if ($action === 'mark_read') {
    if ($role === 'patient') {
        $patient_id = $_SESSION['patient_id'];
        $stmt = $conn->prepare("UPDATE patient_notifications SET status = 'read' WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
    } elseif ($role === 'doctor') {
        $doctor_id = $_SESSION['doctor_id'];
        $stmt = $conn->prepare("UPDATE doctor_notifications SET status = 'read' WHERE doctor_id = ?");
        $stmt->bind_param("i", $doctor_id);
    } elseif ($role === 'admin') {
        $stmt = $conn->prepare("UPDATE admin_notifications SET status = 'read'");
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>