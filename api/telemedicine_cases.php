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

if ($action === 'get_patients') {
    $result = $conn->query("SELECT id, full_name, email FROM patients ORDER BY full_name ASC");
    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
    echo json_encode(['success' => true, 'patients' => $patients]);
    exit;
}

function perform_ai_triage($symptoms) {
    $symptoms = strtolower($symptoms);
    $score = 10; // Base score
    $priority = 'Low';

    $high_risk = ['chest pain', 'breathless', 'unconscious', 'emergency', 'heart attack', 'stroke', 'bleeding'];
    $medium_risk = ['fever', 'severe', 'vomit', 'fracture', 'asthma', 'diabetes', 'hypertension', 'vision'];

    foreach ($high_risk as $word) {
        if (strpos($symptoms, $word) !== false) $score += 40;
    }
    foreach ($medium_risk as $word) {
        if (strpos($symptoms, $word) !== false) $score += 20;
    }

    if ($score >= 80) $priority = 'Emergency';
    elseif ($score >= 50) $priority = 'High';
    elseif ($score >= 30) $priority = 'Medium';

    return ['score' => min($score, 100), 'priority' => $priority];
}

if ($action === 'create') {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $patient_name = $_POST['patient_name_or_id'] ?? '';
    $symptoms = $_POST['symptoms'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $history = $_POST['medical_history'] ?? '';

    if (empty($patient_name) || empty($symptoms)) {
        echo json_encode(['success' => false, 'message' => 'Patient name and symptoms are required.']);
        exit;
    }

    $triage = perform_ai_triage($symptoms);
    $t_score = $triage['score'];
    $t_priority = $triage['priority'];

    $stmt = $conn->prepare("INSERT INTO telemedicine_cases (patient_id, patient_name_or_id, symptoms, diagnosis, medical_history, triage_score, triage_priority, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssissi", $patient_id, $patient_name, $symptoms, $diagnosis, $history, $t_score, $t_priority, $doctor_id);

    if ($stmt->execute()) {
        $case_id = $stmt->insert_id;
        
        // --- FEATURE 8: BOARD ROOM ---
        // Auto-add creator as Lead Physician
        $board_stmt = $conn->prepare("INSERT INTO telemedicine_case_members (case_id, doctor_id, role) VALUES (?, ?, 'Lead Physician')");
        $board_stmt->bind_param("ii", $case_id, $doctor_id);
        $board_stmt->execute();
        // -----------------------------

        SyncManager::signal('telemedicine_cases', 'INSERT', $case_id);
        
        // Notify Admin of new case (with short timeout to avoid hanging)
        require_once '../includes/config.php';
        require_once '../includes/SimpleSMTP.php';
        $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, 5);
        $admin_subject = "New Patient Case Initiated: $patient_name";
        $admin_message = "A new medical case has been created for collaboration.\n\n" .
                        "Patient: $patient_name\n" .
                        "Symptoms: $symptoms\n" .
                        "Initiated by Doctor ID: $doctor_id\n" .
                        "Case ID: $case_id";
        @$smtp->send(SMTP_USER, $admin_subject, $admin_message, FROM_EMAIL, FROM_NAME);

        echo json_encode(['success' => true, 'case_id' => $case_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
}

if ($action === 'update_status') {
    $case_id = intval($_POST['case_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($case_id <= 0 || !in_array($status, ['Open', 'Under Review', 'Closed'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
        exit;
    }

    // Permission Check: Only the creator can change the status
    $check_stmt = $conn->prepare("SELECT created_by FROM telemedicine_cases WHERE id = ?");
    $check_stmt->bind_param("i", $case_id);
    $check_stmt->execute();
    $case_owner = $check_stmt->get_result()->fetch_assoc()['created_by'] ?? 0;

    // Use current doctor ID and bridge check (email check)
    $is_owner = ($doctor_id == $case_owner);
    
    // Check if current doctor has same email as owner (bridge for doctors/telemedicine_doctors)
    if (!$is_owner) {
        $owner_email_stmt = $conn->prepare("SELECT email FROM telemedicine_doctors WHERE id = ?");
        $owner_email_stmt->bind_param("i", $case_owner);
        $owner_email_stmt->execute();
        $owner_email = $owner_email_stmt->get_result()->fetch_assoc()['email'] ?? '';

        $current_email_stmt = $conn->prepare("SELECT email FROM telemedicine_doctors WHERE id = ?");
        $current_email_stmt->bind_param("i", $doctor_id);
        $current_email_stmt->execute();
        $current_email = $current_email_stmt->get_result()->fetch_assoc()['email'] ?? '';

        if ($owner_email && $owner_email === $current_email) {
            $is_owner = true;
        }
    }

    if (!$is_owner) {
        echo json_encode(['success' => false, 'message' => 'Only the initiating specialist can change the case status.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE telemedicine_cases SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $case_id);

    if ($stmt->execute()) {
        SyncManager::signal('telemedicine_cases', 'UPDATE', $case_id);
        
        // FEATURE 10: LEDGER
        require_once '../includes/ledger_helper.php';
        log_telemedicine_ledger($conn, $case_id, $doctor_id, 'STATUS_CHANGE', "Case status updated to $status");
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}

if ($action === 'finalize') {
    $case_id = intval($_POST['case_id'] ?? 0);
    $subjective = $_POST['subjective'] ?? '';
    $objective = $_POST['objective'] ?? '';
    $assessment = $_POST['assessment'] ?? '';
    $plan = $_POST['plan'] ?? '';
    $signature = $_POST['signature_data'] ?? '';

    if ($case_id <= 0 || empty($assessment) || empty($plan) || empty($signature)) {
        echo json_encode(['success' => false, 'message' => 'Assessment, Plan, and Signature are required.']);
        exit;
    }

    $soap_summary = "--- FINAL SPECIALIST OPINION (SOAP) ---\n" .
                   "S: $subjective\n" .
                   "O: $objective\n" .
                   "A: $assessment\n" .
                   "P: $plan\n" .
                   "Signed by Specialist ID: $doctor_id on " . date('Y-m-d H:i');

    $stmt = $conn->prepare("UPDATE telemedicine_cases SET diagnosis = ?, specialist_signature = ?, status = 'Closed' WHERE id = ?");
    $stmt->bind_param("ssi", $soap_summary, $signature, $case_id);

    if ($stmt->execute()) {
        SyncManager::signal('telemedicine_cases', 'UPDATE', $case_id);
        
        // FEATURE 10: LEDGER
        require_once '../includes/ledger_helper.php';
        log_telemedicine_ledger($conn, $case_id, $doctor_id, 'CASE_FINALIZED', "Case finalized and closed with SOAP assessment.");

        // --- FEATURE 1: PATIENT BRIDGE ---
        $mr_title = "D2D Specialist Consultation Report";
        $mr_type = "Diagnostic";
        $mr_stmt = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, title, record_type, notes) SELECT patient_id, ?, ?, ?, ? FROM telemedicine_cases WHERE id = ?");
        $mr_stmt->bind_param("isssi", $doctor_id, $mr_title, $mr_type, $soap_summary, $case_id);
        $mr_stmt->execute();

        // Post a system message to the chat
        $system_msg = "Case finalized and closed by specialist. Report pushed to permanent medical record.";
        $stmt_msg = $conn->prepare("INSERT INTO telemedicine_messages (case_id, doctor_id, message, message_type) VALUES (?, ?, ?, 'clinical_action')");
        $stmt_msg->bind_param("iis", $case_id, $doctor_id, $system_msg);
        $stmt_msg->execute();
        SyncManager::signal('telemedicine_chat', 'INSERT', $case_id);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}

if ($action === 'get_specialists') {
    $res = $conn->query("
        SELECT d.id, d.name, dep.name as specialty 
        FROM telemedicine_doctors d 
        LEFT JOIN departments dep ON d.department_id = dep.id 
        WHERE d.id != $doctor_id LIMIT 50
    ");
    $specialists = [];
    while($row = $res->fetch_assoc()) {
        $specialists[] = $row;
    }
    echo json_encode(['success' => true, 'specialists' => $specialists]);
    exit;
}

if ($action === 'get_timeline_range') {
    $case_id = intval($_GET['case_id'] ?? 0);
    $stmt = $conn->prepare("SELECT created_at as start, NOW() as end FROM telemedicine_cases WHERE id = ?");
    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $range = $stmt->get_result()->fetch_assoc();
    echo json_encode(['success' => true, 'start' => $range['start'], 'end' => $range['end']]);
    exit;
}

if ($action === 'get_case_members') {
    $case_id = intval($_GET['case_id'] ?? 0);
    if ($case_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid case ID.']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT m.doctor_id, m.role, m.joined_at, d.name, dep.name as specialty 
        FROM telemedicine_case_members m
        JOIN telemedicine_doctors d ON m.doctor_id = d.id
        LEFT JOIN departments dep ON d.department_id = dep.id
        WHERE m.case_id = ?
        ORDER BY m.joined_at ASC
    ");
    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    echo json_encode(['success' => true, 'members' => $members]);
    exit;
}

if ($action === 'invite_specialist') {
    $case_id = intval($_POST['case_id'] ?? 0);
    $specialist_id = intval($_POST['specialist_id'] ?? 0);

    if ($case_id <= 0 || $specialist_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }

    // Get specialist name for the message
    $s_name = $conn->query("SELECT name FROM telemedicine_doctors WHERE id = $specialist_id")->fetch_assoc()['name'] ?? 'Specialist';

    // Post invitation to chat
    $chat_msg = "🤝 SPECIALIST INVITED: Dr. $s_name has been requested to join this case.";
    $stmt_msg = $conn->prepare("INSERT INTO telemedicine_messages (case_id, doctor_id, message, message_type) VALUES (?, ?, ?, 'clinical_action')");
    $stmt_msg->bind_param("iis", $case_id, $doctor_id, $chat_msg);
    
    if ($stmt_msg->execute()) {
        // --- FEATURE 8: BOARD ROOM ---
        // Add invited specialist to the case members as PENDING
        $member_stmt = $conn->prepare("INSERT INTO telemedicine_case_members (case_id, doctor_id, role, status) VALUES (?, ?, 'Consultant', 'pending') ON DUPLICATE KEY UPDATE status = 'pending'");
        $member_stmt->bind_param("ii", $case_id, $specialist_id);
        $member_stmt->execute();
        // -----------------------------

        // --- NEW: Send Official Notification ---
        $inviter_name = $_SESSION['doctor_name'];
        $notif_title = "🤝 New Case Invitation";
        $notif_msg = "Dr. $inviter_name has invited you to collaborate on Case #$case_id.";
        $notif_url = "telemedicine_dashboard.php";
        
        notify_doctor($specialist_id, 'invitation', $notif_title, $notif_msg, $notif_url);
        SyncManager::signal('telemedicine_chat', 'INSERT', $case_id);
        // ---------------------------------------

        // FEATURE 10: LEDGER
        require_once '../includes/ledger_helper.php';
        log_telemedicine_ledger($conn, $case_id, $doctor_id, 'SPECIALIST_INVITED', "Dr. $s_name (ID: $specialist_id) was invited to the board.");

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send invite.']);
    }
}

if ($action === 'accept_invitation') {
    $case_id = intval($_POST['case_id'] ?? 0);
    if ($case_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid case ID.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE telemedicine_case_members SET status = 'accepted', joined_at = NOW() WHERE case_id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $case_id, $doctor_id);
    
    if ($stmt->execute() && $conn->affected_rows > 0) {
        // Log to ledger
        require_once '../includes/ledger_helper.php';
        log_telemedicine_ledger($conn, $case_id, $doctor_id, 'INVITATION_ACCEPTED', "Specialist joined the case board.");
        
        // Post to chat
        $s_name = $conn->query("SELECT name FROM telemedicine_doctors WHERE id = $doctor_id")->fetch_assoc()['name'] ?? 'Specialist';
        $chat_msg = "✅ INVITATION ACCEPTED: Dr. $s_name has joined the case board.";
        $stmt_msg = $conn->prepare("INSERT INTO telemedicine_messages (case_id, doctor_id, message, message_type) VALUES (?, ?, ?, 'clinical_action')");
        $stmt_msg->bind_param("iis", $case_id, $doctor_id, $chat_msg);
        $stmt_msg->execute();

        // --- NEW: Notify Lead Physician ---
        $lead_res = $conn->query("SELECT doctor_id FROM telemedicine_case_members WHERE case_id = $case_id AND role = 'Lead Physician' LIMIT 1");
        if ($lead_res && $lead_row = $lead_res->fetch_assoc()) {
            $lead_id = $lead_row['doctor_id'];
            if ($lead_id != $doctor_id) {
                $notif_title = "✅ Invitation Accepted";
                $notif_msg = "Dr. $s_name has accepted your invitation and joined Case #$case_id.";
                $notif_url = "telemedicine_case.php?id=$case_id";
                
                notify_doctor($lead_id, 'invitation_accepted', $notif_title, $notif_msg, $notif_url);
            }
        }
        // -----------------------------------
        
        SyncManager::signal('telemedicine_chat', 'INSERT', $case_id);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to accept invitation or already accepted.']);
    }
}

if ($action === 'revoke_access') {
    $case_id = intval($_POST['case_id'] ?? 0);
    $target_doctor_id = intval($_POST['doctor_id'] ?? 0);

    if ($case_id <= 0 || $target_doctor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }

    // Security Check: Only the Lead Physician can revoke access
    $lead_stmt = $conn->prepare("SELECT doctor_id FROM telemedicine_case_members WHERE case_id = ? AND role = 'Lead Physician'");
    $lead_stmt->bind_param("i", $case_id);
    $lead_stmt->execute();
    $lead_id = $lead_stmt->get_result()->fetch_assoc()['doctor_id'] ?? 0;

    if ($doctor_id != $lead_id) {
        echo json_encode(['success' => false, 'message' => 'Only the Lead Physician can revoke access.']);
        exit;
    }

    if ($target_doctor_id == $lead_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot revoke your own access.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM telemedicine_case_members WHERE case_id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $case_id, $target_doctor_id);
    
    if ($stmt->execute()) {
        // Log to ledger
        require_once '../includes/ledger_helper.php';
        $s_name = $conn->query("SELECT name FROM telemedicine_doctors WHERE id = $target_doctor_id")->fetch_assoc()['name'] ?? 'Specialist';
        log_telemedicine_ledger($conn, $case_id, $doctor_id, 'ACCESS_REVOKED', "Access revoked for Dr. $s_name.");

        // Post to chat
        $chat_msg = "🚫 ACCESS REVOKED: Dr. $s_name has been removed from the clinical board.";
        $stmt_msg = $conn->prepare("INSERT INTO telemedicine_messages (case_id, doctor_id, message, message_type) VALUES (?, ?, ?, 'clinical_action')");
        $stmt_msg->bind_param("iis", $case_id, $doctor_id, $chat_msg);
        $stmt_msg->execute();

        // Signal for real-time kick
        SyncManager::signal('telemedicine_case_members', 'DELETE', $target_doctor_id);
        SyncManager::signal('telemedicine_chat', 'INSERT', $case_id);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to revoke access.']);
    }
}

if ($action === 'dismiss_case') {
    $case_id = intval($_POST['case_id'] ?? 0);
    $is_dismissed = intval($_POST['dismiss'] ?? 1);

    if ($case_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid case ID.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE telemedicine_case_members SET is_dismissed = ? WHERE case_id = ? AND doctor_id = ?");
    $stmt->bind_param("iii", $is_dismissed, $case_id, $doctor_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}
?>