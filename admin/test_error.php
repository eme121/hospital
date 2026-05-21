<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "Testing Error Reporting...<br>";
trigger_error("This is a test error", E_USER_ERROR);
?>