<?php
/**
 * Senior Architect Clinical Helper
 * Centralizes patient queue and case management logic.
 */
class ClinicalHelper {
    /**
     * Get patient queue for a specific stage
     * 
     * @param mysqli $conn
     * @param string $stage 'Doctor', 'Pharmacy', 'Lab', etc.
     * @param array $item_types Types of invoice items to check payment for
     * @return mysqli_result
     */
    public static function getPatientQueue($conn, $stage, $item_types = ['Consultation', 'Lab'], $doctor_id = null) {
        $item_types_str = "'" . implode("','", $item_types) . "'";
        
        // Dynamic check for doctor_id column to prevent errors if schema hasn't been updated
        $has_doc_col = $conn->query("SHOW COLUMNS FROM patient_queue_status LIKE 'doctor_id'")->num_rows > 0;
        
        // Only show patients that:
        // 1. Are assigned to THIS doctor
        // 2. OR are NOT assigned to anyone (doctor_id is 0 or NULL)
        $doctor_filter = ($doctor_id && $has_doc_col) ? "AND (q.doctor_id = $doctor_id OR q.doctor_id IS NULL OR q.doctor_id = 0)" : "";
        
        $sql = "SELECT q.*, p.full_name as patient_name, p.file_number, p.gender, p.age,
                       (SELECT inv.status FROM invoices inv JOIN invoice_items itm ON inv.id = itm.invoice_id
                        WHERE inv.patient_id = q.patient_id AND itm.item_type IN ($item_types_str) AND inv.status != 'Cancelled'
                        ORDER BY inv.created_at DESC LIMIT 1) as payment_status
                FROM patient_queue_status q
                JOIN patients p ON q.patient_id = p.id
                WHERE q.current_stage = '$stage'
                $doctor_filter
                ORDER BY q.updated_at DESC";
        
        return $conn->query($sql);
    }

    /**
     * Get collaborative cases for a doctor
     */
    public static function getDoctorCases($conn, $ids_list, $status = 'Open', $show_dismissed = false) {
        $dismissed_filter = $show_dismissed ? "" : "AND tcm.is_dismissed = 0";
        $sql = "SELECT tc.*, p.full_name as patient_name, tcm.status as invitation_status, tcm.is_dismissed
                FROM telemedicine_cases tc
                LEFT JOIN patients p ON tc.patient_id = p.id
                JOIN telemedicine_case_members tcm ON tc.id = tcm.case_id
                WHERE (tcm.doctor_id IN ($ids_list))
                AND (tc.status = '$status')
                $dismissed_filter
                ORDER BY tc.created_at DESC LIMIT 50";
        
        return $conn->query($sql);
    }

    /**
     * Get nursing queue from onboarding
     */
    public static function getNursingQueue($conn) {
        $sql = "SELECT p.id, p.full_name, p.file_number, p.phone, p.gender, p.age, po.form_progress
                FROM patient_onboarding po 
                JOIN patients p ON po.patient_id = p.id 
                WHERE po.status = 'Sent to Nursing'
                ORDER BY po.updated_at DESC";
        return $conn->query($sql);
    }

    /**
     * Get lab requests with payment status
     */
    public static function getLabRequests($conn, $status = 'Pending') {
        $sql = "SELECT r.*, p.full_name as patient_name, p.file_number, p.gender, p.age, 
                COALESCE(NULLIF(r.custom_test_name, ''), t.test_name) as test_name, 
                t.category, t.is_numeric, t.unit, t.reference_min, t.reference_max, 
                COALESCE(d.name, n.name) as requester_name,
                CASE WHEN d.id IS NOT NULL THEN 'Doctor' ELSE 'Nurse' END as requester_type,
                (SELECT inv.status FROM invoices inv 
                 JOIN invoice_items itm ON inv.id = itm.invoice_id 
                 WHERE inv.patient_id = r.patient_id 
                 AND itm.item_type = 'Lab'
                 AND inv.status != 'Cancelled'
                 ORDER BY ABS(TIMESTAMPDIFF(SECOND, inv.created_at, r.requested_at)) ASC LIMIT 1) as payment_status
                FROM lab_requests r 
                JOIN patients p ON r.patient_id = p.id 
                JOIN lab_tests t ON r.test_id = t.id 
                LEFT JOIN doctors d ON r.doctor_id = d.id 
                LEFT JOIN nurses n ON r.nurse_id = n.id
                WHERE r.status = ? 
                ORDER BY r.priority DESC, r.requested_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>