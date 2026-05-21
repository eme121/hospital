<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/sync_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$action = $_POST['action'] ?? '';

if ($action === 'finalize') {
    $patient_id = intval($_POST['patient_id']);
    $visit_id = intval($_POST['visit_id']);
    $diagnosis = $_POST['diagnosis'] ?? '';
    $clinical_notes = $_POST['clinical_notes'] ?? '';
    $follow_up = $_POST['follow_up_date'] ?? null;
    $meds_json = $_POST['medications_json'] ?? '[]';

    $conn->begin_transaction();
    try {
        // Schema Check (Lazy Migration)
        $conn->query("ALTER TABLE patient_visits ADD COLUMN IF NOT EXISTS clinical_notes TEXT AFTER diagnosis");
        $conn->query("ALTER TABLE patient_visits ADD COLUMN IF NOT EXISTS follow_up_date DATE AFTER clinical_notes");

        require_once 'billing_engine.php';
        $billing = new BillingEngine($conn);

        // 1. Update Patient Visit
        $stmt = $conn->prepare("UPDATE patient_visits SET diagnosis = ?, clinical_notes = ?, follow_up_date = ?, status = 'Finalized' WHERE id = ?");
        $stmt->bind_param("sssi", $diagnosis, $clinical_notes, $follow_up, $visit_id);
        $stmt->execute();

        // 2. Save Prescription
        $check_presc = $conn->query("SHOW TABLES LIKE 'telemedicine_prescriptions'");
        if ($check_presc->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO telemedicine_prescriptions (patient_id, doctor_id, visit_id, medications_json) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $patient_id, $doctor_id, $visit_id, $meds_json);
            $stmt->execute();
            $prescription_id = $conn->insert_id;

            // 2.1 Automate Pharmacy Billing if medications are present
            $meds = json_decode($meds_json, true);
            if (!empty($meds)) {
                $billing_items = [];
                foreach ($meds as $m) {
                    $drug_name = $conn->real_escape_string($m['drug']);
                    $res = $conn->query("SELECT price FROM medications WHERE name LIKE '%$drug_name%' LIMIT 1");
                    $price = 0;
                    if ($res && $res->num_rows > 0) {
                        $price = floatval($res->fetch_assoc()['price']);
                    }
                    
                    if ($price > 0) {
                        $billing_items[] = [
                            'description' => $m['drug'] . " (" . $m['dosage'] . ")",
                            'type' => 'Medication',
                            'price' => $price,
                            'quantity' => 1 // Default to 1 unit for billing, can be adjusted by pharmacist
                        ];
                    }
                }
                
                if (!empty($billing_items)) {
                    $billing->automateInvoice($patient_id, $billing_items);
                    SyncManager::signal('billing', 'INSERT', $patient_id);
                }
            }
            SyncManager::signal('prescriptions', 'INSERT', $prescription_id);
        }

        // 3. Update Queue Status to 'Pharmacy'
        $conn->query("INSERT INTO patient_queue_status (patient_id, current_stage, status, notes)
                      VALUES ($patient_id, 'Pharmacy', 'Waiting for Dispensing', 'Consultation Completed')
                      ON DUPLICATE KEY UPDATE current_stage='Pharmacy', status='Waiting for Dispensing', notes='Consultation Completed', updated_at=CURRENT_TIMESTAMP");

        SyncManager::signal('patient_queue', 'UPDATE', $patient_id);

        // 4. Update Onboarding status if it exists        $conn->query("UPDATE patient_onboarding SET status = 'Completed' WHERE patient_id = $patient_id");

        $conn->commit();
        
        // Finalize Signals
        SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
        SyncManager::signal('clinical_visits', 'UPDATE', $visit_id);

        echo json_encode(['success' => true, 'message' => 'Consultation finalized successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fallback for just saving progress without finalizing
if ($action === 'save_progress') {
    // Implement if needed
}
?>