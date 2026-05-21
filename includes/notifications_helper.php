<?php
if (file_exists(__DIR__ . '/config.php')) require_once __DIR__ . '/config.php';
if (file_exists(__DIR__ . '/SimpleSMTP.php')) require_once __DIR__ . '/SimpleSMTP.php';
if (file_exists(__DIR__ . '/sync_helper.php')) require_once __DIR__ . '/sync_helper.php';

/**
 * Centralized notification system for Email, WhatsApp, and In-App alerts
 */
class NotificationService {
    private static $conn;

    public static function setConnection($conn) {
        self::$conn = $conn;
    }

    public static function send($role, $user_id, $type, $title, $message, $action_url = null, $extra_data = []) {
        // 1. In-App Notification
        self::createInApp($role, $type, $title, $message, $user_id, $action_url);

        // 2. Email Notification
        if (!empty($extra_data['email'])) {
            self::sendEmail($extra_data['email'], $title, $message);
        }

        // 3. WhatsApp Notification
        if (!empty($extra_data['phone'])) {
            self::sendWhatsApp($extra_data['phone'], $message);
        }
    }

    private static function createInApp($role, $type, $title, $message, $user_id, $action_url) {
        $conn = self::$conn;
        if (!$conn) return;

        $inserted_id = 0;
        $stmt = null;

        try {
            if ($role === 'admin') {
                $stmt = $conn->prepare("INSERT INTO admin_notifications (type, title, message, action_url) VALUES (?, ?, ?, ?)");
                if ($stmt) $stmt->bind_param("ssss", $type, $title, $message, $action_url);
            } elseif ($role === 'doctor') {
                $stmt = $conn->prepare("INSERT INTO doctor_notifications (doctor_id, type, title, message, action_url) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) $stmt->bind_param("issss", $user_id, $type, $title, $message, $action_url);
            } elseif ($role === 'patient') {
                $stmt = $conn->prepare("INSERT INTO patient_notifications (patient_id, type, title, message, action_url) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) $stmt->bind_param("issss", $user_id, $type, $title, $message, $action_url);
            }
            
            if ($stmt && $stmt->execute()) {
                $inserted_id = $conn->insert_id;
                // Trigger Real-Time Sync Signal for Notifications
                if (class_exists('SyncManager')) {
                    SyncManager::signal('notifications', 'INSERT', $user_id);
                }
            }
        } catch (Throwable $e) {
            error_log("In-App Notification failed: " . $e->getMessage());
        }
    }

    public static function sendEmail($to, $subject, $message) {
        if (!defined('SMTP_HOST') || !class_exists('SimpleSMTP')) return false;
        try {
            $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
            
            $html_msg = "
            <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
                <div style='background: #2563eb; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>Hope Haven Hospital</h2>
                </div>
                <div style='padding: 30px; line-height: 1.6; color: #333;'>
                    <h3 style='color: #2563eb; margin-top: 0;'>$subject</h3>
                    <p>" . nl2br($message) . "</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 30px 0;'>
                    <p style='font-size: 12px; color: #999; text-align: center;'>
                        &copy; " . date('Y') . " Hope Haven Hospital. This is an automated notification.
                    </p>
                </div>
            </div>";

            return $smtp->send($to, $subject, $html_msg, FROM_EMAIL, FROM_NAME);
        } catch (Throwable $e) {
            error_log("Email failed: " . $e->getMessage());
            return false;
        }
    }

    public static function sendWhatsApp($phone, $message) {
        $conn = self::$conn;
        $res = $conn->query("SELECT `key`, `value` FROM system_settings WHERE `key` IN ('whatsapp_api_url', 'whatsapp_token', 'whatsapp_from', 'enable_whatsapp')");
        $settings = [];
        while($row = $res->fetch_assoc()) $settings[$row['key']] = $row['value'];

        if (($settings['enable_whatsapp'] ?? '0') !== '1') return false;

        // Clean phone number: remove non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle local Nigerian format (e.g. 080...)
        if (strlen($phone) == 11 && strpos($phone, '0') === 0) {
            $phone = '234' . substr($phone, 1);
        }
        
        // Final length check
        if (strlen($phone) < 10) return false;

        $url = $settings['whatsapp_api_url']; // Twilio URL
        $token = $settings['whatsapp_token']; // Twilio Auth Token
        $from = $settings['whatsapp_from'];   // Twilio WhatsApp Number
        
        // Ensure 'whatsapp:' prefix for 'From' number
        if (strpos($from, 'whatsapp:') !== 0) {
            $from = "whatsapp:" . $from;
        }

        // Twilio requires the + prefix for the destination number
        $to = "whatsapp:+" . ltrim($phone, '+');

        // Parse SID from URL for Basic Auth
        preg_match('/Accounts\/(AC[a-z0-9]+)\//i', $url, $matches);
        $sid = $matches[1] ?? '';

        $data = [
            'From' => $from,
            'To' => $to,
            'Body' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($sid && $token) {
            curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        }
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 400) {
            error_log("WhatsApp API Error ($http_code): " . $response);
            return false;
        }
        return true;
    }
}
?>