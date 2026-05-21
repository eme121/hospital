<?php
/**
 * Automated Appointment Reminders Script
 * Designed to be run as a daily cron job.
 * Sends emails to patients scheduled for tomorrow.
 */

require_once '../includes/db_connect.php';
require_once '../includes/config.php';
require_once '../includes/SimpleSMTP.php';
require_once '../includes/SMSBridge.php';

echo "--- Starting Reminder Service [" . date('Y-m-d H:i:s') . "] ---\n";

$tomorrow = date('Y-m-d', strtotime('+1 day'));
$reminders_sent = 0;
$sms = new SMSBridge(SMS_SID, SMS_TOKEN, SMS_FROM);

// 1. Reminders for Physical Appointments
$sql_p = "SELECT a.*, p.full_name, p.email, p.phone, d.name as doctor_name 
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          LEFT JOIN doctors d ON a.doctor_id = d.id 
          WHERE a.appointment_date = ? AND a.status = 'Approved'";

$stmt_p = $conn->prepare($sql_p);
$stmt_p->bind_param("s", $tomorrow);
$stmt_p->execute();
$res_p = $stmt_p->get_result();

while ($row = $res_p->fetch_assoc()) {
    $to = $row['email'];
    $phone = $row['phone'];
    $subject = "Reminder: Appointment Tomorrow at Hope Haven Hospital";
    $message = "Dear " . $row['full_name'] . ",\n\nThis is a friendly reminder of your scheduled physical appointment tomorrow.\n\nDoctor: Dr. " . ($row['doctor_name'] ?? 'Medical Officer') . "\nTime: " . $row['appointment_time'] . "\n\nPlease arrive 15 minutes early. We look forward to seeing you.\n\nBest regards,\nHope Haven Hospital";
    
    $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
    if ($smtp->send($to, $subject, $message, FROM_EMAIL, FROM_NAME)) {
        $reminders_sent++;
    }

    // Send SMS
    if (!empty($phone)) {
        $sms_msg = "Hope Haven Hospital Reminder: You have an appointment tomorrow (" . date('M d', strtotime($tomorrow)) . ") at " . $row['appointment_time'] . " with Dr. " . ($row['doctor_name'] ?? 'Medical Officer') . ".";
        $sms->send($phone, $sms_msg);
    }
}

// 2. Reminders for Virtual Appointments
$sql_v = "SELECT a.*, p.full_name, p.email, p.phone, td.name as doctor_name 
          FROM telemedicine_appointments a 
          JOIN patients p ON a.patient_id = p.id 
          LEFT JOIN telemedicine_doctors td ON a.doctor_id = td.id 
          WHERE a.appointment_date = ? AND a.status = 'Confirmed'";

$stmt_v = $conn->prepare($sql_v);
$stmt_v->bind_param("s", $tomorrow);
$stmt_v->execute();
$res_v = $stmt_v->get_result();

while ($row = $res_v->fetch_assoc()) {
    $to = $row['email'];
    $phone = $row['phone'];
    $subject = "Reminder: Virtual Consultation Tomorrow";
    $message = "Dear " . $row['full_name'] . ",\n\nThis is a reminder for your upcoming virtual consultation tomorrow.\n\nSpecialist: " . ($row['doctor_name'] ?? 'Medical Specialist') . "\nTime: " . $row['appointment_time'] . "\n\nPlease log in to your patient dashboard 5 minutes before your time to join the call.\n\nBest regards,\nHope Haven Hospital";
    
    $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
    if ($smtp->send($to, $subject, $message, FROM_EMAIL, FROM_NAME)) {
        $reminders_sent++;
    }

    // Send SMS
    if (!empty($phone)) {
        $sms_msg = "Hope Haven: Virtual consultation reminder tomorrow at " . $row['appointment_time'] . ". Join via your dashboard.";
        $sms->send($phone, $sms_msg);
    }
}

echo "Total Reminders Sent: $reminders_sent\n";
echo "--- Reminder Service Finished ---\n";
?>