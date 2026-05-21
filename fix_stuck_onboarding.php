<?php
require_once 'includes/db_connect.php';

// Find patients who have confirmed folder payments in payment_history but are stuck in onboarding
$query = "SELECT po.patient_id, po.status as current_status, po.payment_status as current_pay_status
          FROM patient_onboarding po
          JOIN payment_history ph ON po.patient_id = ph.patient_id
          WHERE ph.description LIKE '%Folder Payment%' 
          AND ph.status = 'confirmed'
          AND (po.status = 'Payment Pending' OR po.status = 'Awaiting Confirmation')";

$res = $conn->query($query);
$fixed = 0;

while($row = $res->fetch_assoc()) {
    $pid = $row['patient_id'];
    $conn->query("UPDATE patient_onboarding SET status = 'Paid', payment_status = 'Confirmed' WHERE patient_id = $pid AND status IN ('Not Started', 'Payment Pending', 'Awaiting Confirmation')");
    $fixed++;
    echo "Fixed Patient ID: $pid (Was: {$row['current_status']})<br>";
}

echo "Total patients synchronized: $fixed";
?>