<?php
session_start();

require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';
require_once '../includes/clinical_helper.php';
require_once 'billing_engine.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Action available to both doctors and nurses
if ($action === 'get_available_tests') {
    if (!isset($_SESSION['nurse_id']) && !isset($_SESSION['doctor_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }
    $sql = "SELECT id, test_name, category, price FROM lab_tests ORDER BY category, test_name";
    $result = $conn->query($sql);
    echo json_encode(['success' => true, 'tests' => $result->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if (!isset($_SESSION['nurse_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$nurse_id = $_SESSION['nurse_id'];

if ($action === 'get_nursing_queue') {
    $result = ClinicalHelper::getNursingQueue($conn);
    echo json_encode(['success' => true, 'queue' => $result->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'search_patients') {
    $term = $_GET['term'] ?? '';
    $sql = "SELECT id, full_name, file_number, phone FROM patients WHERE (full_name LIKE ? OR file_number LIKE ?) AND is_deleted = 0 LIMIT 10";
    $stmt = $conn->prepare($sql);
    $search = "%$term%";
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    echo json_encode(['success' => true, 'patients' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'start_visit') {
    $patient_id = $_POST['patient_id'];
    $complaints = $_POST['complaints'] ?? '';
    $history = $_POST['history'] ?? '';
    $visit_id = $_POST['visit_id'] ?? null;

    if ($visit_id) {
        $stmt = $conn->prepare("UPDATE patient_visits SET presenting_complaints = ?, medical_history = ? WHERE id = ?");
        $stmt->bind_param("ssi", $complaints, $history, $visit_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO patient_visits (patient_id, nurse_id, presenting_complaints, medical_history) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $patient_id, $nurse_id, $complaints, $history);
    }
    
    if ($stmt->execute()) {
        $v_id = $visit_id ?: $conn->insert_id;
        // Mark onboarding as "In Intake" to remove from queue
        $conn->query("UPDATE patient_onboarding SET status = 'In Intake' WHERE patient_id = $patient_id AND status = 'Sent to Nursing'");
        
        // Trigger Sync Signals
        SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
        SyncManager::signal('clinical_visits', 'INSERT', $v_id);

        echo json_encode(['success' => true, 'visit_id' => $v_id]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

if ($action === 'save_vitals') {
    $visit_id = $_POST['visit_id'];
    $patient_id = $_POST['patient_id'];
    $sys = $_POST['blood_pressure_sys'];
    $dia = $_POST['blood_pressure_dia'];
    $hr = $_POST['heart_rate'];
    $pulse = $_POST['pulse'];
    $temp = $_POST['temperature'];
    $fbs = $_POST['fasting_blood_sugar'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    $bmi = $_POST['bmi'];
    $notes = $_POST['notes'] ?? '';

    // Check if vitals already exist for this visit
    $check = $conn->query("SELECT id FROM vital_signs WHERE visit_id = $visit_id");
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE vital_signs SET blood_pressure_sys=?, blood_pressure_dia=?, heart_rate=?, pulse=?, temperature=?, fasting_blood_sugar=?, weight=?, height=?, bmi=?, notes=? WHERE visit_id=?");
        $stmt->bind_param("ddidiidddsi", $sys, $dia, $hr, $pulse, $temp, $fbs, $weight, $height, $bmi, $notes, $visit_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO vital_signs (patient_id, visit_id, nurse_id, blood_pressure_sys, blood_pressure_dia, heart_rate, pulse, temperature, fasting_blood_sugar, weight, height, bmi, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiidiiddddds", $patient_id, $visit_id, $nurse_id, $sys, $dia, $hr, $pulse, $temp, $fbs, $weight, $height, $bmi, $notes);
    }

    if ($stmt->execute()) {
        SyncManager::signal('clinical_visits', 'UPDATE', $visit_id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

if ($action === 'get_doctors') {
    $doctors = $conn->query("SELECT id, name FROM telemedicine_doctors WHERE status = 'Approved'")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'doctors' => $doctors]);
    exit;
}

if ($action === 'get_visit_history') {
    $patient_id = $_GET['patient_id'];
    $sql = "SELECT v.*, n.name as nurse_name
            FROM patient_visits v
            LEFT JOIN nurses n ON v.nurse_id = n.id
            WHERE v.patient_id = ?
            ORDER BY v.visit_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'history' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'refer_to_doctor') {
    $visit_id = $_POST['visit_id'];
    $doctor_id = $_POST['doctor_id'];
    $priority = $_POST['priority'];
    $notes = $_POST['notes'];
    $patient_id = $_POST['patient_id'];

    $stmt = $conn->prepare("INSERT INTO nursing_referrals (visit_id, doctor_id, nurse_id, priority, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $visit_id, $doctor_id, $nurse_id, $priority, $notes);

    if ($stmt->execute()) {
        $conn->query("UPDATE patient_visits SET status = 'Referred' WHERE id = $visit_id");

        // Update Queue Status - NOW WITH doctor_id mapping
        $conn->query("INSERT INTO patient_queue_status (patient_id, current_stage, status, notes, doctor_id)
                      VALUES ($patient_id, 'Doctor', 'From Nursing (No Lab)', '$notes', $doctor_id)
                      ON DUPLICATE KEY UPDATE current_stage='Doctor', status='From Nursing (No Lab)', notes='$notes', doctor_id=$doctor_id");

        SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
        SyncManager::signal('clinical_visits', 'UPDATE', $visit_id);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

if ($action === 'request_lab_test') {
    $patient_id = $_POST['patient_id'];
    $visit_id = $_POST['visit_id'];
    $test_ids = $_POST['test_ids']; // Array of IDs
    $priority = $_POST['priority'] ?? 'Normal';
    $notes = $_POST['notes'] ?? '';

    if (empty($test_ids)) {
        echo json_encode(['success' => false, 'message' => 'No tests selected.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $billing_items = [];
        $billing = new BillingEngine($conn);

        foreach ($test_ids as $test_id) {
            // Get test details
            $t_stmt = $conn->prepare("SELECT test_name, price FROM lab_tests WHERE id = ?");
            $t_stmt->bind_param("i", $test_id);
            $t_stmt->execute();
            $test = $t_stmt->get_result()->fetch_assoc();

            if ($test) {
                // Insert Lab Request
                $stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, nurse_id, test_id, priority, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiss", $patient_id, $nurse_id, $test_id, $priority, $notes);
                $stmt->execute();

                $billing_items[] = [
                    'description' => "Lab Test: " . $test['test_name'],
                    'type' => 'Lab',
                    'price' => $test['price']
                ];
            }
        }

        // Automated Invoice
        if (!empty($billing_items)) {
            $invoice_id = $billing->automateInvoice($patient_id, $billing_items);

            // Notify Accountant
            require_once '../includes/notifications_helper.php';
            NotificationService::setConnection($conn);
            NotificationService::send('admin', 0, 'lab_payment_pending', 'New Lab Request', "Lab tests requested for patient ID $patient_id. Invoice #$invoice_id generated.", 'manage_invoices.php?status=Pending');
        }

        // Update Queue Status
        $conn->query("INSERT INTO patient_queue_status (patient_id, current_stage, status, notes)
                      VALUES ($patient_id, 'Lab', 'Waiting for Tests', 'Nurse requested multiple tests')
                      ON DUPLICATE KEY UPDATE current_stage='Lab', status='Waiting for Tests', notes='Nurse requested multiple tests'");

        $conn->commit();
        SyncManager::signal('lab_requests', 'INSERT');
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_reports') {
    // Basic stats for nursing dashboard
    $stats = [
        'today_vitals' => $conn->query("SELECT COUNT(*) FROM vital_signs WHERE DATE(recorded_at) = CURDATE()")->fetch_row()[0],
        'pending_referrals' => $conn->query("SELECT COUNT(*) FROM nursing_referrals WHERE status = 'Pending'")->fetch_row()[0],
        'patient_queue' => $conn->query("SELECT COUNT(*) FROM patient_onboarding WHERE status = 'Sent to Nursing'")->fetch_row()[0]
    ];
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

if ($action === 'get_templates') {
    $templates = $conn->query("SELECT * FROM clerking_templates")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'templates' => $templates]);
    exit;
}

if ($action === 'save_clerking') {
    $visit_id = $_POST['visit_id'];
    $template_id = $_POST['template_id'];
    $data_json = $_POST['data_json'];

    $stmt = $conn->prepare("INSERT INTO clerking_records (visit_id, template_id, nurse_id, data_json) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $visit_id, $template_id, $nurse_id, $data_json);

    if ($stmt->execute()) {
        SyncManager::signal('clinical_visits', 'UPDATE', $visit_id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

if ($action === 'get_vitals_history') {
    $patient_id = $_GET['patient_id'];
    $sql = "SELECT * FROM vital_signs WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'history' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'get_lab_results') {
    $patient_id = $_GET['patient_id'];
    $sql = "SELECT lr.*, lt.test_name, lt.category
            FROM lab_requests lr
            JOIN lab_tests lt ON lr.test_id = lt.id
            WHERE lr.patient_id = ?
            ORDER BY lr.requested_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'results' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'get_recent_patients') {
    $sql = "SELECT id, full_name, file_number, phone, gender, age FROM patients WHERE is_deleted = 0 ORDER BY created_at DESC LIMIT 6";
    $result = $conn->query($sql);
    echo json_encode(['success' => true, 'patients' => $result->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'get_all_visits') {
    $sql = "SELECT v.*, p.full_name, p.file_number, n.name as nurse_name
            FROM patient_visits v
            JOIN patients p ON v.patient_id = p.id
            LEFT JOIN nurses n ON v.nurse_id = n.id
            ORDER BY v.visit_date DESC LIMIT 50";
    $result = $conn->query($sql);
    echo json_encode(['success' => true, 'visits' => $result->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'get_visit_details') {
    $visit_id = $_GET['visit_id'];
    $v_sql = "SELECT v.*, p.full_name, p.file_number, p.phone FROM patient_visits v JOIN patients p ON v.patient_id = p.id WHERE v.id = ?";
    $v_stmt = $conn->prepare($v_sql);
    $v_stmt->bind_param("i", $visit_id);
    $v_stmt->execute();
    $visit = $v_stmt->get_result()->fetch_assoc();

    $vit_sql = "SELECT * FROM vital_signs WHERE visit_id = ?";
    $vit_stmt = $conn->prepare($vit_sql);
    $vit_stmt->bind_param("i", $visit_id);
    $vit_stmt->execute();
    $vitals = $vit_stmt->get_result()->fetch_assoc();

    $clk_sql = "SELECT cr.*, ct.name as template_name 
               FROM clerking_records cr 
               JOIN clerking_templates ct ON cr.template_id = ct.id 
               WHERE cr.visit_id = ?";
    $clk_stmt = $conn->prepare($clk_sql);
    $clk_stmt->bind_param("i", $visit_id);
    $clk_stmt->execute();
    $clerking = $clk_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'visit' => $visit,
        'vitals' => $vitals,
        'clerking' => $clerking
    ]);
    exit;
}

if ($action === 'get_onboarding_data') {
    $patient_id = $_GET['patient_id'];
    $sql = "SELECT complaints, history FROM patient_onboarding WHERE patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    echo json_encode(['success' => true, 'complaints' => $data['complaints'] ?? '', 'history' => $data['history'] ?? '']);
    exit;
}
