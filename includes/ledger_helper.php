<?php
function log_telemedicine_ledger($conn, $case_id, $doctor_id, $action_type, $description) {
    $stmt = $conn->prepare("INSERT INTO telemedicine_ledger (case_id, doctor_id, action_type, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $case_id, $doctor_id, $action_type, $description);
    $stmt->execute();
}
?>