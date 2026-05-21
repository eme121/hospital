<?php
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT DISTINCT diagnosis FROM patient_visits WHERE diagnosis LIKE ? LIMIT 5");
$search = "%$q%";
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while($row = $result->fetch_assoc()) {
    if ($row['diagnosis']) $suggestions[] = $row['diagnosis'];
}

// Add some common defaults if results are sparse
$defaults = ['Malaria', 'Typhoid Fever', 'Acute Bronchitis', 'Hypertension', 'Diabetes Mellitus', 'Gastroenteritis', 'Upper Respiratory Tract Infection'];
foreach($defaults as $d) {
    if (stripos($d, $q) !== false && !in_array($d, $suggestions)) {
        $suggestions[] = $d;
    }
}

echo json_encode(array_slice($suggestions, 0, 5));
?>