<?php
require_once 'includes/db_connect.php';
$result = $conn->query("SHOW TABLES LIKE 'events'");
if ($result->num_rows > 0) {
    echo "Table 'events' exists.\n";
    $columns = $conn->query("SHOW COLUMNS FROM events");
    while($col = $columns->fetch_assoc()) {
        print_r($col);
    }
} else {
    echo "Table 'events' does NOT exist.\n";
}

$result = $conn->query("SHOW COLUMNS FROM patients LIKE 'owed_amount'");
if ($result->num_rows > 0) {
    echo "Column 'owed_amount' exists in patients.\n";
} else {
    echo "Column 'owed_amount' does NOT exist in patients.\n";
}
?>
