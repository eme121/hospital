<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id']) && !isset($_SESSION['patient_id']) && !isset($_SESSION['nurse_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'save') {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_SESSION['doctor_id'] ?? NULL;
    $sys = $_POST['blood_pressure_sys'] ?? NULL;
    $dia = $_POST['blood_pressure_dia'] ?? NULL;
    $hr = $_POST['heart_rate'] ?? NULL;
    $temp = $_POST['temperature'] ?? NULL;
    $rr = $_POST['respiratory_rate'] ?? NULL;
    $spo2 = $_POST['spo2'] ?? NULL;
    $weight = $_POST['weight'] ?? NULL;
    $height = $_POST['height'] ?? NULL;
    $notes = $_POST['notes'] ?? '';

    // Calculate BMI if height and weight are provided
    $bmi = NULL;
    if ($weight && $height && $height > 0) {
        $height_m = $height / 100;
        $bmi = round($weight / ($height_m * $height_m), 1);
    }

    $stmt = $conn->prepare("INSERT INTO vital_signs (patient_id, doctor_id, blood_pressure_sys, blood_pressure_dia, heart_rate, temperature, respiratory_rate, spo2, weight, height, bmi, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiididddds", $patient_id, $doctor_id, $sys, $dia, $hr, $temp, $rr, $spo2, $weight, $height, $bmi, $notes);

    if ($stmt->execute()) {
        $vital_id = $conn->insert_id;
        log_audit("Recorded Vitals", "vitals", $vital_id, "BP: $sys/$dia, HR: $hr, Temp: $temp");
        
        // SYNC: Automatically post to Telemedicine Chat
        if (isset($_POST['case_id']) && intval($_POST['case_id']) > 0) {
            $case_id = intval($_POST['case_id']);
            $chat_msg = "📊 VITALS RECORDED: BP: $sys/$dia mmHg, HR: $hr bpm, Temp: $temp °C";
            $chat_stmt = $conn->prepare("INSERT INTO telemedicine_messages (case_id, doctor_id, message, message_type) VALUES (?, ?, ?, 'clinical_action')");
            $chat_stmt->bind_param("iis", $case_id, $doctor_id, $chat_msg);
            $chat_stmt->execute();

            // FEATURE 10: LEDGER
            require_once '../includes/ledger_helper.php';
            log_telemedicine_ledger($conn, $case_id, $doctor_id, 'VITALS_RECORDED', $chat_msg);
        }

        echo json_encode(['success' => true, 'bmi' => $bmi]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}

if ($action === 'get') {
    $patient_id = $_GET['patient_id'] ?? $_SESSION['patient_id'];
    $limit = intval($_GET['limit'] ?? 10);

    $stmt = $conn->prepare("SELECT * FROM vital_signs WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT ?");
    $stmt->bind_param("ii", $patient_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vitals = [];
    while ($row = $result->fetch_assoc()) {
        $vitals[] = $row;
    }
    echo json_encode(['success' => true, 'vitals' => $vitals]);
}

if ($action === 'get_latest') {
    $patient_id = intval($_GET['patient_id'] ?? 0);
    $before_ts = $_GET['before_timestamp'] ?? null;
    
    // Get latest single record before timestamp
    $sql = "SELECT * FROM vital_signs WHERE patient_id = ?";
    if ($before_ts) {
        $sql .= " AND recorded_at <= ?";
    }
    $sql .= " ORDER BY recorded_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if ($before_ts) {
        $stmt->bind_param("is", $patient_id, $before_ts);
    } else {
        $stmt->bind_param("i", $patient_id);
    }
    $stmt->execute();
    $vitals = $stmt->get_result()->fetch_assoc();

    // FEATURE 3: Historical Trends for Sparklines (also filtered by timestamp if provided)
    $trend_sql = "SELECT heart_rate, temperature, blood_pressure_sys, recorded_at FROM vital_signs WHERE patient_id = ?";
    if ($before_ts) {
        $trend_sql .= " AND recorded_at <= ?";
    }
    $trend_sql .= " ORDER BY recorded_at DESC LIMIT 10";

    $trend_stmt = $conn->prepare($trend_sql);
    if ($before_ts) {
        $trend_stmt->bind_param("is", $patient_id, $before_ts);
    } else {
        $trend_stmt->bind_param("i", $patient_id);
    }
    $trend_stmt->execute();
    $trend_res = $trend_stmt->get_result();
    $trends = [];
    while($row = $trend_res->fetch_assoc()) {
        $trends[] = $row;
    }

    echo json_encode(['success' => true, 'vitals' => $vitals, 'trends' => array_reverse($trends)]);
}
?>