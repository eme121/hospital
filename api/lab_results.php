<?php
session_start();
if (!isset($_SESSION['lab_tech_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'release') {
    $request_id = intval($_POST['request_id']);
    $patient_id = intval($_POST['patient_id']);
    $findings = $_POST['findings'];
    $tech_id = $_SESSION['lab_tech_id'];
    $file_name = null;

    // Handle File Upload
    if (isset($_FILES['result_file']) && $_FILES['result_file']['error'] == 0) {
        $ext = pathinfo($_FILES['result_file']['name'], PATHINFO_EXTENSION);
        $file_name = "LAB_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['result_file']['tmp_name'], "../assets/lab_results/" . $file_name);
    }

    $conn->begin_transaction();
    try {
        // 1. Insert into Lab Results (Default status is Pending Review)
        $stmt = $conn->prepare("INSERT INTO lab_results (request_id, patient_id, technician_id, findings, result_file, status) VALUES (?, ?, ?, ?, ?, 'Pending Review')");
        $stmt->bind_param("iiiss", $request_id, $patient_id, $tech_id, $findings, $file_name);
        $stmt->execute();

        // 2. Update Request Status
        $update = $conn->prepare("UPDATE lab_requests SET status = 'Completed' WHERE id = ?");
        $update->bind_param("i", $request_id);
        $update->execute();

        // 3. Notify Doctor ONLY
        require_once '../includes/notifications_helper.php';
        NotificationService::setConnection($conn);

        $test_stmt = $conn->prepare("SELECT t.test_name, r.doctor_id, r.case_id, p.full_name as patient_name, d.email as doctor_email, d.phone as doctor_phone FROM lab_requests r JOIN lab_tests t ON r.test_id = t.id JOIN patients p ON r.patient_id = p.id LEFT JOIN doctors d ON r.doctor_id = d.id WHERE r.id = ?");
        $test_stmt->bind_param("i", $request_id);
        $test_stmt->execute();
        $info = $test_stmt->get_result()->fetch_assoc();
        
        if ($info) {
            $test_name = $info['test_name'];
            $doctor_id = $info['doctor_id'];
            $case_id = $info['case_id'];
            $patient_name = $info['patient_name'];
            
            $msg_doctor = "New lab results are ready for review.\nPatient: $patient_name\nTest: $test_name\nPlease authorize release to patient.";
            
            NotificationService::send('doctor', $doctor_id, 'lab', 'Lab Results Ready for Review', $msg_doctor, "telemedicine_case.php?id=$case_id", [
                'email' => $info['doctor_email'],
                'phone' => $info['doctor_phone']
            ]);
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>