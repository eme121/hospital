<?php
session_start();
require_once '../../includes/db_connect.php';

// Security check
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized access");
}

/**
 * Professional Database Backup Script
 * Exports all tables, structure and data into a .sql file
 */

// Disable error reporting for cleaner output, but log them if needed
error_reporting(0);

// Set header to force download
$fileName = "backup_" . date('Y-m-d_H-i-s') . ".sql";
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "-- Hope Haven Hospital Database Backup\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- Server Version: " . $conn->server_info . "\n";
echo "-- ------------------------------------------------------\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n";
echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
echo "SET AUTOCOMMIT = 0;\n";
echo "START TRANSACTION;\n";
echo "SET time_zone = '+00:00';\n\n";

// Get all tables
$tables = array();
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Structure
    $result = $conn->query("SHOW CREATE TABLE `$table` ");
    $row = $result->fetch_row();
    echo "\n\n-- Structure for table `$table` --\n\n";
    echo "DROP TABLE IF EXISTS `$table`;\n";
    echo $row[1] . ";\n\n";

    // Data
    $result = $conn->query("SELECT * FROM `$table` ");
    $numFields = $result->field_count;

    if ($result->num_rows > 0) {
        echo "-- Data for table `$table` --\n";
        while ($row = $result->fetch_row()) {
            echo "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $numFields; $j++) {
                if (isset($row[$j])) {
                    // Handle numbers vs strings
                    $val = $conn->real_escape_string($row[$j]);
                    echo "'" . $val . "'";
                } else {
                    echo "NULL";
                }
                if ($j < ($numFields - 1)) {
                    echo ",";
                }
            }
            echo ");\n";
        }
    }
}

echo "\n\nCOMMIT;\n";
echo "SET FOREIGN_KEY_CHECKS=1;";
exit;
?>