<?php
require_once 'includes/db_connect.php';

$res = $conn->query("SELECT q.*, p.full_name 
                    FROM patient_queue_status q 
                    JOIN patients p ON q.patient_id = p.id 
                    WHERE q.current_stage IN ('Doctor', 'Pharmacy')
                    ORDER BY q.updated_at DESC LIMIT 5");

echo "<h3>Recent Patients in Doctor/Pharmacy Queue</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Patient</th><th>Stage</th><th>Status</th><th>Last Update</th></tr>";
while($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['full_name']} (ID: {$row['patient_id']})</td>";
    echo "<td>{$row['current_stage']}</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>{$row['updated_at']}</td>";
    echo "</tr>";
}
echo "</table>";

$res2 = $conn->query("SELECT id, patient_id, status, diagnosis, visit_date FROM patient_visits ORDER BY visit_date DESC LIMIT 5");
echo "<h3>Recent Patient Visits</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Visit ID</th><th>Patient ID</th><th>Status</th><th>Diagnosis</th><th>Date</th></tr>";
while($row = $res2->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['patient_id']}</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>" . ($row['diagnosis'] ?? 'NULL') . "</td>";
    echo "<td>{$row['visit_date']}</td>";
    echo "</tr>";
}
echo "</table>";
?>