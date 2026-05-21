<?php
require_once '../includes/db_connect.php';

$dept_id = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'physical';

// Smart Filtering: Only show doctors who are enabled for the specific booking type
if ($type == 'virtual') {
    // Show doctors from the main doctors table enabled for Virtual Care
    $sql = "SELECT id, name, image_url FROM doctors WHERE department_id = ? AND allow_virtual = 1 ORDER BY name ASC";
} else {
    // Show doctors from the main doctors table enabled for Physical Visit
    $sql = "SELECT id, name, image_url FROM doctors WHERE department_id = ? AND allow_physical = 1 ORDER BY name ASC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dept_id);
$stmt->execute();
$result = $stmt->get_result();

$doctors = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($doctors);
?>