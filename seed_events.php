<?php
require_once 'includes/db_connect.php';

echo "<h1>Seeding Sample Events...</h1>";

$events = [
    [
        'title' => 'Community Health Screening',
        'description' => 'Join us for a free health screening session including blood pressure, sugar levels, and BMI checks for all community members.',
        'event_date' => date('Y-m-d', strtotime('+7 days')),
        'image' => 'evt_69c1526723e1b.jpeg'
    ],
    [
        'title' => 'Maternal & Child Health Workshop',
        'description' => 'A specialized workshop for expectant mothers and parents, focusing on nutrition and neonatal care.',
        'event_date' => date('Y-m-d', strtotime('+14 days')),
        'image' => '69be55ec5e44b.jpeg'
    ],
    [
        'title' => 'Advanced Orthopedic Seminar',
        'description' => 'Our lead specialists discuss the latest innovations in joint replacement and minimally invasive orthopedic surgery.',
        'event_date' => date('Y-m-d', strtotime('+21 days')),
        'image' => '69c01b316b0df.jpeg'
    ]
];

foreach ($events as $event) {
    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, image, is_deleted) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $event['title'], $event['description'], $event['event_date'], $event['image']);
    
    if ($stmt->execute()) {
        echo "<p>✅ Added Event: " . htmlspecialchars($event['title']) . "</p>";
    } else {
        echo "<p>❌ Error adding event: " . $conn->error . "</p>";
    }
}

echo "<br><a href='index.php'>Go back to Home</a>";
?>