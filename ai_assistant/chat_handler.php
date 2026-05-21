<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = strtolower(trim($input['message'] ?? ''));

$responses = json_decode(file_get_contents('responses.json'), true);

$reply = "";
$found = false;

// 1. Booking Check
$booking_keywords = ['book', 'appointment', 'schedule', 'doctor', 'see a doctor'];
foreach ($booking_keywords as $bk) {
    if (strpos($message, $bk) !== false) {
        $reply = "You can easily book an appointment with our specialist doctors right here: <a href='appointment.php' class='text-blue-600 font-bold underline'>Book Appointment Now</a>. I'm here if you need help with anything else!";
        $found = true;
        break;
    }
}

// 2. Emergency Check
$emergency_keywords = ['emergency', 'urgent', 'ambulance', 'accident', 'bleeding', 'unconscious'];
foreach ($emergency_keywords as $ek) {
    if (strpos($message, $ek) !== false) {
        $reply = $responses['emergency']['message'];
        $found = true;
        break;
    }
}

// 2. Keyword Match
if (!$found) {
    foreach ($responses['keywords'] as $keyword => $response) {
        if (strpos($message, $keyword) !== false) {
            $reply = $response;
            $found = true;
            break;
        }
    }
}

// 3. Service Match
if (!$found) {
    foreach ($responses['services'] as $service => $info) {
        if (strpos($message, $service) !== false) {
            $reply = $info . " Learn more here: " . $responses['links']['services'];
            $found = true;
            break;
        }
    }
}

// 4. Default Response
if (!$found) {
    $reply = "I'm sorry, I didn't quite catch that. Could you please specify if you're looking for our services, location, emergency info, or booking an appointment? You can also use the quick buttons below.";
}

echo json_encode(['reply' => $reply]);
