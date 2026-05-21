<?php
require_once dirname(__DIR__) . '/includes/db_connect.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/SimpleSMTP.php';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$subject_msg = $_POST['subject'] ?? '';
$message_content = $_POST['message'] ?? '';

header('Content-Type: application/json');

if (empty($name) || empty($email) || empty($subject_msg) || empty($message_content)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
    exit;
}

// 1. Save to Database (Ensures message is received even if email fails)
$db_saved = false;
try {
    // Auto-create table if missing (Self-healing for live server)
    $conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $email, $subject_msg, $message_content);
        $db_saved = $stmt->execute();
        
        // Create an admin notification
        if (function_exists('create_notification')) {
            create_notification('admin', 'info', 'New Contact Message', "You have a new message from $name regarding $subject_msg");
        }
    }
} catch (Exception $e) {
    // Database failed, but we can still try email
}

// 2. Send confirmation to the client via SMTP
$client_email_sent = false;
try {
    if (defined('SMTP_HOST') && !empty(SMTP_HOST)) {
        $to = $email;
        $subject = "Message Received - Hope Haven Hospital";
        $message = "Dear $name,\n\nThank you for reaching out to Hope Haven Hospital. We have received your message regarding '$subject_msg' and our team will get back to you shortly.\n\nYour message:\n\"$message_content\"\n\nBest regards,\nHOPE Assistant";

        $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
        $client_email_sent = @$smtp->send($to, $subject, $message, FROM_EMAIL, FROM_NAME);

        // 3. Optional: Send a notification to the hospital admin
        $admin_subject = "New Contact Form Inquiry: $subject_msg";
        $admin_message = "New message from $name ($email):\n\n$message_content";
        @$smtp->send(SMTP_USER, $admin_subject, $admin_message, FROM_EMAIL, FROM_NAME);
    }
} catch (Exception $e) {
    // Email failed
}

// Return success if at least it's saved in DB
if ($db_saved) {
    echo json_encode(['success' => true, 'email_sent' => $client_email_sent, 'db_saved' => $db_saved]);
} else {
    echo json_encode(['success' => false, 'message' => 'System error: Unable to save message to database.']);
}
?>