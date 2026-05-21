<?php
require_once 'includes/db_connect.php';
require_once 'api/billing_engine.php';

$billing = new BillingEngine($conn);

echo "<pre>";

// 1. Check for Virtual Appointments without linked Invoices
$fee_res = $conn->query("SELECT value FROM system_settings WHERE `key` = 'virtual_consultation_fee' LIMIT 1");
$base_fee = ($fee_res && $fee_res->num_rows > 0) ? $fee_res->fetch_assoc()['value'] : 5000;

$res = $conn->query("
    SELECT ta.id, ta.patient_id, ta.department_id, d.name as dept_name, d.consultation_fee
    FROM telemedicine_appointments ta
    JOIN departments d ON ta.department_id = d.id
    LEFT JOIN invoices i ON (i.appointment_id = ta.id AND i.appointment_type = 'Virtual')
    WHERE i.id IS NULL
");

echo "Checking for appointments without invoices...\n";

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "Found Appointment #{$row['id']} (Patient #{$row['patient_id']}) with no invoice. Generating one...\n";
        
        $fee = $row['consultation_fee'] > 0 ? $row['consultation_fee'] : $base_fee;
        
        $billing->automateInvoice($row['patient_id'], [[
            'description' => "Consultation Fee: " . ($row['dept_name'] ?? 'General Medicine') . " (Virtual)",
            'type' => 'Consultation',
            'price' => $fee
        ]], $row['id'], 'Virtual');
        
        echo "Invoice generated successfully for Appointment #{$row['id']} at amount ₦" . number_format($fee) . ".\n";
    }
} else {
    echo "No missing invoices found for virtual appointments.\n";
}

echo "\nChecking 'invoices' table to see what's there for appointments...\n";
$inv_res = $conn->query("SELECT * FROM invoices ORDER BY id DESC LIMIT 10");
while($inv = $inv_res->fetch_assoc()) {
    print_r($inv);
}

echo "</pre>";
?>