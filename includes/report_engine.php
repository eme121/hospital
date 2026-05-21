<?php
/**
 * Hope Haven Unified Analytics Engine
 * Architect: Senior Healthcare Systems Engineer
 */

class ReportEngine {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * FINANCIAL REPORTS
     */
    public function getFinancialSummary($start_date, $end_date) {
        $sql = "SELECT 
                    SUM(total_amount) as total_billed,
                    SUM(paid_amount) as total_collected,
                    SUM(total_amount - paid_amount) as total_outstanding,
                    COUNT(id) as invoice_count
                FROM invoices 
                WHERE DATE(created_at) BETWEEN ? AND ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getRevenueByDepartment($start_date, $end_date) {
        $sql = "SELECT 
                    ii.item_type, 
                    SUM(ii.subtotal) as revenue,
                    COUNT(*) as volume
                FROM invoice_items ii
                JOIN invoices i ON ii.invoice_id = i.id
                WHERE DATE(i.created_at) BETWEEN ? AND ?
                GROUP BY ii.item_type
                ORDER BY revenue DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * PHARMACY REPORTS
     */
    public function getPharmacyMetrics($start_date, $end_date) {
        $sql = "SELECT 
                    m.name, 
                    SUM(ii.quantity) as total_dispensed,
                    SUM(ii.subtotal) as total_revenue
                FROM invoice_items ii
                JOIN invoices i ON ii.invoice_id = i.id
                JOIN medications m ON ii.item_description LIKE CONCAT('%', m.name, '%')
                WHERE ii.item_type = 'Medication' 
                AND DATE(i.created_at) BETWEEN ? AND ?
                GROUP BY m.id
                ORDER BY total_dispensed DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLowStockAlerts() {
        return $this->conn->query("SELECT name, stock_quantity, reorder_level, unit 
                                 FROM medications 
                                 WHERE stock_quantity <= reorder_level 
                                 ORDER BY stock_quantity ASC")->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * LABORATORY REPORTS
     */
    public function getLabMetrics($start_date, $end_date) {
        $sql = "SELECT 
                    status, 
                    COUNT(*) as count 
                FROM lab_requests 
                WHERE DATE(requested_at) BETWEEN ? AND ?
                GROUP BY status";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * CLINICAL (NURSING & DOCTORS)
     */
    public function getPatientVolume($start_date, $end_date) {
        $sql = "SELECT 
                    DATE(appointment_date) as date, 
                    COUNT(*) as count,
                    appt_type
                FROM (
                    SELECT appointment_date, 'Physical' as appt_type FROM appointments
                    UNION ALL
                    SELECT appointment_date, 'Virtual' as appt_type FROM telemedicine_appointments
                ) as combined
                WHERE appointment_date BETWEEN ? AND ?
                GROUP BY DATE(appointment_date), appt_type
                ORDER BY date ASC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getVitalsSummary($start_date, $end_date) {
        $sql = "SELECT 
                    COUNT(*) as total_checks,
                    AVG(temperature) as avg_temp,
                    MAX(temperature) as max_temp,
                    COUNT(CASE WHEN temperature > 38 THEN 1 END) as fever_cases
                FROM vital_signs 
                WHERE DATE(recorded_at) BETWEEN ? AND ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['total_checks' => 0, 'avg_temp' => 0, 'max_temp' => 0, 'fever_cases' => 0];
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * DIAGNOSTIC INSIGHTS
     */
    public function getTopDiagnoses($start_date, $end_date) {
        $sql = "SELECT diagnosis, COUNT(*) as frequency 
                FROM patient_visits 
                WHERE diagnosis IS NOT NULL AND diagnosis != ''
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY diagnosis 
                ORDER BY frequency DESC LIMIT 10";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
