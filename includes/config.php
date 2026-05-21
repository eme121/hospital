<?php
// .env Loader Function
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) return false;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
        return true;
    }
}

// Load .env from root, app root, or includes directory
loadEnv(__DIR__ . '/../../.env') || loadEnv(__DIR__ . '/../.env') || loadEnv(__DIR__ . '/.env');

// Smart BASE_URL Detection
if (!defined('BASE_URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Get the directory of the current script
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = str_replace('\\', '/', dirname($script_name));
    
    // Clean up the path to get the project root (hospital_final)
    $project_root = preg_replace('/(\/includes|\/api|\/admin|\/accountant|\/records|\/nurse|\/lab)$/', '', $path);
    $project_root = rtrim($project_root, '/');
    
    define('BASE_URL', $protocol . "://" . $host . $project_root);
}

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'okonkwoemekaisaac@gmail.com');
define('SMTP_PASS', 'khnq kioe euge mqhk'); // Your Google App Password configured correctly
define('FROM_EMAIL', 'no-reply@hopehaven.ng');
define('FROM_NAME', 'Hope Haven Hospital');

// Paystack Configuration (Test Keys)
define('PAYSTACK_PUBLIC_KEY', 'pk_test_5df65f502799b3962fdaf7c8d839c68ddb3ea4c0'); 
define('PAYSTACK_SECRET_KEY', 'sk_test_fb729375b0ea14783f9fc74dfcc2629690ae4bb9');

// SMS Configuration (Twilio Example)
define('SMS_SID', 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
define('SMS_TOKEN', 'YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
define('SMS_FROM', '+1234567890');