<?php
require_once '../includes/db_connect.php';
require_once '../includes/config.php';
require_once '../includes/SimpleSMTP.php';
require_once '../includes/SMSBridge.php';

header('Content-Type: application/json');

/**
 * Checks for prescriptions that need a dose reminder "now"
 */
function send_medication_reminders($conn) {
    $now_time = date('H:i');
    $today = date('Y-m-d');
    
    // Fetch active prescriptions
    $sql = "SELECT p.*, pat.full_name as patient_name, pat.email as patient_email, pat.phone as patient_phone 
            FROM telemedicine_prescriptions p
            JOIN patients pat ON p.patient_id = pat.id
            WHERE p.start_date <= ? AND p.end_date >= ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sent_count = 0;
    $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
    $sms = new SMSBridge(SMS_SID, SMS_TOKEN, SMS_FROM);
    
    while ($row = $result->fetch_assoc()) {
        $dosage_times = explode(',', $row['dosage_times']);
        foreach ($dosage_times as $time) {
            $time = trim($time);
            $diff = abs(strtotime($now_time) - strtotime($time)) / 60;
            
            if ($diff <= 10) {
                $to = $row['patient_email'];
                $phone = $row['patient_phone'];
                $med = $row['medications'];
                $dosage = $row['dosage'];

                $subject = "Medication Reminder: " . $med;
                $message = "Dear " . $row['patient_name'] . ",\n\nThis is a friendly reminder to take your medication: " . $med . ".\n\nDosage: " . $dosage . "\nScheduled Time: " . $time . "\n\nStay healthy!\nHope Haven Hospital";
                
                if ($smtp->send($to, $subject, $message, FROM_EMAIL, FROM_NAME)) {
                    $sent_count++;
                }

                if (!empty($phone)) {
                    $sms_msg = "Hope Haven Med Reminder: It's time for your $med ($dosage). Scheduled: $time.";
                    $sms->send($phone, $sms_msg);
                }
            }
        }
    }
    return $sent_count;
}

$sent = send_medication_reminders($conn);
echo json_encode(['success' => true, 'reminders_sent' => $sent]);
?>
