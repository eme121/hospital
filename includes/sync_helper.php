<?php
/**
 * Senior Architect Sync Engine
 * Handles real-time signal dispatching across the platform.
 */
class SyncManager {
    private static $instance = null;
    private $conn;

    private function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new SyncManager();
        }
        return self::$instance;
    }

    /**
     * Signal a real-time update for a specific module
     * 
     * @param string $module Name of the module (e.g., 'lab_requests', 'patient_queue')
     * @param string $type The type of change (e.g., 'UPDATE', 'INSERT', 'REFRESH')
     * @param int $id Optional ID of the affected record
     */
    public static function signal($module, $type = 'REFRESH', $id = null, $payload = null) {
        $sync = self::getInstance();
        if (!$sync->conn) return null;

        $token = md5(uniqid($module . rand(), true));
        
        $sender_id = null;
        $sender_name = null;
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (isset($_SESSION['doctor_id'])) {
            $sender_id = $_SESSION['doctor_id'];
            $sender_name = $_SESSION['doctor_name'] ?? 'Doctor';
        } elseif (isset($_SESSION['pharmacist_id'])) {
            $sender_id = $_SESSION['pharmacist_id'];
            $sender_name = $_SESSION['pharmacist_name'] ?? 'Pharmacist';
        } elseif (isset($_SESSION['nurse_id'])) {
            $sender_id = $_SESSION['nurse_id'];
            $sender_name = $_SESSION['nurse_name'] ?? 'Nurse';
        } elseif (isset($_SESSION['patient_id'])) {
            $sender_id = $_SESSION['patient_id'];
            $sender_name = $_SESSION['patient_name'] ?? 'Patient';
        } elseif (isset($_SESSION['admin_id'])) {
            $sender_id = $_SESSION['admin_id'];
            $sender_name = $_SESSION['admin_name'] ?? 'Admin';
        } elseif (isset($_SESSION['lab_tech_id'])) {
            $sender_id = $_SESSION['lab_tech_id'];
            $sender_name = $_SESSION['lab_tech_name'] ?? 'Lab Tech';
        } elseif (isset($_SESSION['accountant_id'])) {
            $sender_id = $_SESSION['accountant_id'];
            $sender_name = $_SESSION['accountant_name'] ?? 'Accountant';
        } elseif (isset($_SESSION['records_id'])) {
            $sender_id = $_SESSION['records_id'];
            $sender_name = $_SESSION['records_name'] ?? 'Records';
        }

        try {
            // 1. Update the module's global sync token
            $stmt = $sync->conn->prepare("UPDATE sync_registry SET sync_token = ? WHERE module_name = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $token, $module);
                $stmt->execute();
            }

            // 2. Dispatch a specific signal record
            $stmt_signal = $sync->conn->prepare("INSERT INTO sync_signals (module_name, signal_type, data_id, sender_id, sender_name, payload) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_signal) {
                $stmt_signal->bind_param("ssiiss", $module, $type, $id, $sender_id, $sender_name, $payload);
                $stmt_signal->execute();
            }
        } catch (Throwable $e) {
            error_log("Sync signal failed: " . $e->getMessage());
        }

        return $token;
    }
}
?>