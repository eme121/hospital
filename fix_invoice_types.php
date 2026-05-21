<?php
require_once 'includes/db_connect.php';

echo "<h1>Data Consistency Patch</h1>";

// 1. Fix Invoices where appointment_type is '0' or empty
// We check if the appointment_id exists in telemedicine_appointments first
$invoices = $conn->query("SELECT id, appointment_id FROM invoices WHERE appointment_id IS NOT NULL AND (appointment_type IS NULL OR appointment_type = '' OR appointment_type = '0')");

$fixed = 0;
while ($inv = $invoices->fetch_assoc()) {
    $inv_id = $inv['id'];
    $appt_id = $inv['appointment_id'];
    
    // Check if it's in virtual table
    $check_virtual = $conn->query("SELECT id FROM telemedicine_appointments WHERE id = $appt_id");
    if ($check_virtual->num_rows > 0) {
        $conn->query("UPDATE invoices SET appointment_type = 'Virtual' WHERE id = $inv_id");
        $fixed++;
        echo "Fixed Invoice #$inv_id -> Set to Virtual<br>";
    } else {
        // Check if it's in physical table
        $check_physical = $conn->query("SELECT id FROM appointments WHERE id = $appt_id");
        if ($check_physical->num_rows > 0) {
            $conn->query("UPDATE invoices SET appointment_type = 'Physical' WHERE id = $inv_id");
            $fixed++;
            echo "Fixed Invoice #$inv_id -> Set to Physical<br>";
        }
    }
}

// 2. Also ensure that if an invoice is PAID, the linked appointment is also marked as PAID
$paid_invoices = $conn->query("SELECT appointment_id, appointment_type FROM invoices WHERE status = 'Paid' AND appointment_id IS NOT NULL");
$synced = 0;
while ($inv = $paid_invoices->fetch_assoc()) {
    $appt_id = $inv['appointment_id'];
    $type = $inv['appointment_type'];
    $table = ($type === 'Virtual') ? 'telemedicine_appointments' : 'appointments';
    
    $res = $conn->query("UPDATE $table SET is_paid = 1, status = 'Confirmed' WHERE id = $appt_id AND is_paid = 0");
    if ($conn->affected_rows > 0) {
        $synced++;
        echo "Synced Appointment #$appt_id in $table -> Set to Paid/Confirmed<br>";
    }
}

echo "<h2>Patch Summary</h2>";
echo "Fixed Invoice Types: $fixed<br>";
echo "Synced Appointment Payments: $synced<br>";
echo "<br><a href='admin/dashboard.php'>Return to Dashboard</a>";
?>