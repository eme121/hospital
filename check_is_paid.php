<?php
require_once 'includes/db_connect.php';

function check_column($table, $column) {
    global $conn;
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($res && $res->num_rows > 0) {
        echo "Column '$column' exists in table '$table'.\n";
    } else {
        echo "Column '$column' MISSING from table '$table'.\n";
    }
}

check_column('appointments', 'is_paid');
check_column('telemedicine_appointments', 'is_paid');
check_column('invoices', 'appointment_id');
check_column('invoices', 'appointment_type');
?>