<?php
$conn = new mysqli('localhost', 'root', '', 'hospital_db');
if ($conn->connect_error) die("Connection failed");

$roles = [
    'Admin' => "SELECT username as identifier FROM admins",
    'Doctors' => "SELECT email as identifier, name FROM telemedicine_doctors",
    'Lab Technicians' => "SELECT email as identifier, name FROM lab_technicians",
    'Pharmacists' => "SELECT email as identifier, name FROM pharmacists",
    'Nurses' => "SELECT email as identifier, name FROM nurses"
];

foreach ($roles as $role => $sql) {
    echo "\n--- $role ---\n";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        while($row = $res->fetch_assoc()) {
            $u = $row['identifier'] ?? 'N/A';
            $n = $row['name'] ?? '';
            echo "Login ID: $u " . ($n ? "($n)" : "") . "\n";
        }
    } else {
        echo "No accounts found or table missing.\n";
    }
}
$conn->close();
?>