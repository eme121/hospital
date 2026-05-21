<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';
require_once '../includes/SimpleSMTP.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
        exit;
    }

    // Check if table exists, create if not (Self-healing)
    $conn->query("CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        email VARCHAR(255) NOT NULL UNIQUE, 
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    try {
        $stmt = $conn->prepare("INSERT INTO newsletter_subscriptions (email) VALUES (?)");
        $stmt->bind_param("s", $email);

        if ($stmt->execute()) {
            // --- Send Welcome Email using SimpleSMTP ---
            $subject = "Welcome to Hope Haven Hospital Newsletter!";
            $from_name = defined('FROM_NAME') ? FROM_NAME : 'Hope Haven Hospital';
            $from_email = defined('FROM_EMAIL') ? FROM_EMAIL : 'no-reply@hopehaven.ng';

            $message = "
            <html>
            <head>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f7fafc; }
                    .container { max-width: 600px; margin: 20px auto; padding: 40px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; }
                    .header { text-align: center; margin-bottom: 40px; }
                    .logo { color: #2563eb; font-size: 24px; font-weight: bold; text-transform: uppercase; letter-spacing: -1px; }
                    .content { color: #4a5568; }
                    .footer { margin-top: 40px; font-size: 11px; color: #a0aec0; text-align: center; border-top: 1px solid #edf2f7; padding-top: 20px; }
                    .button { display: inline-block; padding: 16px 32px; background-color: #2563eb; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: bold; margin-top: 25px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <div class='logo'>Hope <span style='color:#64748b'>Haven</span></div>
                    </div>
                    <div class='content'>
                        <h2 style='color:#1a202c'>Welcome to the Community!</h2>
                        <p>Hi there,</p>
                        <p>Thank you for subscribing to our newsletter. You've joined a community dedicated to health excellence and compassionate care.</p>
                        <p>As a subscriber, you will receive:</p>
                        <ul style='padding-left: 20px;'>
                            <li>Expert health and wellness advice.</li>
                            <li>Priority notifications for hospital workshops.</li>
                            <li>Updates on new medical services and community programs.</li>
                        </ul>
                        <p>We look forward to staying in touch with you.</p>
                        <center><a href='" . BASE_URL . "' class='button'>Visit Our Website</a></center>
                    </div>
                    <div class='footer'>
                       &copy; " . date('Y') . " Hope Haven Hospital. Kwang, Plateau State, Nigeria.<br>
                       You received this because you subscribed via our website.<br>
                       If this wasn't you, please ignore this email.
                    </div>                </div>
            </body>
            </html>";

            // Initialize SMTP from config
            $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
            $sent = $smtp->send($email, $subject, $message, FROM_EMAIL, FROM_NAME);

            echo json_encode(['success' => true, 'message' => 'Subscription successful! A welcome email has been sent.']);
        }
    } catch (Exception $e) {
        if ($conn->errno == 1062) { // Duplicate entry
            echo json_encode(['success' => false, 'message' => 'You are already subscribed to our updates.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>