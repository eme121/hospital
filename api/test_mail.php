<?php
require_once '../includes/config.php';
require_once '../includes/SimpleSMTP.php';

header('Content-Type: text/plain');

$to = "okonkwoemekaisaac@gmail.com"; // Testing with your own email
$subject = "Test Email from Hope Haven Hospital (SMTP)";
$message = "This is a test email to check if the new SMTP system is working.";

echo "Attempting to send SMTP mail to: $to\n";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP User: " . SMTP_USER . "\n";

$smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);

if ($smtp->send($to, $subject, $message, FROM_EMAIL, FROM_NAME)) {
    echo "SUCCESS: SMTP mail sent successfully.\n";
} else {
    echo "FAILURE: SMTP mail failed.\n";
    echo "Logs:\n";
    print_r($smtp->getLog());
}
?>