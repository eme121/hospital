<?php
require_once __DIR__ . '/config.php';
date_default_timezone_set('Africa/Lagos');

// Use environment variables for database configuration, fallback to local defaults
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$dbname = getenv('DB_NAME') ?: "hospital_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Error: Please make sure MySQL is started in your XAMPP Control Panel. Error: " . $conn->connect_error);
}

// Set MySQL Timezone to match PHP
$conn->query("SET time_zone = '+01:00'");

if (!function_exists('create_notification')) {
    /**
     * @param string $role admin|doctor|patient
     * @param int|null $user_id target user ID (ignored if role is admin)
     */
    function create_notification($role, $type, $title, $message, $user_id = NULL, $action_url = NULL) {
        global $conn;
        $success = false;
        if ($role === 'admin') {
            $stmt = $conn->prepare("INSERT INTO admin_notifications (type, title, message, action_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $type, $title, $message, $action_url);
        } elseif ($role === 'doctor') {
            $stmt = $conn->prepare("INSERT INTO doctor_notifications (doctor_id, type, title, message, action_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $type, $title, $message, $action_url);
        } elseif ($role === 'patient') {
            $stmt = $conn->prepare("INSERT INTO patient_notifications (patient_id, type, title, message, action_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $type, $title, $message, $action_url);
        } else {
            return false;
        }
        
        if ($stmt->execute()) {
            $success = true;
            // Trigger Real-Time Sync Signal
            require_once __DIR__ . '/sync_helper.php';
            if (class_exists('SyncManager')) {
                SyncManager::signal('notifications', 'INSERT', $user_id);
            }
        }
        return $success;
    }
}

// Helper functions for common notifications
if (!function_exists('notify_admin')) {
    function notify_admin($type, $title, $message, $action_url = NULL) {
        return create_notification('admin', $type, $title, $message, NULL, $action_url);
    }
}

if (!function_exists('notify_doctor')) {
    function notify_doctor($doctor_id, $type, $title, $message, $action_url = NULL) {
        return create_notification('doctor', $type, $title, $message, $doctor_id, $action_url);
    }
}

if (!function_exists('notify_patient')) {
    function notify_patient($patient_id, $type, $title, $message, $action_url = NULL) {
        return create_notification('patient', $type, $title, $message, $patient_id, $action_url);
    }
}

if (!function_exists('log_audit')) {
    function log_audit($action, $entity_type = NULL, $entity_id = NULL, $details = NULL) {
        global $conn;
        $user_id = 0;
        $user_type = 'patient'; // Default

        if (isset($_SESSION['doctor_id'])) { 
            $user_id = $_SESSION['doctor_id']; 
            $user_type = ($_SESSION['doctor_type'] ?? '') === 'telemedicine_synced' ? 'telemedicine_doctor' : 'doctor'; 
        }
        elseif (isset($_SESSION['pharmacist_id'])) { $user_id = $_SESSION['pharmacist_id']; $user_type = 'pharmacist'; }
        elseif (isset($_SESSION['nurse_id'])) { $user_id = $_SESSION['nurse_id']; $user_type = 'nurse'; }
        elseif (isset($_SESSION['patient_id'])) { $user_id = $_SESSION['patient_id']; $user_type = 'patient'; }
        elseif (isset($_SESSION['admin_id'])) { $user_id = $_SESSION['admin_id']; $user_type = 'admin'; }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System';

        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_type, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssisss", $user_id, $user_type, $action, $entity_type, $entity_id, $details, $ip, $ua);
        return $stmt->execute();
    }
}