<?php
header('Content-Type: application/json');

// Include SMTP classes and config
require_once 'includes/config.php';
require_once 'includes/SimpleSMTP.php';

// Paystack Secret Key
$SECRET_KEY = defined('PAYSTACK_SECRET_KEY') ? PAYSTACK_SECRET_KEY : 'sk_test_fb729375b0ea14783f9fc74dfcc2629690ae4bb9';

// Log directory and file
$log_file = 'donation_logs.txt';
$admin_email = 'okonkwoemekaisaac@gmail.com';

/**
 * Helper to send email notifications
 */
function sendNotificationEmail($subject, $message) {
    global $admin_email;
    try {
        if (defined('SMTP_HOST') && !empty(SMTP_HOST)) {
            $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
            return @$smtp->send($admin_email, $subject, $message, FROM_EMAIL, FROM_NAME);
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // 1. Lead Capture Logic
    if ($action === 'lead_capture') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : 'Unknown';
        $email = isset($_POST['email']) ? trim($_POST['email']) : 'Unknown';
        $amount = isset($_POST['amount']) ? $_POST['amount'] : '0';
        $method = isset($_POST['method']) ? $_POST['method'] : 'Unknown';
        $category = isset($_POST['category']) ? $_POST['category'] : 'Unknown';
        
        $log_entry = date('Y-m-d H:i:s') . " | LEAD CAPTURE: Donor $name ($email) proceeded with $method for ₦$amount in category: $category\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Send Email (Don't let email failure block lead capture response)
        $subject = "New Donation Lead: $name";
        $body = "A donor has proceeded to the payment stage.\n\n" .
                "Name: $name\n" .
                "Email: $email\n" .
                "Amount: ₦$amount\n" .
                "Method: $method\n" .
                "Category: $category\n" .
                "Time: " . date('Y-m-d H:i:s');
        sendNotificationEmail($subject, $body);
        
        echo json_encode(['status' => 'success', 'message' => 'Lead Captured']);
        exit;
    }

    // 2. Manual Bank Transfer Logic
    if ($action === 'manual_transfer') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $ref = isset($_POST['transfer_ref']) ? trim($_POST['transfer_ref']) : 'None';
        $category = isset($_POST['donation_category']) ? $_POST['donation_category'] : 'Unknown';
        
        if (empty($name) || empty($email) || $amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Incomplete transfer details.']);
            exit;
        }

        $log_entry = date('Y-m-d H:i:s') . " | MANUAL TRANSFER: $name (₦$amount) submitted ref: $ref. Verification Pending.\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);

        // Send Email
        $subject = "Action Required: New Bank Transfer Donation";
        $body = "A donor has submitted a manual bank transfer receipt.\n\n" .
                "Name: $name\n" .
                "Email: $email\n" .
                "Amount: ₦" . number_format($amount, 2) . "\n" .
                "Category: $category\n" .
                "Reference/Remark: $ref\n" .
                "Status: Pending Verification\n" .
                "Time: " . date('Y-m-d H:i:s');
        sendNotificationEmail($subject, $body);

        echo json_encode([
            'status' => 'success',
            'message' => 'Transfer record received. Verification in progress.',
            'amount' => number_format($amount, 2)
        ]);
        exit;
    }

    // 3. Paystack Verification Logic
    if ($action === 'verify_paystack') {
        $reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : 'Unknown';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

        if (empty($reference)) {
            echo json_encode(['status' => 'error', 'message' => 'No reference provided.']);
            exit;
        }

        $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $SECRET_KEY", "Cache-Control: no-cache"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($result && isset($result['status']) && $result['status'] === true && $result['data']['status'] === 'success') {
            $log_entry = date('Y-m-d H:i:s') . " | PAYSTACK SUCCESS: ₦$amount verified with ref $reference\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            
            // Send Email
            $subject = "Donation Successful: ₦" . number_format($amount, 2);
            $body = "A donation has been successfully processed via Paystack.\n\n" .
                    "Name: $name\n" .
                    "Amount: ₦" . number_format($amount, 2) . "\n" .
                    "Paystack Ref: $reference\n" .
                    "Time: " . date('Y-m-d H:i:s');
            sendNotificationEmail($subject, $body);

            echo json_encode([
                'status' => 'success',
                'amount' => number_format($amount, 2)
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Transaction verification failed.']);
        }
        exit;
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>