<?php
ob_start();
session_start();
require_once '../includes/db_connect.php';

$patient_id = $_SESSION['patient_id'] ?? null;
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$dept_id = intval($_POST['department'] ?? 0);
$doctor_id = intval($_POST['doctor'] ?? 0);
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$reason = $_POST['reason'] ?? '';
$type = $_POST['type'] ?? 'physical';

header('Content-Type: application/json');

function send_json_response($data) {
    $output = ob_get_clean();
    if (!empty($output)) {
        file_put_contents('appointment_debug.log', "Unexpected output: " . $output . "\n", FILE_APPEND);
    }
    echo json_encode($data);
    exit;
}

try {
    if (empty($name) || empty($email) || empty($phone) || $doctor_id <= 0 || empty($date) || empty($time)) {
        send_json_response(['success' => false, 'message' => 'Please fill all required fields correctly.']);
    }

    $availability_col = ($type == 'virtual') ? 'allow_virtual' : 'allow_physical';

    // 0. Validate that the DOCTOR exists and is available for this type of visit
    $doctor_check_sql = "SELECT id FROM doctors WHERE id = ? AND $availability_col = 1";
    $d_stmt = $conn->prepare($doctor_check_sql);
    if (!$d_stmt) {
        throw new Exception('Doctor check prepare failed: ' . $conn->error);
    }
    $d_stmt->bind_param("i", $doctor_id);
    $d_stmt->execute();
    if ($d_stmt->get_result()->num_rows === 0) {
        send_json_response(['success' => false, 'message' => 'Selected doctor is unavailable for this visit type. Please choose another.']);
    }

    // 1. Check if the DOCTOR is available at this time across BOTH tables
    $check_sql = "SELECT id FROM (
                    SELECT id, doctor_id, appointment_date, appointment_time, status FROM appointments
                    UNION ALL
                    SELECT id, doctor_id, appointment_date, appointment_time, status FROM telemedicine_appointments
                  ) as combined 
                  WHERE doctor_id = ? AND appointment_date = ? AND TIME_FORMAT(appointment_time, '%H:%i') = ? AND status IN ('Confirmed', 'Pending')";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception('Availability check prepare failed: ' . $conn->error);
    }
    $check_stmt->bind_param("iss", $doctor_id, $date, $time);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        send_json_response(['success' => false, 'message' => 'This doctor is already booked for this time slot.']);
    }

    // 2. Check if the PATIENT (email) already has an appointment at this exact time
    $patient_check_sql = "SELECT id FROM (
                            SELECT id, email, appointment_date, appointment_time, status FROM appointments
                            UNION ALL
                            SELECT id, email, appointment_date, appointment_time, status FROM telemedicine_appointments
                          ) as combined 
                          WHERE email = ? AND appointment_date = ? AND TIME_FORMAT(appointment_time, '%H:%i') = ? AND status IN ('Confirmed', 'Pending')";
    $p_stmt = $conn->prepare($patient_check_sql);
    if (!$p_stmt) {
        throw new Exception('Patient check prepare failed: ' . $conn->error);
    }
    $p_stmt->bind_param("sss", $email, $date, $time);
    $p_stmt->execute();
    if ($p_stmt->get_result()->num_rows > 0) {
        send_json_response(['success' => false, 'message' => 'You already have an appointment booked for this time slot.']);
    }

    $pid_val = ($patient_id !== null) ? intval($patient_id) : 0;

    if ($type == 'virtual') {
        $sql = "INSERT INTO telemedicine_appointments (patient_id, patient_name, email, phone, department_id, doctor_id, appointment_date, appointment_time, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    } else {
        $sql = "INSERT INTO appointments (patient_id, patient_name, email, phone, department_id, doctor_id, appointment_date, appointment_time, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Insert prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("isssiisss", $pid_val, $name, $email, $phone, $dept_id, $doctor_id, $date, $time, $reason);

    if ($stmt->execute()) {
        $appt_id = $conn->insert_id;
        require_once '../includes/notifications_helper.php';
        require_once 'billing_engine.php';
        NotificationService::setConnection($conn);
        $billing = new BillingEngine($conn);

        // Fetch Doctor Details for notification
        $doc_res = $conn->query("SELECT name, email, phone FROM doctors WHERE id = $doctor_id");
        if(!$doc_res || $doc_res->num_rows == 0) {
            $doc_res = $conn->query("SELECT name, email, phone FROM telemedicine_doctors WHERE id = $doctor_id");
        }
        
        $doc_info = ($doc_res) ? $doc_res->fetch_assoc() : ['name' => 'Doctor', 'email' => null, 'phone' => null];

        // 0. Automate Invoice
        $invoice_id = null;
        if ($pid_val > 0) {
            $fee = 0;
            if ($type == 'virtual') {
                $fee_res = $conn->query("SELECT value FROM system_settings WHERE `key` = 'virtual_consultation_fee'");
                $fee = floatval(($fee_res && $row = $fee_res->fetch_assoc()) ? $row['value'] : 5000);
            } else {
                $fee_res = $conn->query("SELECT consultation_fee FROM departments WHERE id = $dept_id");
                $fee = floatval(($fee_res && $row = $fee_res->fetch_assoc()) ? $row['consultation_fee'] : 0);
            }

            if ($fee > 0) {
                try {
                    $invoice_id = $billing->automateInvoice($pid_val, [[
                        'description' => "Consultation Fee - Dr. " . ($doc_info['name'] ?? 'Doctor') . " ($type)",
                        'type' => 'Consultation',
                        'price' => $fee
                    ]], $appt_id, ($type == 'virtual' ? 'Virtual' : 'Physical'));
                } catch (Throwable $e) {
                    file_put_contents('appointment_debug.log', "Billing failed: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
        }

        $notif_title = ($type == 'virtual') ? "New Tele-Consult" : "New Appointment";
        $notif_msg = "New appointment booking:\nPatient: $name\nDate: $date\nTime: $time\nType: $type\nReason: $reason";
        
        // 1. Notify Admin
        try {
            $admin_whatsapp_res = $conn->query("SELECT value FROM system_settings WHERE `key` = 'admin_whatsapp'");
            $admin_whatsapp = ($admin_whatsapp_res && $row = $admin_whatsapp_res->fetch_assoc()) ? $row['value'] : '';
            NotificationService::send('admin', null, 'appointment', $notif_title, $notif_msg, ($type == 'virtual' ? 'telemedicine.php' : 'dashboard.php'), [
                'email' => defined('SMTP_USER') ? SMTP_USER : null,
                'phone' => $admin_whatsapp
            ]);
        } catch (Throwable $e) {
            file_put_contents('appointment_debug.log', "Admin notification failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        // 2. Notify Doctor
        try {
            if ($doctor_id > 0) {
                NotificationService::send('doctor', $doctor_id, 'appointment', 'New Booking Received', $notif_msg, 'telemedicine_dashboard.php', [
                    'email' => $doc_info['email'] ?? null,
                    'phone' => $doc_info['phone'] ?? null
                ]);
            }
        } catch (Throwable $e) {
            file_put_contents('appointment_debug.log', "Doctor notification failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        // 3. Notify Patient
        try {
            $patient_msg = "Dear $name, your appointment has been booked for $date at $time with Dr. " . ($doc_info['name'] ?? 'Doctor') . ". Status: Pending Approval.";
            NotificationService::send('patient', $pid_val, 'appointment', 'Booking Confirmed', $patient_msg, 'patient_dashboard.php', [
                'email' => $email,
                'phone' => $phone
            ]);
        } catch (Throwable $e) {
            file_put_contents('appointment_debug.log', "Patient notification failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        // 4. Trigger Real-Time Sync Signal
        try {
            require_once '../includes/sync_helper.php';
            SyncManager::signal('patient_queue', 'INSERT', $appt_id);
        } catch (Throwable $e) {
            file_put_contents('appointment_debug.log', "Sync signal failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        send_json_response(['success' => true, 'invoice_id' => $invoice_id]);
    } else {
        throw new Exception('Database error: ' . ($stmt->error ?: $conn->error));
    }
} catch (Throwable $e) {
    file_put_contents('appointment_debug.log', "Fatal Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    send_json_response(['success' => false, 'message' => 'An internal error occurred: ' . $e->getMessage()]);
}
?>