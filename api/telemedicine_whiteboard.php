<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? '';
$doctor_id = $_SESSION['doctor_id'];

if ($action === 'save_stroke') {
    $case_id = intval($_POST['case_id'] ?? 0);
    $stroke_data = $_POST['stroke_data'] ?? ''; // JSON string of points
    $color = $_POST['color'] ?? '#f43f5e';

    if ($case_id <= 0 || empty($stroke_data)) {
        echo json_encode(['success' => false]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO telemedicine_annotations (case_id, doctor_id, stroke_data, color) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $case_id, $doctor_id, $stroke_data, $color);

    if ($stmt->execute()) {
        SyncManager::signal('telemedicine_whiteboard', 'INSERT', $case_id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

if ($action === 'get_strokes') {
    $case_id = intval($_GET['case_id'] ?? 0);
    $last_stroke_id = intval($_GET['last_id'] ?? 0);

    $stmt = $conn->prepare("SELECT * FROM telemedicine_annotations WHERE case_id = ? AND id > ? ORDER BY id ASC");
    $stmt->bind_param("ii", $case_id, $last_stroke_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $strokes = [];
    while ($row = $result->fetch_assoc()) {
        $strokes[] = $row;
    }
    echo json_encode(['success' => true, 'strokes' => $strokes]);
}

if ($action === 'clear') {
    $case_id = intval($_POST['case_id'] ?? 0);
    $conn->query("DELETE FROM telemedicine_annotations WHERE case_id = $case_id");
    SyncManager::signal('telemedicine_whiteboard', 'DELETE', $case_id);
    echo json_encode(['success' => true]);
}
?>