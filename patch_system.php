<?php
require_once 'includes/db_connect.php';

echo "<h2>System Upgrade: Financial Aid & Presence</h2>";

// 1. Update financial_aid_requests table
$cols = [
    'is_approved' => "INT(1) DEFAULT 0",
    'display_on_site' => "INT(1) DEFAULT 0",
    'display_until' => "DATETIME DEFAULT NULL"
];

foreach ($cols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM financial_aid_requests LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE financial_aid_requests ADD COLUMN $col $def");
        echo "✅ Added column: $col to financial_aid_requests<br>";
    }
}

// 2. Ensure telemedicine_presence has all required columns
$conn->query("ALTER TABLE telemedicine_presence ADD COLUMN IF NOT EXISTS case_id INT(11) DEFAULT NULL");
$conn->query("ALTER TABLE telemedicine_presence ADD COLUMN IF NOT EXISTS is_typing_in_case INT(1) DEFAULT 0");

// 3. Generate explained.txt
$explanation = "
HOSPITAL SYSTEM MODULE EXPLANATIONS
===================================

1. TELEMEDICINE (DOCTOR CASE DISCUSSION)
----------------------------------------
- When a doctor clicks 'Vitals', 'Prescription', or 'Lab' inside a case discussion:
- NEXT STEP: Data is saved to specific tables (vitals, lab_requests, telemedicine_prescriptions).
- SYNC: A system message is automatically posted to the chat (e.g., 'Lab Test Requested: Malaria').
- WHY: To keep all specialists updated in real-time.
- BILLING: Lab requests automatically trigger a charge on the patient's next invoice.
- PERMISSIONS: Only the original creator of a case (the lead doctor) can close or finalize it.

2. NURSE PORTAL (THE GATEKEEPER)
--------------------------------
- RECEPTION: Nurses register patients and create 'Triage' records.
- TRIAGE: They record initial BP, Heart Rate, and Temp before the doctor sees the patient.
- STATUS: They move patients from 'Waiting' to 'With Doctor'.
- WHY: To ensure the doctor has all baseline data ready before the consultation starts.

3. ADMIN PORTAL (THE COMMAND CENTER)
------------------------------------
- HR: Admin manages staff credentials (approving/declining doctors).
- FINANCE: Oversees revenue from invoices and diagnostic tests.
- CONTENT: Manages site elements like 'Financial Aid' display.
- SECURITY: Controls the 'is_approved' status for clinical staff.

4. FINANCIAL AID SYSTEM
-----------------------
- REQUEST: Patients apply for aid via their dashboard.
- ADMIN APPROVAL: Admin must set 'Approved' and 'Display on Site' before it appears to the public.
- DURATION: Admin sets a 'Display Until' date. Once expired, it disappears automatically.
- POSITION: The public widget appears at the bottom-left to be less intrusive.
";

file_put_contents('explained.txt', $explanation);
echo "✅ Generated explained.txt<br>";

echo "<br><b>Upgrade Complete!</b>";
?>